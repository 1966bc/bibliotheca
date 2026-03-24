# Bibliotheca — Project Conventions

## Philosophy

This is a didactic project. Every choice serves clarity and learning.
We follow the Böhm-Jacopini theorem: sequence, selection (if/else),
and iteration (for/while). No magic methods, no voodoo programming.
The code does what it says, and says what it does.

## Principles

- **KISS** (Keep It Simple, Stupid) — Always choose the simplest solution
  that works. Complexity is the enemy of understanding.
- **DRY** (Don't Repeat Yourself) — Every piece of knowledge should have
  a single, unambiguous representation in the system.
- **YAGNI** (You Aren't Gonna Need It) — Don't build what you don't need
  yet. Solve today's problem today.
- **SoC** (Separation of Concerns) — Each part of the code has one job.
  Backend serves data, frontend presents it. Model queries, Controller
  routes, View displays.
- **Least Surprise** — Code should behave as the reader expects.
  No hidden side effects, no clever tricks.
- **Fail Fast** — If something goes wrong, stop immediately. Never drag
  an error through the code hoping it will resolve itself.
- **Fail Safe** — When you fail, fail securely. Never leave the system
  in an inconsistent or unsafe state.

## Stack

| Layer    | Technology      | Notes                          |
|----------|-----------------|--------------------------------|
| Backend  | PHP (pure)      | No frameworks (Laravel, etc.)  |
| Frontend | JavaScript (pure) | No frameworks (React, Vue, etc.) |
| Markup   | HTML (pure)     | No template engines            |
| Style    | CSS (pure)      | No preprocessors (Sass, etc.)  |
| Database | SQLite          | Via PDO                        |

## Architecture

Model-View-Controller (MVC), simplified for clarity.

- **Model** — PHP classes that talk to the database via PDO and return data.
- **Controller** — PHP scripts that receive HTTP requests, call the Model,
  and respond with JSON.
- **View** — HTML pages with JavaScript classes that call Controllers
  via `fetch` and build the interface.

## Programming Paradigm

- **PHP** — Object-Oriented Programming (classes with clear responsibilities).
- **JavaScript** — Object-Oriented Programming (ES6 classes, async/await).

## Coding Standards

### PHP (based on PSR-12)

- Indentation: 4 spaces (no tabs).
- Classes: `PascalCase` (e.g., `Book`, `Category`, `BookController`).
- Methods and variables: `camelCase` (e.g., `getAll`, `findById`, `bookTitle`).
- Constants: `UPPER_SNAKE_CASE` (e.g., `DB_PATH`, `MAX_RESULTS`).
- Opening brace on the same line for control structures.
- Opening brace on the next line for classes and methods.
- One class per file.
- `<?php` opening tag only, never `<?`.
- `declare(strict_types=1);` at the top of every file.

### JavaScript (based on MDN conventions)

- Indentation: 4 spaces (no tabs).
- Classes: `PascalCase` (e.g., `BookView`, `CategoryView`).
- Methods and variables: `camelCase` (e.g., `loadBooks`, `currentPage`).
- Constants: `UPPER_SNAKE_CASE` (e.g., `API_URL`, `MAX_ITEMS`).
- Always `'use strict';` at the top.
- Always `const` or `let`, never `var`.
- Always `async/await` with `fetch`, never raw `.then()` chains.
- Semicolons: always.

### SQL

- Keywords: `UPPERCASE` (e.g., `SELECT`, `FROM`, `WHERE`, `INSERT INTO`).
- Table names: `snake_case`, singular (e.g., `book`, `author`, `book_author`).
- Column names: `snake_case` (e.g., `first_name`, `published_date`).
- Each clause on its own line for readability.
- Always use prepared statements with named parameters (`:id`, `:title`).

### HTML

- Indentation: 4 spaces.
- Attributes: double quotes (e.g., `class="main"`).
- IDs and classes: `kebab-case` (e.g., `book-list`, `main-header`).
- Semantic tags whenever possible (`header`, `main`, `section`, `article`).

### CSS

- Indentation: 4 spaces.
- Class names: `kebab-case` (e.g., `.book-card`, `.nav-link`).
- One property per line.
- Logical grouping of properties (layout, typography, color, etc.).

## File Naming

- All file names: `snake_case`, lowercase.
- Prefer single words when possible.
- Plural for collections, singular for single entity:
  - `books.php` / `book.php`
  - `categories.js` / `category.js`
- PHP class files match the class name: `Book.php`, `CategoryController.php`.

## Language

- **Code** (variables, methods, classes, comments): English only.
- Comments explain **why**, not how. The code itself should make
  the *how* self-evident.

## Encoding

- UTF-8 everywhere: files, database, HTTP headers, HTML meta tags.
- No BOM.

## Security (non-negotiable)

These are rules, not features. They apply from day one.

- **SQL injection** — Always use PDO prepared statements. Never concatenate
  user input into queries.
- **XSS** — Always escape output with `htmlspecialchars()` in PHP
  or `textContent` in JavaScript. Never use `innerHTML` with user data.
- **Input validation** — Validate and sanitize all user input on the server
  side. Client-side validation is for UX only, never for security.
- **CSRF** — Protect state-changing operations with tokens.

## Git

- Commit messages: English, imperative mood, concise.
  - Good: `Add book model`, `Fix pagination query`, `Update category view`
  - Bad: `added stuff`, `fix`, `WIP`
- One logical change per commit.
- Branch names: `kebab-case` (e.g., `add-book-model`, `fix-search`).
