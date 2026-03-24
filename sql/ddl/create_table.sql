-- Bibliotheca — Database Schema
-- SQLite via PDO

CREATE TABLE IF NOT EXISTS publisher (
    publisher_id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    status INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS category (
    category_id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    status INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS author (
    author_id INTEGER PRIMARY KEY,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    birthdate TEXT,
    status INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS book (
    book_id INTEGER PRIMARY KEY,
    publisher_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    pages INTEGER,
    published INTEGER,
    status INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (publisher_id) REFERENCES publisher (publisher_id),
    FOREIGN KEY (category_id) REFERENCES category (category_id)
);

CREATE TABLE IF NOT EXISTS book_author (
    book_id INTEGER NOT NULL,
    author_id INTEGER NOT NULL,
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id) REFERENCES book (book_id),
    FOREIGN KEY (author_id) REFERENCES author (author_id)
);
