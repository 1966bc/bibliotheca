# Chapter 04 — Backend

In our architecture, PHP has one job: talk to the database and
return data. It lives outside `public/`, invisible to the browser.
It receives requests, processes them, and answers with JSON. The
internal organs, remember?

Most PHP books and tutorials embed PHP directly inside HTML. Do
not let that mislead you. PHP's real job is the backend: validate,
query, respond. For everything the user sees and interacts with, there is
JavaScript. This decoupling is fundamental. The sooner you
internalize it, the better.

## Prerequisites

PHP needs the SQLite driver. Check if it is installed:

```bash
php -m | grep -i sqlite
```

If empty, install it and restart Apache:

```bash
sudo apt install php-sqlite3
sudo systemctl restart apache2
```

## DBMS — the database class

Open `src/DBMS.php` and read it. This is the only class that
talks to the database. Every query, every insert, every
transaction passes through it. It is the first file you should
study.

Most tutorials have you open a connection, write the query, and
fetch results on every single page. That is not how it works in
real projects. We write it once, in one place. Everyone else
uses it. That is DRY in action.

Naturally, it is a class we wrote ourselves. A thin wrapper
around PDO.
It exposes two ways to execute SQL, matching what PDO itself
offers:

- **`query()`** — for fixed SQL with no parameters.
  Example: `SELECT * FROM publisher ORDER BY name`.
- **`fetchOne()` / `fetchAll()`** — for SQL with named parameters,
  using prepared statements. Example: `WHERE publisher_id = :id`.

And convenience methods for write operations (all via prepare):
`insert()`, `update()`, `delete()`.

It also provides `exec()` for statements that return no rows
(DDL, PRAGMA), and `beginTransaction()` / `commit()` / `rollBack()`
for atomic operations — when two or more statements must succeed
together or fail together.

As a safety net, `query()` and `exec()` reject SQL that contains
named parameters (`:name`). If you need parameters, use prepared
statements — no exceptions.

Key choices in the constructor:

- `ERRMODE_EXCEPTION` — fail fast on errors.
- `FETCH_ASSOC` — access data by column name, never by position.
- `PRAGMA foreign_keys = ON` — SQLite ignores foreign keys by default.

## PHP in the MVC

Remember MVC from Chapter 00? PHP lives in the Model (`src/`)
and in the Controller (`public/api/`). JavaScript and HTML/CSS
handle the rest.

## The Model

Open `src/Publisher.php`. The Model receives the DBMS in its
constructor (**dependency injection**) and provides methods to
query one entity. It does not know where the database lives or
how it was opened. It just uses it.

The `status` column (1 = active, 0 = disabled) controls
visibility. `getAll` returns everything; `getActive` filters
by `status = 1`. Disabled records appear greyed out in the list.
Deletion is permanent (`DELETE FROM`).

## The Controller

Open `public/api/publishers.php`.

The Controller is the only PHP file the browser can reach. It creates
the DBMS and the Model, reads the HTTP request, calls the Model,
and returns JSON. Nothing else.

## Testing with curl

PHP runs on the server. Unlike JavaScript, it does not need a
browser. Keep these two worlds separate in your mind. As you
grow, you will notice subtle exceptions, but for now: PHP is the
server, JavaScript is the browser. We can prove it right now
from the command line:

```bash
curl http://localhost/bibliotheca/public/api/publishers.php
```

```json
[{"publisher_id":7,"name":"Addison-Wesley","status":1},
 {"publisher_id":1,"name":"Adelphi","status":1},
 {"publisher_id":2,"name":"Einaudi","status":1},
 {"publisher_id":3,"name":"Feltrinelli","status":1},
 {"publisher_id":8,"name":"Gallimard","status":1},
 {"publisher_id":5,"name":"Laterza","status":1},
 {"publisher_id":4,"name":"Mondadori","status":1},
 {"publisher_id":6,"name":"Penguin Books","status":1}]
```

Eight publishers, ordered by name. The backend works.

## Next

[Chapter 05 — Frontend](05_frontend.md)
