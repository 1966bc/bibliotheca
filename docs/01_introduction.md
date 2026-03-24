# Chapter 00 — Introduction

## What is Bibliotheca?

Bibliotheca is a didactic web application for studying web programming.
Not just learning — studying. Immersing yourself in the art of code.

## Philosophy

Projects begin with pen and paper. Before writing a single line of code,
we define our rules and our domain. This is how real projects are built.

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

## Architecture

Model-View-Controller (MVC), simplified for clarity.

- **Model** — PHP classes that talk to the database and return data.
- **Controller** — PHP scripts that receive requests and respond with JSON.
- **View** — HTML + JavaScript that calls the backend via `fetch`.

## Programming paradigm

Object-Oriented Programming on both sides:

- **PHP** — Classes with clear responsibilities.
- **JavaScript** — ES6 classes with async/await.

## Coding standards

See [CONVENTIONS.md](../CONVENTIONS.md) for the complete reference.

## Next

[Chapter 02 — Database](02_database.md)
