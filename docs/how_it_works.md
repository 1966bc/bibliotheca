# How It Works

## Request and response

Everything on the web is built on one mechanism: a client
sends a **request**, a server sends back a **response**. That
is it. Every click, every page load, every form submission,
every API call — request, response. HTML, CSS, JavaScript,
frameworks, databases — they all exist to produce or consume
requests and responses. If you understand this, you understand
the web.

In our application, every request that JavaScript makes to
the server goes through one function: `fetch`. It is the
bridge between the browser and the server. Without it,
JavaScript can change the page but cannot talk to anyone.
This is why learning JavaScript matters. It can change the
color of a button or make elements appear and disappear, but
it does much more than that. 
It is the nervous system of the application (Chapter 00):
the language that ferries data between the browser and the
server. `fetch` is its Charon.

## Open your terminal

The best way to see this in action is to watch it from the
outside. Not with the browser — the browser does too much at
once. Use `curl`, which does exactly one thing: send a request,
show the response.

## The first request

Click "Categories" on the menu. What actually happens? Let
us ask `curl`:

```bash
curl -v http://localhost/bibliotheca/public/categories \
    2>&1 | grep "^>"
```

```
> GET /bibliotheca/public/categories HTTP/1.1
> Host: localhost
> User-Agent: curl/7.88.1
> Accept: */*
>
```

This is the **request**. The browser sends these exact bytes
to Apache on port 80 (or 443 for HTTPS). The first line says: send me the page at this URL, using
HTTP/1.1. (It could be a page, an image, a JSON response —
the browser does not know in advance. It just asks.) The rest
are headers — metadata about the request.

Now the response:

```bash
curl -s http://localhost/bibliotheca/public/categories
```

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="a7f3b9...">
    <meta name="authenticated" content="0">
    <title>Bibliotheca</title>
    <link rel="stylesheet" href="...style.css">
</head>
<body>
    <header>...</header>
    <main>
        <h2>Categories</h2>
        <table id="category-table">
            <thead>
                <tr><th>Name</th><th></th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </main>
    <script src="...categories.js"></script>
</body>
</html>
```

Look at the `<tbody>`. It is empty. There are no records.

## Where are the records?

They are not in this response. The HTML contains the structure
— the skeleton — but no data. The table is an empty container
waiting to be filled.

The records live in the database. The only way to get them is
to call the API:

```bash
curl -s http://localhost/bibliotheca/public/api/categories.php
```

```json
[
  {"category_id":1,"name":"Fiction","status":1},
  {"category_id":2,"name":"Science","status":1},
  {"category_id":3,"name":"History","status":0}
]
```

There they are. JSON, not HTML. Data, not structure.

## Two requests, one page

This is the key: **loading a page takes two requests**.

1. **First request** — the browser asks for the page. Apache
   rewrites the URL through `.htaccess`, the router loads the
   template, PHP sends back HTML. The table is empty.
2. **Second request** — JavaScript starts (the `<script>` tag
   at the bottom of the page), calls `fetch` to the API, gets
   JSON back, and fills the table with `createElement` and
   `textContent`.

When you use the browser, this happens automatically — so fast
you never notice the table is empty for a fraction of a second.
With `curl`, you see the truth: `curl` does not execute
JavaScript, so the second request never happens. The table
stays empty.

You can see both requests in the browser's Network tab (F12).
The first returns HTML. The second returns JSON. Two trips,
one page.

## What travels on the wire

When the browser sends a request, it is not a file. It is a
block of text transmitted over a TCP connection to port 80 (or 443 for HTTPS).

```
> GET /bibliotheca/public/categories HTTP/1.1
> Host: localhost
> Accept: */*
>
```

The first line is the **request line**: method, path, protocol.
The lines that follow are **headers**: key-value pairs. The
empty line marks the end. A GET has no body; a POST carries one
after the blank line.

The server responds in the same format:

```
< HTTP/1.1 200 OK
< Content-Type: text/html; charset=UTF-8
< Set-Cookie: PHPSESSID=...; HttpOnly; SameSite=Strict
<
<!DOCTYPE html>...
```

Status line, headers, blank line, body. That is all HTTP is:
structured text over a wire.

No files are written to disk. Apache has a process (managed by
its **MPM**, Multi-Processing Module) listening on a TCP socket.
The bytes arrive in memory, Apache interprets them, calls PHP,
and sends the response back on the same connection.

With HTTP/1.1, the connection stays open briefly (**keep-alive**)
so the browser can reuse it for the CSS, the JavaScript, and
the favicon without opening a new connection each time.

## What Apache does

Apache receives the request and reads the path. Since
`/categories` is not a real file, `.htaccess` rewrites it:

```
RewriteRule ^(.+)$ index.php?route=$1 [QSA,L]
```

Now Apache calls PHP with `index.php?route=categories`. The
router checks the whitelist, finds "categories", and loads
`pages/categories.php` inside the HTML shell (header, nav,
main, footer). PHP sends the complete HTML back to Apache,
Apache sends it to the browser.

## What the browser does

The browser receives the HTML and builds the **DOM** (Document
Object Model) — an in-memory tree of objects:

```
table #category-table
├── thead
│   └── tr
│       └── th  "Name"
└── tbody  (empty)
```

Every tag becomes a node. JavaScript works on this tree, not
on the HTML text. When it calls `createElement('tr')` and
`appendChild(row)`, it adds nodes to the tree and the browser
redraws the screen.

The `<script>` tag at the bottom triggers the second request.
It sits at the bottom so the DOM is already built when
JavaScript starts — otherwise `querySelector` would not find
the elements.

## What JavaScript does

The browser loads `js/categories.js` (the `<script>` tag
at the bottom of the HTML). This file creates a `Categories`
object. The constructor
calls `load()`. These are methods we wrote — `load` and
`render` are not part of JavaScript, they are our convention
(Chapter 05). The only built-in part is `fetch`, which sends
the HTTP request. `this.API` is a property set in the
constructor — it holds the URL of the API endpoint:

```javascript
this.API = '/bibliotheca/public/api/categories.php';
```

So when we write:

```javascript
async load() {
    const response = await fetch(this.API);
    const categories = await response.json();
    this.render(categories);
}
```

`fetch(this.API)` sends a GET request to that URL —
the same request we just made with `curl`. Notice there is no
`method: 'GET'` here: when you call `fetch` with just a URL,
it defaults to GET. Writing:

`fetch(this.API)`

and:

`fetch(this.API, { method: 'GET' })`

is the same thing. You only need to specify the method for
POST, PUT, and DELETE.

The server responds through the API endpoint
(`api/categories.php`),
which calls the model (`src/Category.php`),
which calls DBMS (`src/DBMS.php`),
which queries SQLite (`sql/bibliotheca.db`),
and if everything went as it should, the server responds
with JSON — a list of records if we sent GET, the new or
updated record if we sent POST or PUT, a confirmation if
we sent DELETE.

Now you see why the directories are organized that way.
`src/` and `sql/` are outside `public/` because they are
private — only PHP can reach them. `api/` and `pages/` are
inside `public/` because they need to receive HTTP requests.
The directory structure is not arbitrary. It reflects who
can talk to who.

One API file and one model per entity. Every operation on
categories — read, create, update, delete — goes through
those same two files. The pages and JavaScript files instead
are two: one for the list (`categories.php` +
`categories.js`) and one for the form (`category.php` +
`category.js`).

In the case of a GET, back in the browser, `load` receives
the JSON data. `render` builds the DOM: for each category,
it creates a `<tr>`, sets the text with `textContent`, and
appends it to the `<tbody>`. The empty table fills up.
For POST, PUT, and DELETE the flow is different — we will
see that shortly in "What happens when you click Save".

## How data changes shape

A single piece of data passes through four representations on
its way from disk to screen:

| Layer      | Form                   |
|------------|------------------------|
| SQLite     | Row in a table         |
| PHP        | Associative array      |
| Network    | JSON text              |
| JavaScript | Object with properties |

```
SQLite:  | 1 | Fiction | 1 |
             ↓
PHP:     ['category_id'=>1, 'name'=>'Fiction',
          'status'=>1]
             ↓
JSON:    {"category_id":1,"name":"Fiction",
          "status":1}
             ↓
JS:      {category_id: 1, name: "Fiction",
          status: 1}
```

Each layer only knows about the one directly below it.
JavaScript has no idea SQLite exists; it sees a URL that
returns JSON. The model has no idea JavaScript exists; it
returns an array to the controller.

## The chain

The full chain, with the actual files. Notice the two
requests: the first (steps 1-3) brings the HTML, the second
(steps 4-8) brings the data.

```
First request — the page:

  Browser
  1 → .htaccess             (rewrites URL)
  2 → index.php             (router, loads template)
  3 → pages/categories.php  (HTML skeleton)
      ← HTML response (empty table + script tag)

Second request — the data:

  4 → js/categories.js      (fetch)
  5 → api/categories.php    (controller)
  6 → src/Category.php      (model)
  7 → src/DBMS.php           (database wrapper)
  8 → sql/bibliotheca.db     (SQLite)
      ← JSON response (the records)
```

And back: SQLite returns rows → `DBMS.php` returns arrays →
the model (`Category.php`) returns to the controller
(`api/categories.php`) → the controller encodes JSON →
JavaScript (`categories.js`) receives it → `render` builds
the DOM → the user sees the table.

This is MVC in action: the **Model** (`src/Category.php`)
handles the data, the **Controller** (`api/categories.php`)
handles the request, the **View** (`pages/categories.php` +
`js/categories.js`) handles what the user sees.

Take note of this mapping. Learn which directory holds what.
Even if it all feels unclear right now, this map will be the
thing that makes everything click.

## What happens when you click Save

The list page reads. The form page writes. What happens when
the user fills a form and clicks Save?

```bash
# JavaScript sends this (you can simulate with curl):
curl -X POST \
     -H "Content-Type: application/json" \
     -H "X-CSRF-Token: a7f3b9..." \
     -d '{"name":"Biography"}' \
     http://localhost/bibliotheca/public/api/categories.php
```

The same URL as the GET. The only difference is the method:
POST instead of GET. When JavaScript writes:

```javascript
response = await fetch(this.API, {
    method: 'POST',
    ...
});
```

that `'POST'` becomes the first word of the HTTP request
line: `POST /api/categories.php HTTP/1.1`. It travels over
the wire like any other text. On the server side, PHP reads
it from `$_SERVER['REQUEST_METHOD']`. The controller uses
this value to decide what to do:

- **POST** → validate, `insert`, return the new record
- **PUT** → validate, `update`, return the updated record
- **DELETE** → check dependencies, `delete`, confirm

## What `fetch` sends to the controller (`api/categories.php`) and what it gets back

Look at a complete `fetch` call. Remember, `this.API` is
the URL we defined in the constructor
(`/bibliotheca/public/api/categories.php`):

The `payload` is the JavaScript object that holds the data
we want to send — built from the form fields:

```javascript
const payload = { name: 'Biography' };
```

For a category, the payload is simple — just a name. For a
book, it carries more fields — as many as the database table
columns we need to insert or update:

```javascript
const payload = {
    title: 'The Art of War',
    publisher_id: 1,
    category_id: 3,
    pages: 128,
    published: 500,
    author_ids: [5]
};
```

The structure changes, the mechanism does not. Then `fetch`
sends it:

```javascript
response = await fetch(this.API, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector(
            'meta[name="csrf-token"]'
        ).content
    },
    body: JSON.stringify(payload)
});
```

Everything inside this call becomes part of the HTTP request
that travels to the server:

- **`method: 'POST'`** → PHP reads it from
  `$_SERVER['REQUEST_METHOD']`.
- **`'Content-Type': 'application/json'`** → tells PHP that
  the body is JSON. PHP reads the body with
  `json_decode(file_get_contents('php://input'))`.
- **`'X-CSRF-Token': ...`** → the security token. PHP reads
  it from the headers and compares it to the session. If it
  does not match, the request is rejected with 403.
- **`body: JSON.stringify(payload)`** → the actual data. The
  `payload` object is converted to a JSON string because HTTP
  only carries text.

Nothing is hidden. Everything that `fetch` sends, PHP can
read. The request is just structured text — method, headers,
body — and each part has a purpose.

## Inside the controller (`api/categories.php`)

The controller runs on the server. You never see it work —
there is no visible output, no window, no log on screen. Out
of sight, out of mind. But it is the most important file in
the chain.

What does `api/categories.php` actually do when a request
arrives? The first thing it does is load the model and the
database wrapper, and create the objects it needs:

```php
require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Category.php';

$db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
$category = new Category($db);
```

Notice the path: `../../src/` — the controller reaches
outside `public/` to get to the private files. The model
receives the DBMS instance in its constructor, so it can
query the database.

Then it reads the HTTP method and decides what to do:

```php
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // read: call the model, return JSON
} elseif ($method === 'POST') {
    // create: read the body, validate, insert
} elseif ($method === 'PUT') {
    // update: read the body, validate, update
} elseif ($method === 'DELETE') {
    // delete: read the body, check dependencies, delete
}
```

One file, one `if/elseif` chain. The HTTP method decides
the branch. This is why REST works (Chapter 05): the same
URL handles all four operations, and the method tells the
controller which one.

For POST and PUT, the controller reads the body that `fetch`
sent:

```php
$data = json_decode(
    file_get_contents('php://input'), true
);
$name = trim(strip_tags($data['name'] ?? ''));
```

`php://input` is where PHP finds the request body — the
JSON string that `JSON.stringify(payload)` created on the
JavaScript side. `json_decode` turns it back into a PHP
array. Then the controller validates, normalizes, and calls
the model:

```php
if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}

$id = $category->insert($name);
echo json_encode(['category_id' => $id, 'name' => $name]);
```

If something is wrong, the controller sets an error status
code and stops. If everything is fine, it calls the model
and returns the result as JSON. The controller never touches
the database directly — it delegates to the model, and the
model delegates to DBMS.

## Below the surface, under the browser's surfing: `src/`

When the controller (`api/categories.php`) calls `$category->insert($name)`, what
happens? Open `src/Category.php`:

```php
public function insert(string $name): int
{
    $sql = "INSERT INTO category (name) VALUES (:name)";

    return $this->db->insert($sql, [':name' => $name]);
}
```

The model knows the SQL — it knows the table name, the
column names, the query structure. But it does not execute
the query itself. It passes the SQL and the parameters to
`$this->db`, which is the DBMS instance it received in its
constructor.

Now open `src/DBMS.php`. Before executing a query, DBMS
binds each parameter with the correct type:

```php
private function bindParams(\PDOStatement $stmt,
                            array $params): void
{
    foreach ($params as $key => $value) {
        $type = match (true) {
            is_int($value)  => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default         => PDO::PARAM_STR,
        };

        $stmt->bindValue($key, $value, $type);
    }
}
```

An integer is bound as `PARAM_INT`, a null as `PARAM_NULL`,
a string as `PARAM_STR`. The database receives the right
type, not just text.

We wrote this function (that in a class is more correctly
named a method, just as variables are named properties). It
is not part of PHP or PDO — it is ours. Why? Because we control the type of data at every level
(Chapter 07). JavaScript validates the input, PHP normalizes
it, and here — at the deepest point — we make sure the
database receives an integer as an integer, not as a string
that looks like a number. Instead of repeating this logic in
every function, we write it once and reuse it everywhere.
This is what it means to have dominion over the code: we make
PDO work for us, not the other way around.

Then the insert function uses it:

```php
public function insert(string $sql,
                       array $params = []): int
{
    $stmt = $this->pdo->prepare($sql);
    $this->bindParams($stmt, $params);
    $stmt->execute();
    return (int) $this->pdo->lastInsertId();
}
```

DBMS uses PDO — PHP's built-in database layer. `prepare`
creates the query with placeholders (`:name`).
`bindParams` attaches each value with its type.
`execute` runs the query. `lastInsertId` returns the ID
of the new row.

This is the deepest point of the chain. From here, the data
travels back up: DBMS returns the ID to the model, the model
returns it to the controller, the controller wraps it in
JSON and sends it back to `fetch`.

Three files, three responsibilities:

| File                   | Role       | Handles |
|------------------------|------------|---------|
| `api/categories.php`   | Controller | HTTP    |
| `src/Category.php`     | Model      | SQL     |
| `src/DBMS.php`         | Wrapper    | PDO     |

Each one does one thing.

## What comes back

What the controller sends back depends on what you asked —
which HTTP method you used:

- **GET** → the server returns data as JSON: a list of
  records, or a single record. This is the only method that
  returns data to display.
- **POST, PUT, DELETE** → the server does not return data to
  display. It returns a confirmation
  (`{"category_id":4,"name":"Biography"}`) or an error
  (`{"error":"Category already exists"}`).

Every response also carries a **status code**: 200 (OK),
400 (bad input), 401 (not logged in), 409 (duplicate),
422 (dependency error).

The user never sees this JSON directly. Unlike a traditional
website, the server does not redirect to a new page. It just
sends JSON back. It is JavaScript that reads the response
and decides what to do: redirect to the list on success, or
show the error message inline.

JavaScript reads both:

```javascript
if (!response.ok) {
    const result = await response.json();
    this.showError('category-name', result.error);
    return;
}
```

`response.ok` checks the status code. `response.json()`
reads the body. The conversation is complete: `fetch` sent
a request, the controller processed it, and the response
tells JavaScript what happened.

On success, JavaScript redirects to the list page — which
triggers the full two-request cycle again.

On error (409 duplicate, 422 dependency), JavaScript reads
the response and shows the message inline with `showError`.

## Edit mode

Editing adds one extra request (Chapter 05 and 06). When the
user clicks Edit, the URL contains `?id=2`. `checkEdit` sees
it, `load(2)` fetches the record via GET, `render` fills the
form. Then Save sends a PUT. Three requests total instead of
two.

## The complete picture

| Operation | Method   | Requests | Flow                     |
|-----------|----------|----------|--------------------------|
| Read      | `GET`    | 2        | HTML + JSON → table      |
| Create    | `POST`   | 2        | Empty form → save → list |
| Update    | `PUT`    | 3        | Form + load → save → list|
| Delete    | `DELETE` | 2        | Confirm → delete → list  |

Every operation follows the same pattern: the HTML arrives
first (the skeleton), then JavaScript does the rest (the
data). The list is read-only. Every write goes through the
form. One URL per entity, different HTTP methods.

Every entity — Publisher, Category, Author, Book — follows
this exact flow. The files change, the pattern does not.
