# Chapter 04 — Backend

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

See `src/DBMS.php`.

A thin wrapper around PDO with five methods: `fetchOne`, `fetchAll`,
`insert`, `update`, `delete`. Every method uses prepared statements
to prevent SQL injection.

Key choices in the constructor:

- `ERRMODE_EXCEPTION` — fail fast on errors.
- `FETCH_ASSOC` — access data by column name, never by position.
- `PRAGMA foreign_keys = ON` — SQLite ignores foreign keys by default.

## The Model

See `src/Publisher.php`.

The Model receives the DBMS in its constructor (**dependency injection**)
and provides methods to query one entity. It does not know where the
database lives or how it was opened — it just uses it.

Note: `getAll` filters by `WHERE status = 1`. We never truly delete
rows — we set `status = 0`. This is called **soft delete**.

## The Controller

See `public/api/publishers.php`.

The Controller is the only PHP file the browser can reach. It creates
the DBMS and the Model, reads the HTTP request, calls the Model,
and returns JSON. Nothing else.

## Testing with curl

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
