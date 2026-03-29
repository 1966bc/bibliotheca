# Chapter 05 — Frontend

## The flow

1. The browser requests a URL (e.g., `/publishers`).
2. Apache rewrites it through `.htaccess` to `index.php`.
3. The router loads the right page from `pages/`.
4. The page includes a JavaScript file.
5. JavaScript calls `fetch` to the API — gets JSON back.
6. JavaScript builds the HTML from the data.

The page never fully reloads. The data travels as JSON, the HTML
is built in the browser.

## Routing

See `public/.htaccess` and `public/index.php`.

All URLs go through a single entry point. The router reads the
`route` parameter, checks it against a whitelist, and includes
the matching page. Unknown routes get a 404.

## Pages — plural and singular

Each entity has two pages:

- **Plural** (`publishers.php`) — the list, with Edit and Delete buttons.
- **Singular** (`publisher.php`) — the form, for adding or editing.

One page, one job. Separation of Concerns.

## fetch — the bridge

`fetch` is the native browser API for HTTP requests. Combined with
`async/await`, it reads like sequential code:

```javascript
const response = await fetch('/api/publishers.php');
const publishers = await response.json();
```

Two lines. Request, parse, done. No callbacks, no libraries.

## DOM — building the page

The DOM (Document Object Model) is the browser's internal
representation of the HTML page. When the browser reads your HTML,
it builds a tree of objects — every tag becomes a node, every
attribute a property. JavaScript does not modify the HTML file.
It modifies this tree, and the browser redraws the screen.

We use `document.createElement` to add nodes and `textContent`
to set their text. Never `innerHTML` with external data — that
is an XSS vulnerability.

`textContent` is safe by design: it treats everything as text,
never as HTML.

## Next

[Chapter 06 — CRUD](06_crud.md)
