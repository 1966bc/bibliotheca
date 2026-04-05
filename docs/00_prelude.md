# Prelude

## What is this

The best way to learn something is by imitation. Study a real
application, understand how it works, then build your own.

Bibliotheca is a complete web application — a book catalog.
Simple enough to hold in your head, rich enough to teach you
the fundamentals of web development.

The entire source code is on GitHub:
https://github.com/1966bc/bibliotheca

Read it. You will read more code than you write in your career —
and reading good code is how you learn to write it. Reading *why*
it is written that way is even better.

## Who is this for?

For the young programmer's mind — or the curious one at any age —
open to the wonder of understanding how things actually work.
A web application, in anno Domini 2026. From the database to
the browser, with nothing in between but your own code.

You do not need experience with web development, but you should
know the basics of programming: variables, functions, loops,
conditionals. You should also have some intuition of how a
website works: that a browser asks a server for something, and
the server answers. And above all, the hacker's instinct: the
urge to open things up and see how they work.

## How to read this

Read in order and try to absorb the key concepts. The lessons are
brief but there is a lot to take in. Look at the application as
you go — piece by piece, the puzzle will come to life.

## What you will need

- A **Linux** machine (we use Debian 12 Bookworm, but any distribution works)
- A **LAMP** stack: Linux, Apache, MySQL/MariaDB, PHP — even though
  we use SQLite instead of MySQL, Apache and PHP are essential.
  Install them with:

```bash
sudo apt install apache2 php libapache2-mod-php php-sqlite3
```

- A **text editor** — we recommend [Sublime Text](https://www.sublimetext.com/),
  fast and lightweight. You do not need a heavy IDE to write good code.
- A **terminal**
- A **web browser**
- **Curiosity**

One note about the terminal. Throughout this book, we will use
the command line for everything we can: creating files, running
queries, testing the API, checking permissions, managing git.
This is deliberate. The terminal is not a fallback for when the
GUI is missing. It is the primary tool of a programmer. Learn
to think in commands.

Let the show begin.

[Chapter 00 — Introduction](01_introduction.md)
