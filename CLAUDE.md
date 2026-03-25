# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Bibliotheca is a didactic web application (book catalog) built with a pure stack: PHP, JavaScript (ES6), HTML, CSS, SQLite. No frameworks, no build tools, no package managers. Every choice serves clarity and learning.

## Architecture

MVC pattern with a front-controller router:

- **Router**: `public/index.php` — dispatches on `?route=` GET parameter; `.htaccess` rewrites all URLs through it
- **Models**: `src/*.php` — PHP classes (Book, Author, Category, Publisher) that use `src/DBMS.php` (PDO wrapper: `query` for fixed SQL, `fetchOne`/`fetchAll` for parameterized reads, `insert`/`update`/`delete` for writes, `exec` for DDL, `beginTransaction`/`commit`/`rollBack` for atomic operations)
- **API endpoints**: `public/api/*.php` — REST-style JSON controllers that dispatch on `$_SERVER['REQUEST_METHOD']` (GET/POST/PUT/DELETE), return JSON with HTTP status codes (200, 400, 404, 405, 409). Each file requires models from `src/` via `require_once __DIR__ . '/../../src/...'`
- **Views**: `public/pages/*.php` — thin HTML templates that load JS classes from `public/js/*.js`
- **Frontend JS**: ES6 classes in pairs — plural file (e.g. `books.js`) contains the list/table `*View` class, singular file (e.g. `book.js`) contains the `*Form` class for add/edit + validation

Request flow: `Browser → .htaccess → index.php → pages/*.php → js/*.js → fetch() → api/*.php → src/Model.php → SQLite`

The `src/` directory is outside the document root and not web-accessible. Only `public/` is served by Apache.

## Database

- SQLite file at `sql/bibliotheca.db`; schema in `sql/ddl/create_table.sql`, sample data in `sql/dml/insert.sql`
- 5 tables: `publisher`, `category`, `author`, `book`, `book_author` (many-to-many junction)
- `status` column (1=active, 0=disabled) for toggling visibility; deletes are hard (`DELETE FROM`)
- Foreign keys enforced (`PRAGMA foreign_keys = ON` in DBMS.php)
- Indexes on foreign key columns (`idx_book_publisher`, `idx_book_category`, `idx_book_author_book`, `idx_book_author_author`)
- Rebuild DB:
  ```bash
  rm sql/bibliotheca.db && cat sql/ddl/create_table.sql sql/dml/insert.sql | sqlite3 sql/bibliotheca.db
  sudo chgrp www-data sql/ sql/bibliotheca.db && sudo chmod 775 sql/ && sudo chmod 664 sql/bibliotheca.db
  ```

## Commands

Run unit tests (uses in-memory SQLite, no setup needed):

```bash
php tests/run.php
```

No single-test runner — all tests run together via `tests/run.php` using the custom `TestRunner` class. To add a test, append a `$t->test('name', function () use ($t) { ... })` block in `tests/run.php`.

No build step, no linter, no package manager. Requires Apache2 with mod_rewrite, PHP, and php-sqlite3.

## Coding Standards (see CONVENTIONS.md)

- **PHP**: PSR-12, `declare(strict_types=1);`, 4-space indent, PascalCase classes, camelCase methods, one class per file
- **JS**: ES6 classes, `'use strict';`, async/await (never `.then()`), semicolons required, 4-space indent
- **SQL**: UPPERCASE keywords, snake_case identifiers, prepared statements with named parameters (`:id`, `:name`)
- **HTML/CSS**: semantic tags, 4-space indent, kebab-case IDs and classes
- **Security**: parameterized queries only (no string interpolation in SQL), `textContent` in JS (never `innerHTML`), `htmlspecialchars()` in PHP, server-side validation is authoritative, all string inputs sanitized with `strip_tags()` + `trim()` + `mb_substr()` in API endpoints
- **DBMS discipline**: `query()`/`exec()` reject SQL containing named parameters (`:name`) — forces use of prepared statements for any parameterized query
- **Git**: imperative mood, English, one logical change per commit

## URLs

Base URL: `http://localhost/bibliotheca/public/`

`.htaccess` rewrites clean URLs to `index.php?route=`. Existing files (CSS, JS, API) are served directly.

- **List pages**: `/bibliotheca/public/publishers` → `index.php?route=publishers` → `pages/publishers.php`
- **Detail/edit pages**: `/bibliotheca/public/publisher?id=3` → `pages/publisher.php` (JS reads `id` from query string); without `?id=` it becomes an "Add new" form
- **API**: `/bibliotheca/public/api/publishers.php` — real file, no rewrite; JS calls it via `fetch()`
- **Home**: `/bibliotheca/public/` shows the book list (same content as `/books`); books have no nav link — they're the landing page
- **Pattern for all entities**: list at `/entities`, add at `/entity`, edit at `/entity?id=N`

Routes must be in the `$allowed` array in `public/index.php` or they return 404.

## Development

Adding a new entity requires touching all layers: model (`src/`), API endpoint (`public/api/`), page templates (`public/pages/` — list and detail), JS classes (`public/js/` — view and form pair), and a route entry in `public/index.php`'s `$allowed` array.

## Lessons

Ten lessons in `docs/` (00–09) covering database, structure, backend, frontend, CRUD, validation, permissions, and debugging. Plus appendices: How It Works, Glossary, and The Apocryphal Chapter (what comes next).
