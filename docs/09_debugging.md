# Chapter 09 — Debugging

## The blank page

The most frustrating thing in web development: your code looks
correct, but the browser shows nothing. No error, no message, just
white. This means PHP crashed before producing any output.

The browser cannot help you. You need to look elsewhere.

## Apache error log

The server-side truth lives here:

```bash
sudo tail -20 /var/log/apache2/error.log
```

This log captures every PHP fatal error, every uncaught exception,
every permission problem. When the browser shows a 500, this log
tells you why.

Common errors you will find:

- `could not find driver` — a PHP extension is missing.
- `attempt to write a readonly database` — permissions (Chapter 08).
- `Uncaught PDOException` — a SQL error, with the exact line number.

## curl — testing the API

Before blaming the frontend, test the backend directly:

```bash
curl http://localhost/bibliotheca/public/api/publishers.php
```

If this returns JSON, the backend works. If it returns nothing or
an error, the problem is server-side.

For POST requests:

```bash
curl -X POST -H "Content-Type: application/json" \
     -d '{"name":"Test"}' \
     http://localhost/bibliotheca/public/api/publishers.php
```

Use `-v` for verbose output — it shows HTTP status codes,
headers, and the full request/response cycle.

## Browser developer tools

Press F12. Three tabs matter:

- **Console** — JavaScript errors appear here. If `fetch` fails
  or your code has a syntax error, this is where you see it.
- **Network** — every HTTP request the browser makes. Click on a
  request to see the status code, request body, and response body.
  If a fetch returns 500, click on it to see the server's error
  message.
- **Elements** — the live HTML. Useful to check if JavaScript
  actually built the DOM you expected.

## PHP from the command line

You can run a PHP file directly to see errors without Apache:

```bash
php /var/www/html/bibliotheca/public/api/publishers.php
```

This bypasses Apache and permissions. If it works here but not
in the browser, the problem is Apache configuration or permissions.

## The golden rule

When something breaks, do not guess. Read the error. Follow the
trail: browser console, network tab, Apache log, PHP command line.
The answer is always there — you just have to look in the right place.
