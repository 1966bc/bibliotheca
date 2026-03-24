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

## Naming convention

Plural for collections, singular for single entity:

- `publishers.php` / `publisher.php`
- `publishers.js` / `publisher.js`

This applies to pages, JavaScript files, and API endpoints.

## How the pieces connect

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
