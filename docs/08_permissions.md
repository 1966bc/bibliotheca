# Chapter 08 — Permissions

## The problem

Your code works from the terminal. You open the browser and get a
blank page, or worse, a 500 Internal Server Error. The reason is
almost always permissions.

Apache runs as a user called `www-data`. Your files belong to your
user (e.g., `bc`). If `www-data` cannot read a file, the page is
blank. If it cannot write, the database operations fail silently.

## How Linux permissions work

Every file has three sets of permissions: owner, group, others.

```
-rw-r--r-- 1 youruser youruser 28672 bibliotheca.db
```

- `rw-` — owner (`youruser`) can read and write.
- `r--` — group (`youruser`) can only read.
- `r--` — others can only read.

Apache (`www-data`) is "others" here — it can read but not write.
That is why SELECT works but INSERT fails.

## The fix

Give the group `www-data` write access to the database file and
its directory (SQLite needs the directory for its journal file):

```bash
sudo chgrp www-data /var/www/html/bibliotheca/sql/bibliotheca.db
sudo chmod 664 /var/www/html/bibliotheca/sql/bibliotheca.db
sudo chgrp www-data /var/www/html/bibliotheca/sql/
sudo chmod 775 /var/www/html/bibliotheca/sql/
```

- `664` — owner reads/writes, group reads/writes, others read.
- `775` — same for directories, plus execute (needed to list contents).

## Every time you recreate the database

When you delete and recreate `bibliotheca.db`, the new file belongs
to your user with default permissions. You must set them again:

```bash
rm bibliotheca.db
sqlite3 bibliotheca.db ".read ddl/create_table.sql"
sqlite3 bibliotheca.db ".read dml/insert.sql"
sudo chgrp www-data bibliotheca.db
sudo chmod 664 bibliotheca.db
```

This is easy to forget. And when you forget, the application reads
data fine but fails on any write operation with:

```
SQLSTATE[HY000]: General error: 8 attempt to write a readonly database
```

## CSRF — protecting write operations

Permissions are not only about Linux file ownership. There is another
kind of permission problem: **who is allowed to make changes** through
the API.

### The attack

Imagine you have the Bibliotheca page open. A malicious website in
another tab could contain hidden JavaScript that sends a POST request
to `http://localhost/bibliotheca/public/api/publishers.php` with
`{"name": "HACKED"}`. The browser would happily send it — from the
browser's perspective, it is just another HTTP request to localhost.

This is called **CSRF** (Cross-Site Request Forgery): a malicious site
tricks the browser into making requests to *your* application.

### The defense — a secret token

The solution is a **CSRF token**: a long random string that only our
pages know. The malicious site cannot read it because the browser's
**Same-Origin Policy** prevents one site from reading another site's
content.

The flow:

1. When PHP renders the page (`index.php`), it generates a random
   token and stores it in the session:
   ```php
   Csrf::start();
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   ```

2. The token is injected into the HTML as a `<meta>` tag:
   ```html
   <meta name="csrf-token" content="a7f3b9c2e1d4...">
   ```

3. JavaScript reads the token and includes it in every POST/PUT/DELETE:
   ```javascript
   headers: {
       'Content-Type': 'application/json',
       'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
   }
   ```

4. The API verifies the token before processing the request:
   ```php
   Csrf::start();
   Csrf::verify();  // 403 if token is missing or wrong
   ```

The malicious site cannot read the `<meta>` tag from our page (Same-Origin
Policy), so it cannot send the correct token. The API rejects its request
with `403 Forbidden`.

### The implementation

The `Csrf` class (`src/Csrf.php`) has three methods:

- `Csrf::start()` — starts a PHP session (with secure
  cookie settings).
- `Csrf::token()` — returns the token, generating one
  if needed.
- `Csrf::verify()` — compares the `X-CSRF-Token` header
  against the session; exits with 403 on mismatch.

GET requests do not need CSRF protection — they only read data, they
never modify it. Only POST, PUT, and DELETE are checked.

### Verify it works

```bash
# Without token → 403
curl -s -o /dev/null -w "%{http_code}" -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -d '{"name": "Test"}'
# Prints: 403

# With token → 200 (get token from page first)
TOKEN=$(curl -s -c /tmp/c.txt http://localhost/bibliotheca/public/ \
    | grep csrf-token | sed 's/.*content="\([^"]*\)".*/\1/')
curl -s -o /dev/null -w "%{http_code}" -b /tmp/c.txt -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -H "X-CSRF-Token: $TOKEN" \
     -d '{"name": "Test"}'
# Prints: 200
```

## Next

[Chapter 09 — Debugging](09_debugging.md)
