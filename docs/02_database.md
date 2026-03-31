# Chapter 02 — Database

## Why start here?

The database is the foundation. If the schema is solid, everything
else follows naturally:

Remember MVC?

From tables come Models.
From Models come Controllers.
From Controllers come Views.

In the beginning was the table.

## SQLite

We chose SQLite for its simplicity. No server to install, no users to
configure, no permissions to manage. One file and you are ready.

PDO (PHP Data Objects) works the same way regardless of the database
engine. If one day you need to switch to MySQL or PostgreSQL, you change
the connection string and nothing else.

## The domain

Bibliotheca is a book catalog. So the domain has four entities:

- **Publisher** — who publishes the book (Einaudi, Penguin Books...)
- **Category** — the classification (Fiction, Science, Philosophy...)
- **Author** — who writes the book
- **Book** — the core entity

And their relationships:

- A book has **one** publisher (many-to-one)
- A book has **one** category (many-to-one)
- A book can have **many** authors, an author can have **many** books
  (many-to-many, via the `book_author` junction table)

## Schema conventions

Every table follows the same structure, a convention we impose
on ourselves:

1. First field: primary key (`table_id`)
2. Second field(s): foreign keys (if any)
3. Data fields
4. Last field: `status` (soft delete — 1 active, 0 inactive)

Take note: we always access data **by column name**, never by
position. Say it out loud. You may not fully understand why yet,
but one day you will be grateful for this choice.

## The schema

```sql
publisher (publisher_id, name, status)
category  (category_id, name, status)
author    (author_id, first_name, last_name, birthdate, status)
book      (book_id, publisher_id, category_id, title, pages, published, status)
book_author (book_id, author_id)
```

Five tables. Four entities, one junction table. Simple enough to hold
in your head while reading the code.

## Data types

The schema intentionally covers the fundamental data types:

- **TEXT** — strings (title, name, first_name, last_name)
- **INTEGER** — numbers (pages, status, primary and foreign keys)
- **TEXT as DATE** — dates in ISO 8601 format (published, birthdate)

## Setting up the console

SQLite has a command-line interface. A programmer should always
prefer the command line. It builds character, and keeps you away
from the dark side of the force. We configure it with a
`setconsole` file that sets headers, column mode, and enables
foreign keys:

```
sqlite3 bibliotheca.db -init sql/setconsole
```

## File structure

```
sql/
    setconsole          — SQLite console configuration
    run.sql             — scratch pad for quick queries
    ddl/
        create_table.sql  — the schema (CREATE TABLE statements)
    dml/
        insert.sql        — sample data (INSERT statements)
    dql/
        (queries will go here)
    bibliotheca.db      — the database file
```

- **ddl/** — Data Definition Language (structure)
- **dml/** — Data Manipulation Language (data)
- **dql/** — Data Query Language (queries)

Take your time in this folder. Read the files, chew on them.
It will pay off.

## Next

[Chapter 03 — Project Structure](03_structure.md)
