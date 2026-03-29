# Chapter 00 — Introduction

## What is Bibliotheca?

Bibliotheca is a didactic web application for studying web programming.
Not just learning — studying. Dive into the art of code.

## Philosophy

Projects begin with pen and paper. Before writing a single line of code,
we define our rules and our domain. This is how real projects are built.

As you read, write down what you understand in your own words.
Reading and rewriting are two different cognitive processes — one
is passive, the other forces you to organize your thoughts. You
will be surprised by the gap between "I understood it" and "I can
explain it." That gap is where learning happens. Otherwise you end
up like Augustine of Hippo: *"If no one asks me, I know; if I wish
to explain it to one who asks, I do not know."*

## Principles

- **Böhm-Jacopini theorem** — Sequence, selection (if/else), iteration
  (for/while). No magic, no tricks.
- **KISS** — Keep It Simple, Stupid. Always the simplest solution that works.
- **DRY** — Don't Repeat Yourself.
- **YAGNI** — You Aren't Gonna Need It. Don't build what you don't need yet.
- **SoC** — Separation of Concerns. Each part of the code has one job.
- **Least Surprise** — Code should behave as the reader expects.
- **Fail Fast** — If something goes wrong, stop immediately.
- **Fail Safe** — When you fail, fail securely.

## Stack

| Layer    | Technology         | Notes                    |
|----------|--------------------|--------------------------|
| Backend  | PHP (pure)         | No frameworks            |
| Frontend | JavaScript (pure)  | No frameworks            |
| Markup   | HTML (pure)        | No template engines      |
| Style    | CSS (pure)         | No preprocessors         |
| Database | SQLite             | Via PDO                  |

Pure languages only. We use nothing but the native capabilities of each
language. This way you understand what happens under the hood.

Each of these technologies has extensive documentation online. Study
them separately — the official docs for PHP, MDN for JavaScript and
HTML/CSS, the SQLite documentation. This book shows how the pieces
fit together.

## The body of a web application

Let us start with an analogy. Before reading about architecture,
look at your own body.

**The skeleton (HTML)** — Strip everything away: skin, clothes, muscles.
What remains is structure. A skull, a spine, ribs. HTML is this: it says
*what exists* — a heading, a table, a form, a navigation menu. A website
with no CSS and no JavaScript still works. Try it: disable both in your
browser. The page is ugly, naked, but readable. That is the skeleton test.

**The skin (CSS)** — Now dress the skeleton. Skin gives visible shape to
bones. Clothes add style, color, identity. CSS does exactly this. But if
you change clothes, the bones underneath do not change.

**The nervous system (JavaScript)** — Bones and skin alone are a statue.
Beautiful, but still. The nervous system carries signals: from senses to
brain, from brain to muscles. JavaScript moves data: it takes the user's
click on "Save", transmits it to the server, receives the response, and
updates the skeleton. It does not decide *what* to show or *how* it looks.
It connects and transports.

**The internal organs (PHP + Database)** — The heart pumps, the liver
filters, the kidneys purify. You cannot see them, but without them the
body dies. The PHP backend and the SQLite database receive requests,
validate data, save it, retrieve it, protect its integrity. The user
does not know they exist — and that is how it should be.

When layers mix — HTML inside JavaScript, SQL inside HTML — it is like
a body with the liver attached to the knee. Does it work? Maybe.
Is it healthy? Never.

## Architecture

Model-View-Controller (MVC), simplified for clarity. Now you can map
the body metaphor to the technical terms:

- **Model** — The organs. PHP classes that talk to the database and return data.
- **Controller** — The nervous system. PHP scripts (API) that receive requests and respond with JSON, plus JavaScript that carries data to the interface.
- **View** — The skeleton and skin. HTML structure + CSS appearance.

## Programming paradigm

Object-Oriented Programming on both sides:

- **PHP** — Classes with clear responsibilities.
- **JavaScript** — ES6 classes with async/await.

We will not teach OOP here — that is a prerequisite. If you do not
know what a class, a method, or an instance is, study that first.
Any language will do: the concepts are the same everywhere. What
matters here is *how* we apply OOP to structure a web application.
You will see it in practice, and that may be the best way to
understand it.

## Coding standards

We follow consistent conventions throughout the project:

- **Indentation**: 4 spaces everywhere (PHP, JavaScript, HTML, CSS, SQL). No tabs.
- **Classes**: `PascalCase` — `Book`, `PublishersView`, `CategoryForm`.
- **Methods and variables**: `camelCase` — `getAll`, `findById`, `bookTitle`.
- **Constants**: `UPPER_SNAKE_CASE` — `DB_PATH`, `API_URL`.
- **Files**: `snake_case`, lowercase. Plural for collections (`publishers.php`),
  singular for single entity (`publisher.php`). PHP class files match
  the class name (`Book.php`).
- **SQL**: keywords `UPPERCASE`, tables and columns `snake_case`.
  Always prepared statements with named parameters (`:id`, `:title`).
- **PHP**: `declare(strict_types=1)` at the top of every file.
  Opening brace on the same line for control structures,
  next line for classes and methods.
- **JavaScript**: `'use strict'` at the top. Always `const` or `let`,
  never `var`. Always `async/await`, never `.then()` chains.
  Semicolons required.
- **HTML**: double quotes for attributes, `kebab-case` for IDs
  and classes, semantic tags.
- **Comments**: English only. Explain *why*, not *how*.

## Next

[Chapter 02 — Database](02_database.md)
