# Chapter 05 — Frontend

## The flow

1. The browser requests a URL (e.g., `/publishers`).
2. Apache rewrites it through `.htaccess` to `index.php`.
3. The router loads the right page from `pages/`.
4. The page includes a JavaScript file.
5. JavaScript calls `fetch` to the API, gets JSON back.
6. JavaScript builds the HTML from the data.

The HTML page loads once. After that, all data exchange happens
through `fetch` calls that return JSON. The browser never asks
the server for a new page.

## Routing

All URLs pass through a single entry point: `index.php`. Apache
makes this possible with one rewrite rule in `.htaccess`:

```
RewriteRule ^(.+)$ index.php?route=$1 [QSA,L]
```

Every request that does not match an existing file gets rewritten.
The URL `/publishers` becomes `index.php?route=publishers`.

Inside `index.php`, the route is checked against a whitelist:

```php
$routes = ['home', 'login', 'publishers', 'publisher',
           'categories', 'category', 'authors', 'author',
           'book', 'about'];
```

If the route is in the list and the file exists, the
corresponding page is loaded. Otherwise, the user gets a 404.
No user input ever becomes a file path directly.

## Pages: plural and singular

In our project, each entity has two pages, by convention:

- **Plural** (`publishers.php`): the list, with Edit and
  Delete buttons shown if the user is logged in.
- **Singular** (`publisher.php`): the form, for adding
  or editing.

One page, one job. Separation of Concerns.

## fetch: the bridge

`fetch` is the browser's native API for HTTP requests. It
replaced `XMLHttpRequest`, the old AJAX interface that powered
the first generation of dynamic web applications. The idea is
the same: JavaScript talks to the server without reloading the
page. The API is simpler.

`fetch` is natively asynchronous: the browser sends the request and
continues executing code without waiting for the response. This
is necessary because network calls are slow compared to
everything else, and blocking the browser would freeze the page.

The `async/await` syntax lets us write asynchronous code that
reads like sequential code. Without it, we would need callbacks
or promise chains. With it, each line waits for the previous
one to finish, exactly as you would expect.

Reading data:

```javascript
async load() {
    const response = await fetch('/api/publishers.php');
    const publishers = await response.json();
}
```

Three lines. Request, parse, done. No callbacks, no libraries.
The `async` keyword on the method is required: `await` only
works inside an `async` function.

Sending data requires a bit more: the HTTP method, headers, and
a JSON body.

```javascript
const payload = { name: 'Adelphi' };

const response = await fetch('/api/publishers.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
    },
    body: JSON.stringify(payload)
});
```

The payload is the data we want to send. `JSON.stringify`
converts the JavaScript object into a JSON string, because
HTTP only carries text. The CSRF token travels in the header.
The data travels in the body. The server reads both before
doing anything.

## DOM: building the page

The DOM (Document Object Model) is the browser's internal
representation of the HTML. Every tag becomes a node, every
attribute a property. JavaScript does not modify the HTML file.
It modifies this tree, and the browser redraws the screen.

Take this HTML from our publishers page:

```html
<table id="publisher-table">
    <thead>
        <tr>
            <th>Name</th>
            <th></th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
```

The browser reads it and builds the DOM, a tree of objects:

```
table #publisher-table
├── thead
│   └── tr
│       ├── th  "Name"
│       └── th
└── tbody  (empty)
```

JavaScript actually sees this tree, not the HTML text.

This is a crucial point. The `id` on the table and the empty
`tbody` are placeholders. They tell JavaScript *where* to
inject the data retrieved by `fetch`. The HTML defines the
structure; JavaScript fills it. When we call
`document.querySelector('#publisher-table tbody')`, we get
the `tbody` node, ready to receive rows.

A concrete example from our code. When the book form loads, it
populates a dropdown with publishers fetched from the API:

```javascript
for (let i = 0; i < publishers.length; i++) {
    const option = document.createElement('option');
    option.value = publishers[i].publisher_id;
    option.textContent = publishers[i].name;
    this.selectPublisher.appendChild(option);
}
```

This is the core mechanism: JavaScript fetches the data, creates
new HTML elements on the fly, places them in the right spot in
the DOM, and can style them through classes or attributes. All
of this happens without reloading the page.
`createElement` makes the node. `textContent` sets its text.
`appendChild` attaches it to the page. Three operations, always
in this order.

Remember, never `innerHTML` with external data. That is an XSS
vulnerability. `textContent` is safe by design: it treats
everything as plain text, never as HTML.

## The JavaScript classes

In our project each HTML page has a dedicated JavaScript class. 
Every class has a **constructor** that stores references to DOM elements
and binds event listeners. The methods that follow depend on
the type of page.

### Table pages

The table pages (`Publishers`, `Categories`, `Authors`,
`Books`) display all records. These have two methods:

- **`load()`**: fetches all records from the API.
- **`render(data)`**: builds the table rows from the data.

### Form pages

The form pages (`Publisher`, `Category`, `Author`, `Book`)
handle create, edit, and delete. These have more methods:

- **`checkEdit()`**: reads the `?id=` parameter from the URL.
  If present, the form is in edit mode and calls `load(id)`.
  If absent, the form is in create mode and stays empty.
- **`load(id)`**: fetches one record from the API.
- **`render(data)`**: populates the input fields with the
  fetched data.
- **`save()`**: validates the input, builds a `payload` object,
  then calls `fetch` with POST (create) or PUT (update).
- **`remove()`**: asks for confirmation, then sends a DELETE
  request.

Notice that `save` and `remove` use the same `fetch` call
structure. The only thing that changes is the HTTP method:

| Operation | Method   |
|-----------|----------|
| Create    | `POST`   |
| Update    | `PUT`    |
| Delete    | `DELETE` |

Every HTTP request carries a **method**: a word that tells the
server what the client wants to do. Most introductory books
only teach two: GET to read a page, POST to send a form. But
HTTP defines more, and each has a precise role:

- **GET**: retrieve data. The browser sends this when you
  type a URL or click a link.
- **POST**: create a new resource. The server receives data
  it has never seen before.
- **PUT**: replace an existing resource. The server updates
  the record that matches the given ID.
- **DELETE**: remove a resource. The server deletes the
  record.

There are others (PATCH, HEAD, OPTIONS), but these four are
all you need for a CRUD application. In the next chapter we
will see how each method maps to a CRUD operation.

The URL is always the same (`this.API`). The server reads
the method to decide what to do. This is **REST**:
Representational State Transfer. The name is academic, but
the idea is simple. Remember the constructor of our classes?

```javascript
this.API = '/bibliotheca/public/api/publishers.php';
```

That URL is what REST calls a **resource**. Every time we
call `fetch`, we point to that same URL:

```javascript
response = await fetch(this.API, {
    method: 'POST',
    ...
});
```

The URL stays the same. Only the `method` changes: POST to
create, PUT to update, DELETE to remove.

This is the key insight of the chapter: one URL, different
methods. Everything else follows from here.
You act on it by choosing the right HTTP method. The URL says
*what*, the method says *what to do with it*.
Instead of inventing separate URLs like
`/api/publishers/create` and `/api/publishers/delete`, we
use a single URL and let the HTTP method express the intent.

The separation between `load` and `render` is the same in
every class: `load` talks to the server, `render` talks to the
DOM. Once you understand `Publisher`, you understand them all.
`Book` adds complexity (multiple authors, more fields), but
the structure is the same.

## Next

[Chapter 06 — CRUD](06_crud.md)
