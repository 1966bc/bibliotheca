# Ten Brief Lessons on Web Programming

*A concept notebook on web programming*

> *To the next generation of programmers:
> learn to read, write and compute — before it's too late.*

## The book

**[Download the PDF](forge/book/ten_brief_lessons.pdf)** — free, open source, ready to print.

A complete guide to building web applications from scratch with pure
PHP, JavaScript, HTML, CSS and SQLite. No frameworks, no libraries,
no magic. Just code.

## Bibliotheca

The book is built around Bibliotheca, a book catalog application.
Simple enough to hold in your head, rich enough to teach you the
fundamentals of web development in anno Domini 2026.

Clone the repo, follow the lessons, read the code.

![Bibliotheca](screenshot.png)

## Quick start

```bash
sudo apt install apache2 php libapache2-mod-php php-sqlite3
git clone https://github.com/1966bc/bibliotheca.git /var/www/html/bibliotheca
cd /var/www/html/bibliotheca/sql
sudo chgrp www-data . bibliotheca.db
sudo chmod 775 .
sudo chmod 664 bibliotheca.db
```

Open `http://localhost/bibliotheca/public/` in your browser.

## The lessons

| #  | Chapter                                          |
|----|--------------------------------------------------|
| 00 | [Prelude](docs/00_prelude.md)                    |
| 01 | [Introduction](docs/01_introduction.md)          |
| 02 | [Database](docs/02_database.md)                  |
| 03 | [Project Structure](docs/03_structure.md)        |
| 04 | [Backend](docs/04_backend.md)                    |
| 05 | [Frontend](docs/05_frontend.md)                  |
| 06 | [CRUD](docs/06_crud.md)                          |
| 07 | [Validation](docs/07_validation.md)              |
| 08 | [Permissions](docs/08_permissions.md)            |
| 09 | [Debugging](docs/09_debugging.md)                |
| 10 | [Security](docs/10_security.md)                  |

## Appendices

| Topic                                              |
|------------------------------------------------------|
| [How It Works](docs/how_it_works.md)                 |
| [Study Notebook](docs/notebook.md)                   |
| [Glossary](docs/glossary.md)                         |
| [The Apocryphal Chapter](docs/apocrypha.md)          |

## Stack

| Layer    | Technology         |
|----------|--------------------|
| Backend  | PHP (pure)         |
| Frontend | JavaScript (pure)  |
| Markup   | HTML (pure)        |
| Style    | CSS (pure)         |
| Database | SQLite via PDO     |

## Conventions

See [CONVENTIONS.md](CONVENTIONS.md) for coding standards and
project rules.

## License

[GPL-3.0](LICENSE)
