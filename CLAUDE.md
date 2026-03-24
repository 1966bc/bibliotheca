# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Bibliotheca is a didactic web application (book catalog) built with a pure stack: PHP, JavaScript (ES6), HTML, CSS, SQLite. No frameworks, no build tools, no package managers. Every choice serves clarity and learning.

## Architecture

MVC pattern with a front-controller router:

- **Router**: `public/index.php` — dispatches on `?route=` GET parameter; `.htaccess` rewrites all URLs through it
- **Models**: `src/*.php` — PHP classes (Book, Author, Category, Publisher) that use `src/DBMS.php` (PDO wrapper with `fetchOne`, `fetchAll`, `insert`, `update`, `delete`)
- **API endpoints**: `public/api/*.php` — REST-style JSON controllers (GET/POST/PUT/DELETE); these call models and return JSON with HTTP status codes
- **Views**: `public/pages/*.php` — thin HTML templates that load JS classes from `public/js/*.js`
- **Frontend JS**: ES6 classes in pairs — `*View` (list/table rendering) and `*Form` (add/edit form + validation)

Request flow: `Browser → .htaccess → index.php → pages/*.php → js/*.js → fetch() → api/*.php → src/Model.php → SQLite`

The `src/` directory is outside the document root and not web-accessible. Only `public/` is served by Apache.

## Database

- SQLite file at `sql/bibliotheca.db`; schema in `sql/ddl/create_table.sql`, sample data in `sql/dml/insert.sql`
- 5 tables: `publisher`, `category`, `author`, `book`, `book_author` (many-to-many junction)
- Soft deletes via `status` column (1=active, 0=deleted)
- Foreign keys enforced (`PRAGMA foreign_keys = ON` in DBMS.php)
- Rebuild DB: `cat sql/ddl/create_table.sql sql/dml/insert.sql | sqlite3 sql/bibliotheca.db`

## Commands

Run unit tests (uses in-memory SQLite, no setup needed):

```bash
php tests/run.php
```

No build step, no linter, no package manager. Requires Apache2 with mod_rewrite, PHP, and php-sqlite3.

## Coding Standards (see CONVENTIONS.md)

- **PHP**: PSR-12, `declare(strict_types=1);`, 4-space indent, PascalCase classes, camelCase methods, one class per file
- **JS**: ES6 classes, `'use strict';`, async/await (never `.then()`), semicolons required, 4-space indent
- **SQL**: UPPERCASE keywords, snake_case identifiers, prepared statements with named parameters (`:id`, `:name`)
- **HTML/CSS**: semantic tags, 4-space indent, kebab-case IDs and classes
- **Security**: parameterized queries only (no string interpolation in SQL), `textContent` in JS (never `innerHTML`), `htmlspecialchars()` in PHP, server-side validation is authoritative
- **Git**: imperative mood, English, one logical change per commit

## Development

Served from `/var/www/html/bibliotheca/public/` as Apache document root. The `.htaccess` handles URL rewriting.

Adding a new entity requires touching all layers: model (`src/`), API endpoint (`public/api/`), page templates (`public/pages/` — list and detail), JS classes (`public/js/` — view and form pair), and a route entry in `public/index.php`'s `$allowed` array.

## Lessons

Ten lessons in `docs/` (00–09) covering database, structure, backend, frontend, CRUD, validation, permissions, and debugging.
