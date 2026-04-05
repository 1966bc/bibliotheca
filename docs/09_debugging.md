# Chapter 09 — Debugging

## The blank page

The most frustrating thing in web development: your code looks
correct, but the browser shows nothing. No error, no message,
just white. This means PHP crashed before producing any output.

The browser cannot help you here. You need to look elsewhere.

## Think in layers

When something breaks, remember the architecture from Chapter
06: JavaScript calls the controller, the controller calls the
model, the model calls DBMS, DBMS talks to SQLite. The bug
lives in one of these layers. Your job is to find which one.

Start from the outside and work inward:

1. Is the browser showing a JavaScript error? → **Console**
2. Is the server returning an error code? → **Network tab**
3. Is PHP crashing? → **Apache error log**
4. Is the SQL wrong? → **SQLite command line**

Be methodical. Narrow it down.

## Apache error log

The server-side truth lives here:

```bash
sudo tail -20 /var/log/apache2/error.log
```

This log captures every PHP fatal error, every uncaught
exception, every permission problem. When the browser shows
a 500, this log tells you why.

For real-time monitoring, use `-f` — the log updates as
requests come in:

```bash
sudo tail -f /var/log/apache2/error.log
```

Keep this running in a terminal while you test in the browser.
You will see errors the moment they happen.

Common errors you will find:

- `could not find driver` — a PHP extension is missing.
- `attempt to write a readonly database` — permissions
  (Chapter 08).
- `Uncaught PDOException` — a SQL error, with the exact line
  number.

## curl: testing the API

Before blaming the frontend, test the backend directly:

```bash
curl http://localhost/bibliotheca/public/api/publishers.php
```

If this returns JSON, the backend works. If it returns nothing
or an error, the problem is server-side.

For POST requests, remember that the controller requires a
CSRF token (Chapter 10). Without it, you will get a 403. For
a quick diagnostic, you can test a GET first to confirm the
API is alive, then check the Network tab for POST issues.

Use `-v` for verbose output — it shows HTTP status codes,
headers, and the full request/response cycle:

```bash
curl -v http://localhost/bibliotheca/public/api/publishers.php
```

The `-v` flag is one of the most useful debugging tools you
have. It shows you exactly what the browser and server are
saying to each other.

## Browser developer tools

Press F12. Three tabs matter:

- **Console** — JavaScript errors appear here. If `fetch`
  fails or your code has a syntax error, this is where you
  see it.
- **Network** — every HTTP request the browser makes. Click
  on a request to see the status code, request body, and
  response body. If a fetch returns 500, click on it to see
  the server's error message.
- **Elements** — the live DOM. Useful to check if JavaScript
  actually built the HTML you expected. Remember the DOM
  diagram from Chapter 05: what you see here is the tree, not
  the source file.

## PHP debugging tools

When you need to inspect a value inside your PHP code, two
tools:

**`error_log()`** — writes to the Apache error log without
breaking the response:

```php
error_log('Publisher name: ' . $name);
error_log(print_r($data, true));
```

Check the output with `tail -f /var/log/apache2/error.log`.
This is safe to use in API controllers — it does not corrupt
the JSON response.

**`var_dump()`** — prints the value directly to the output:

```php
var_dump($data);
exit;
```

Useful for quick inspection, but it breaks the JSON response.
Use it only for temporary debugging, never in production code.
Remove it when you are done.

## PHP from the command line

You can run a PHP file directly to see errors without Apache:

```bash
php /var/www/html/bibliotheca/public/api/publishers.php
```

This bypasses Apache and permissions. If it works here but not
in the browser, the problem is Apache configuration or
permissions (Chapter 08).

## SQLite from the command line

You can inspect the database directly:

```bash
sqlite3 sql/bibliotheca.db
```

Useful commands inside the SQLite shell:

```sql
-- List all tables
.tables

-- Show the schema of a table
PRAGMA table_info(book);

-- Show all indexes in the database
.indices

-- Show the indexes of a specific table
PRAGMA index_list(book);

-- Show which columns an index covers
PRAGMA index_info(idx_book_publisher);

-- Check if foreign keys are enforced
PRAGMA foreign_keys;

-- Show the CREATE statement for a table
.schema book

-- Run a quick query
SELECT * FROM publisher ORDER BY name;
```

`PRAGMA table_info` is especially useful: it shows the column
name, type, whether it allows NULL, its default value, and
whether it is part of the primary key. Learn to use it — it
is faster than reading the schema file.

## The golden rule

When something breaks, resist the urge to change random
things hoping the problem goes away. Read the error message.
Follow the chain: browser console → network tab → Apache log →
PHP command line → SQLite shell. The answer is always in one
of these places. Your job is not to fix — it is to *find*.
Once you find it, the fix is usually obvious.

## Next

[Chapter 10 — Security](10_security.md)
