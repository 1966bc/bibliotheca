# Chapter 03 — Project Structure

## Security by design

The most important decision in a web application structure is what
the browser can and cannot reach. Everything inside the document root
is accessible via URL. Everything outside is not.

This is not a feature. It is a security rule.

## The directory tree

```
bibliotheca/
    docs/                       — the book you are reading
    sql/                        — database (schema, data, queries)
        ddl/                    — CREATE TABLE statements
        dml/                    — INSERT statements
        dql/                    — SELECT queries
        setconsole              — SQLite console configuration
        run.sql                 — scratch pad for quick queries
        bibliotheca.db          — the database file
    src/                        — PHP classes — NOT accessible from the web
        DBMS.php                — database wrapper
        Publisher.php           — Publisher model
        Category.php            — Category model
        Author.php              — Author model
        Book.php                — Book model
    public/                     — document root — ONLY folder exposed to the browser
        .htaccess               — URL rewriting rules
        index.php               — the router
        css/
            style.css
        js/
            publishers.js       — list view (plural)
            publisher.js        — form view (singular)
            ...                 — same pattern for each entity
        api/
            publishers.php      — JSON endpoint
            ...                 — one per entity
        pages/
            home.php
            publishers.php      — list page (plural)
            publisher.php       — form page (singular)
            ...                 — same pattern for each entity
            404.php
```

## Two worlds

- **Public** (`public/`) — what the browser can reach. HTML, CSS,
  JavaScript, and the API endpoints.
- **Private** (`src/`, `sql/`) — what only PHP can reach.

## The Stargate

See `public/.htaccess`.

When the browser requests `/publishers`, Apache looks for a file
or directory called `publishers` inside `public/`. It does not exist.
Without `.htaccess`, Apache would return a 404.

The rewrite rule changes this:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [L,QSA]
```

Line by line:

1. **RewriteEngine On** — enable URL rewriting.
2. **RewriteCond !-f** — only rewrite if the request is NOT an existing
   **f**ile (so `style.css` and `book.js` are served normally).
   The `-f` stands for *file*.
3. **RewriteCond !-d** — only rewrite if the request is NOT an existing
   **d**irectory. The `-d` stands for *directory*.
4. **RewriteRule** — capture the entire URL path and pass it to
   `index.php` as the `route` parameter. `[L]` means stop processing
   rules. `[QSA]` means append any existing query string (so
   `/book?id=3` becomes `index.php?route=book&id=3`).

The result: the user sees clean URLs (`/publishers`, `/book?id=3`),
but every request actually goes through `index.php` — the front
controller. This is the foundation of all modern web routing.

Note: `.htaccess` requires Apache's `mod_rewrite` module. If clean
URLs do not work, run `sudo a2enmod rewrite` and restart Apache.

## Naming convention

Plural for collections, singular for single entity:

- `publishers.php` / `publisher.php`
- `publishers.js` / `publisher.js`

This applies to pages, JavaScript files, and API endpoints.

Why do we care about naming conventions? To avoid *mental mapping*.
When a file is called `publishers.php`, you know immediately it
handles a list. When a variable is called `publisherId`, you know
what it holds. Consistent names mean you spend your mental energy
on the problem, not on decoding someone else's naming choices.

## The route to you

```
Browser                          Server
──────                           ──────
URL: /publishers
  └── .htaccess rewrites to index.php
        └── loads pages/publishers.php
              └── loads js/publishers.js
                    │
                    │  fetch('/api/publishers.php')
                    │
                    └──────────────────► api/publishers.php (Controller)
                                            │
                                            │  require '../../src/Publisher.php'
                                            │
                                            └──► src/Publisher.php (Model)
                                                    │
                                                    │  PDO query
                                                    │
                                                    └──► sql/bibliotheca.db
```

## Next

[Chapter 04 — Backend](04_backend.md)
