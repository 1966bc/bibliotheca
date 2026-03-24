# Glossary

Terms you will encounter in this book, in alphabetical order.

---

**API (Application Programming Interface)** —
A set of endpoints that a program can call to exchange data. In
Bibliotheca, the files in `public/api/` are the API: JavaScript
sends HTTP requests, PHP answers with JSON.

**async/await** —
JavaScript syntax that makes asynchronous code read like sequential
code. `await fetch(url)` pauses execution until the response
arrives, without blocking the browser.

**Böhm-Jacopini theorem** —
Any computable function can be written using only three control
structures: sequence, selection (if/else), and iteration
(for/while). No goto, no magic.

**CRUD** —
Create, Read, Update, Delete — the four basic operations on data.
Every entity in Bibliotheca implements all four through a chain of
HTTP method → Controller → Model → SQL.

| Operation | HTTP   | SQL      |
|-----------|--------|----------|
| Create    | POST   | INSERT   |
| Read      | GET    | SELECT   |
| Update    | PUT    | UPDATE   |
| Delete    | DELETE | DELETE   |

**Controller** —
The PHP script in `public/api/` that receives an HTTP request,
calls the Model, and returns JSON. It decides *what to do* but
does not know *how to query* or *how to display*.

**DDL / DML / DQL** —
Three categories of SQL:
- **DDL** (Data Definition Language) — `CREATE TABLE`, `ALTER TABLE`
- **DML** (Data Manipulation Language) — `INSERT`, `UPDATE`, `DELETE`
- **DQL** (Data Query Language) — `SELECT`

In Bibliotheca, each has its own directory under `sql/`.

**Dependency injection** —
Passing an object to a class instead of letting the class create it
internally. The Model receives the DBMS in its constructor — it
does not know how the connection was opened, only that it can use
it. This makes the code testable and flexible.

**Document root** —
The directory that Apache serves to browsers. In Bibliotheca, this
is `public/`. Files outside it (`src/`, `sql/`) are invisible to
the web — only PHP can reach them.

**DOM (Document Object Model)** —
The browser's in-memory representation of the HTML page. JavaScript
manipulates the DOM to build tables, add rows, show errors — all
without reloading the page.

**DRY (Don't Repeat Yourself)** —
Every piece of knowledge should have a single representation.
If you find yourself copying the same code, extract it.

**ES6** —
The 2015 edition of JavaScript that introduced classes, `const`/`let`,
arrow functions, template literals, and `async/await`. Bibliotheca
uses ES6 classes on the frontend.

**Fail Fast** —
If something goes wrong, stop immediately. Do not drag an error
through the code hoping it will resolve itself. In DBMS.php,
`PDO::ERRMODE_EXCEPTION` enforces this: a bad query throws an
exception instead of returning false.

**Fail Safe** —
When you fail, fail securely. Never leave the system in an
inconsistent or unsafe state.

**`fetch`** —
The browser's native API for HTTP requests. Replaces the older
`XMLHttpRequest`. Combined with `async/await`, it is how JavaScript
talks to the backend.

**Foreign key** —
A column that references the primary key of another table. The
`publisher_id` column in `book` points to `publisher.publisher_id`.
SQLite ignores foreign keys by default — `PRAGMA foreign_keys = ON`
enables enforcement.

**Front controller** —
A design pattern where every request enters the application through
a single file. In Bibliotheca, `index.php` is the front controller:
`.htaccess` routes all URLs to it, and it decides which page to
load.

**HTTP methods** —
The verbs of the web: GET (read), POST (create), PUT (update),
DELETE (remove). Each API endpoint in Bibliotheca uses the method
to decide which operation to perform.

**HTTP status codes** —
Numbers that tell the client what happened. The ones Bibliotheca
uses:
- **200** — OK
- **400** — Bad Request (missing or invalid input)
- **404** — Not Found
- **405** — Method Not Allowed
- **409** — Conflict (duplicate record)
- **500** — Internal Server Error

**`innerHTML` (avoid)** —
A DOM property that parses a string as HTML. If the string contains
user input, this is an XSS vulnerability. Bibliotheca uses
`textContent` instead, which treats everything as plain text.

**JSON (JavaScript Object Notation)** —
A lightweight data format. The API returns JSON, JavaScript parses
it with `response.json()`. It looks like this:
`{"name": "Einaudi", "publisher_id": 2}`.

**Junction table** —
A table that implements a many-to-many relationship. `book_author`
connects books to authors: one book can have many authors, one
author can have many books.

**kebab-case** —
Words separated by hyphens: `book-list`, `main-header`. Used for
HTML IDs, CSS classes, and git branch names.

**KISS (Keep It Simple, Stupid)** —
Always choose the simplest solution that works. Complexity is the
enemy of understanding.

**Model** —
A PHP class in `src/` that knows how to query one entity. It
receives the DBMS via dependency injection and provides methods
like `getAll`, `getById`, `insert`, `update`, `delete`.

**MVC (Model-View-Controller)** —
An architecture that separates data (Model), presentation (View),
and logic (Controller). In Bibliotheca:
- **Model** — `src/*.php`
- **View** — `public/pages/*.php` + `public/js/*.js`
- **Controller** — `public/api/*.php`

**PDO (PHP Data Objects)** —
PHP's database abstraction layer. It works the same way regardless
of the database engine (SQLite, MySQL, PostgreSQL). Switch database
by changing the connection string, nothing else.

**Prepared statement** —
A SQL query with placeholders instead of values. The database engine
separates the query from the data, making SQL injection impossible.
Every query in Bibliotheca uses them.

PDO supports two styles of placeholders:
- **Named** — `:id`, `:name`. Self-documenting, order-independent.
- **Positional** — `?`. Shorter, but you must match the order.

We use named parameters because they are easier to read and safer
to refactor. `WHERE publisher_id = :id AND status = :status` tells
you what each value is. `WHERE publisher_id = ? AND status = ?`
requires counting positions.

**Primary key** —
A column that uniquely identifies each row. In Bibliotheca, it is
always the first column: `publisher_id`, `book_id`, etc.

**PSR-12** —
A PHP coding standard that defines formatting rules: indentation,
brace placement, naming. Bibliotheca follows it.

**Rewrite rule** —
An Apache directive in `.htaccess` that transforms URLs before they
reach PHP. The rule `^(.*)$ index.php?route=$1` captures the URL
path and passes it as a parameter to the router.

**REST (Representational State Transfer)** —
An architectural style for web APIs, defined by Roy Fielding in 2000.
The idea is simple: use what HTTP already gives you.
- **Resources have URLs.** Each entity lives at its own address:
  `/api/books.php`, `/api/publishers.php`.
- **Verbs come from HTTP.** The method *is* the action: GET reads,
  POST creates, PUT updates, DELETE removes. No need for
  `?action=delete` in the URL.
- **Responses are representations.** The server returns data (JSON),
  not pages. The client decides how to display it.
- **Each request is self-contained.** The server does not remember
  previous requests. Every call carries all the information it needs.

Before REST, web services used complex protocols like SOAP — XML-heavy,
with action names buried inside the message body. REST replaced all of
that with what the web already had: URLs for nouns, HTTP methods for
verbs, and JSON for data. Bibliotheca follows this style.

**SoC (Separation of Concerns)** —
Each part of the code has one job. Backend serves data, frontend
presents it. Model queries, Controller routes, View displays.

**Soft delete vs hard delete** —
Two strategies for removing data. Soft delete sets a flag
(`status = 0`) — the record stays in the database but appears
disabled. Hard delete runs `DELETE FROM` — the record is gone.
Bibliotheca uses both: the status toggle disables a record
(greyed out in the list), while the Delete button removes it
permanently.

**SQL injection** —
An attack where user input is inserted into a SQL query, changing
its meaning. Example: entering `'; DROP TABLE book; --` as a name.
Prepared statements prevent this completely.

**SQLite** —
A database engine stored in a single file. No server process, no
configuration. The file `sql/bibliotheca.db` is the entire database.

**`strict_types`** —
`declare(strict_types=1);` at the top of a PHP file enables strict
type checking. Passing a string where an int is expected throws a
TypeError instead of silently converting.

**`textContent`** —
A DOM property that sets or gets the text of an element. Unlike
`innerHTML`, it never interprets HTML — safe by design.

**URL rewriting** —
Transforming `/publishers` into `index.php?route=publishers` so
the user sees clean URLs while the server uses query parameters.
Done by Apache's `mod_rewrite` via `.htaccess`.

**Whitelist** —
A list of explicitly allowed values. The router in `index.php`
checks the route against a whitelist of valid pages. Anything not
on the list gets a 404. Safer than a blacklist (blocking known
bad values) because unknown values are rejected by default.

**XSS (Cross-Site Scripting)** —
An attack where malicious JavaScript is injected into a page.
Prevented by escaping output: `htmlspecialchars()` in PHP,
`textContent` in JavaScript. Never render user input as HTML.

**YAGNI (You Aren't Gonna Need It)** —
Don't build what you don't need yet. Solve today's problem today.
