# How It Works

## Single point of control

The application follows one rule: **the list reads, the form writes**.

- **List page** — displays all records. The only action is Edit.
- **Form page** — all modifications happen here: Create, Update,
  Disable, Delete.

One entity, one form, every operation. The user never has to wonder
*where* to do something — the answer is always: open the record.

## The life of a request

What happens when you click **Publishers** in the navigation bar?
Follow the numbers.

```
    Browser                                          Server
    ───────                                          ──────

 1  Click "Publishers"
    GET /bibliotheca/public/publishers
                │
                ▼
 2  .htaccess ─── RewriteRule ──► index.php?route=publishers
                                        │
                                        │  route in whitelist?
                                        │  yes ──► require pages/publishers.php
                                        │  no  ──► require pages/404.php
                                        │
                                        ▼
 3  Browser receives HTML ◄──────── index.php renders the full page:
    ┌─────────────────────┐         <header>, <nav>, <main>,
    │ header / nav        │         and inside <main>:
    │ ┌─────────────────┐ │         pages/publishers.php
    │ │ <table>         │ │         (empty <tbody>, plus a <script> tag)
    │ │   <thead>       │ │
    │ │   <tbody/>      │ │
    │ └─────────────────┘ │
    │ footer              │
    └─────────────────────┘
                │
                │  <script src="js/publishers.js">
                ▼
 4  PublishersView fires
    │
    │  constructor() → this.load()
    │
    │  async load() {
    │      fetch('/api/publishers.php')  ──────────────────────┐
    │  }                                                       │
    │                                                          ▼
    │                                               5  api/publishers.php
    │                                                  │
    │                                                  │  $db = new DBMS(...)
    │                                                  │  $publisher = new Publisher($db)
    │                                                  │
    │                                                  │  GET → $publisher->getAll()
    │                                                  │
    │                                                  ▼
    │                                               6  src/Publisher.php
    │                                                  │
    │                                                  │  SELECT publisher_id, name, status
    │                                                  │  FROM publisher
    │                                                  │  ORDER BY name
    │                                                  │
    │                                                  ▼
    │                                               7  src/DBMS.php
    │                                                  │
    │                                                  │  prepare → execute → fetchAll
    │                                                  │
    │                                                  ▼
    │                                               8  sql/bibliotheca.db
    │                                                  │
    │                                                  │  SQLite returns rows
    │                                                  │
    │                              JSON ◄──────────────┘
    │                              [{"publisher_id":1,"name":"Adelphi","status":1},
    │                               {"publisher_id":2,"name":"Einaudi","status":0},
    │                               ...]
    │
    │  render(publishers)
    │  │
    │  │  for each publisher:
    │  │      createElement('tr')
    │  │      if status === 0 → row.className = 'row-disabled'
    │  │      nameCell.textContent = publisher.name
    │  │      append [Edit] button
    │  │      append to <tbody>
    │  │
    ▼
 9  Browser shows the table
    ┌──────────────────────────┐
    │ header / nav             │
    │ ┌──────────────────────┐ │
    │ │ Adelphi        [Edit]│ │   ← active (normal)
    │ │ Einaudi        [Edit]│ │   ← disabled (gray)
    │ │ ...                  │ │
    │ └──────────────────────┘ │
    │ footer                   │
    └──────────────────────────┘
```

## Step by step

### 1 — The click

The user clicks a link. The browser sends a GET request to
`/bibliotheca/public/publishers`.

### 2 — URL rewriting

Apache's `.htaccess` intercepts the request. Since `/publishers`
is not a real file or directory, the `RewriteRule` rewrites it to:

```
index.php?route=publishers
```

### 3 — The router

`index.php` reads the `route` parameter, checks it against a
whitelist of allowed routes, and includes the matching page inside
a common HTML shell (header, nav, main, footer).

The page `pages/publishers.php` is minimal: an empty `<table>` and
a `<script>` tag. No data yet.

### 4 — JavaScript takes over

The browser loads `js/publishers.js`. The script creates a
`PublishersView` object. The constructor immediately calls `load()`,
which uses `fetch` to request data from the API.

### 5 — The controller

`api/publishers.php` receives the GET request. It creates a `DBMS`
instance (the database connection) and a `Publisher` instance (the
model). Based on the HTTP method, it calls the right model method.

For a plain GET with no `id` parameter, it calls `getAll()`.

### 6 — The model

`Publisher::getAll()` builds a SQL query that selects all records
— both active and disabled — and delegates execution to DBMS.

The model knows *what* to ask, but not *how* the database works.
That is the DBMS wrapper's job.

### 7 — The database wrapper

`DBMS::fetchAll()` prepares the statement, executes it, and returns
an array of associative arrays. Every query goes through prepared
statements — never string concatenation.

### 8 — SQLite

The database engine runs the query against `sql/bibliotheca.db`
and returns the rows to PHP.

### 9 — Rendering

The JSON response travels back to the browser. `PublishersView.render()`
loops through the array, creates a `<tr>` for each publisher using
`createElement` and `textContent` (never `innerHTML`), and appends
everything to the `<tbody>`.

Rows with `status === 0` get the class `row-disabled` and appear
grayed out. The list is read-only — the only action is the Edit
button, which links to the form page.

## The round trip

The key insight: **the page loads twice**.

1. **First trip** — the browser gets the HTML shell (step 1–3).
   The table is empty.
2. **Second trip** — JavaScript fetches the data as JSON (step 4–9).
   The table fills up.

This is the foundation of every modern web application. The structure
(HTML) and the data (JSON) travel separately. The browser assembles
them.

## Where each file lives

```
bibliotheca/
    src/                    ← private (not web-accessible)
        DBMS.php            7  database wrapper
        Publisher.php       6  model
    sql/
        bibliotheca.db      8  database
    public/                 ← document root (web-accessible)
        .htaccess           2  URL rewriting
        index.php           3  router
        pages/
            publishers.php  3  list template
            publisher.php      form template
        js/
            publishers.js   4  list logic
            publisher.js       form logic
        api/
            publishers.php  5  controller
```

The numbers match the steps in the diagram. Notice how `src/` and
`sql/` sit outside `public/`. The browser can never reach them
directly — only PHP can.

## The life of a form

The list page shows data. The form page changes it. What happens
when you click **Add new publisher**, or **Edit** on an existing one?

### Opening the form

```
    Browser                                          Server
    ───────                                          ──────

    ┌─────────────────────────────────────┐
    │  Two ways to reach the form:        │
    │                                     │
    │  A) Click "Add new publisher"       │
    │     GET /publisher                  │
    │     (no ?id parameter)              │
    │                                     │
    │  B) Click "Edit" on Einaudi         │
    │     GET /publisher?id=2             │
    │                                     │
    └─────────────────────────────────────┘
                │
                ▼
 1  .htaccess ─── RewriteRule ──► index.php?route=publisher
                                        │
                                        ▼
 2  Browser receives HTML ◄──────── index.php renders the page:
    ┌─────────────────────┐         pages/publisher.php
    │ header / nav        │
    │ ┌─────────────────┐ │         <form> with:
    │ │ Add Publisher    │ │         - hidden <input> for ID (empty)
    │ │                  │ │         - text <input> for name (empty)
    │ │ Name: [        ] │ │         - checkbox Active (hidden until Edit)
    │ │                  │ │         - Save button
    │ │ [Save] [Cancel]  │ │         - Delete button (hidden until Edit)
    │ └─────────────────┘ │
    │ footer              │
    └─────────────────────┘
                │
                │  <script src="js/publisher.js">
                ▼
 3  PublisherForm fires
    │
    │  constructor()
    │  │  binds form submit → this.save()
    │  │  binds delete button → this.remove()
    │  │  calls this.checkEdit()
    │  │
    │  checkEdit()
    │  │  reads ?id from URL
    │  │
    │  ├─── no ?id ──► form stays empty ("Add Publisher")
    │  │                checkbox and Delete stay hidden
    │  │                done — waiting for user input
    │  │
    │  └─── ?id=2 ──► this.loadRecord(2)
    │                   │
    │                   │  fetch('/api/publishers.php?id=2')  ─────┐
    │                   │                                          │
    │                   │                                          ▼
    │                   │                               api/publishers.php
    │                   │                               │
    │                   │                               │  GET with ?id=2
    │                   │                               │  $publisher->getById(2)
    │                   │                               │
    │                   │                               │  SELECT ... WHERE publisher_id = :id
    │                   │                               │
    │                   │  JSON ◄────────────────────────┘
    │                   │  {"publisher_id":2,"name":"Einaudi","status":1}
    │                   │
    │                   │  fills the form:
    │                   │  - hidden ID ← 2
    │                   │  - name input ← "Einaudi"
    │                   │  - checkbox Active ← checked (status 1)
    │                   │  - shows checkbox and Delete button
    │                   │  - title ← "Edit Publisher"
    │                   │
    │                   ▼
    │  ┌───────────────────────┐
    │  │ Edit Publisher         │
    │  │                        │
    │  │ Name: [Einaudi      ]  │
    │  │ ☑ Active               │
    │  │                        │
    │  │ [Save] [Delete] Cancel │
    │  └────────────────────────┘
    │
    ▼  Waiting for user input.
```

### Saving the form

```
    Browser                                          Server
    ───────                                          ──────

 4  User modifies the record, clicks Save
    │
    │  form submit event
    │  e.preventDefault() — no page reload
    │
    │  this.save()
    │  │
    │  │  this.validate()
    │  │  ├─── name empty? ──► show error, stop
    │  │  └─── name ok?    ──► continue
    │  │
    │  │  check hidden ID field:
    │  │
    │  ├─── ID is empty (Add mode)
    │  │    │
    │  │    │  fetch(API, {                    ────────────────────┐
    │  │    │      method: 'POST',                                │
    │  │    │      body: {"name":"Einaudi Editore"}               │
    │  │    │  })                                                  │
    │  │    │                                                      ▼
    │  │    │                                           api/publishers.php
    │  │    │                                           │
    │  │    │                                           │  POST
    │  │    │                                           │  validate: name required? ✓
    │  │    │                                           │  check: exists()? → no
    │  │    │                                           │  $publisher->insert("Einaudi Editore")
    │  │    │                                           │
    │  │    │                                           │  INSERT INTO publisher (name)
    │  │    │                                           │  VALUES (:name)
    │  │    │                                           │
    │  │    │  JSON ◄───────────────────────────────────┘
    │  │    │  {"publisher_id":13,"name":"Einaudi Editore"}
    │  │
    │  └─── ID is 2 (Edit mode)
    │       │
    │       │  fetch(API, {                    ────────────────────┐
    │       │      method: 'PUT',                                  │
    │       │      body: {"publisher_id":2,                        │
    │       │             "name":"Einaudi Editore",                │
    │       │             "status":0}                              │
    │       │  })                                                   │
    │       │                                                       ▼
    │       │                                            api/publishers.php
    │       │                                            │
    │       │                                            │  PUT
    │       │                                            │  validate: ID and name? ✓
    │       │                                            │  check: exists()? → no
    │       │                                            │  status=0 and hasBooks()? → block or allow
    │       │                                            │  $publisher->update(2, "Einaudi Editore", 0)
    │       │                                            │
    │       │                                            │  UPDATE publisher
    │       │                                            │  SET name = :name, status = :status
    │       │                                            │  WHERE publisher_id = :id
    │       │                                            │
    │       │  JSON ◄────────────────────────────────────┘
    │       │  {"publisher_id":2,"name":"Einaudi Editore","status":0}
    │
    │
 5  ├─── response ok? ──► redirect to /publishers (the list reloads)
    │
    └─── response error (409/422)?
         │
         │  {"error":"Publisher already exists"}
         │  — or —
         │  {"error":"Cannot disable: publisher has associated books"}
         │
         │  this.showError('publisher-name', result.error)
         │
         ▼
         ┌──────────────────────────────┐
         │ Edit Publisher                │
         │                               │
         │ Name: [Einaudi Editore     ]  │
         │ Publisher already exists       │
         │ ☐ Active                      │
         │                               │
         │ [Save] [Delete] Cancel        │
         └───────────────────────────────┘
```

### Deleting from the form

```
    Browser                                          Server
    ───────                                          ──────

    ┌───────────────────────┐
    │ Edit Publisher         │
    │                        │
    │ Name: [Einaudi      ]  │
    │ ☑ Active               │
    │                        │
    │ [Save] [Delete] Cancel │  ◄── user clicks [Delete]
    └────────────────────────┘
                │
                ▼
 1  confirm('Permanently delete this publisher?
             This cannot be undone.')
    │
    ├─── Cancel ──► nothing happens, form stays open
    │
    └─── OK
         │
         │  fetch(API, {                         ────────────────────┐
         │      method: 'DELETE',                                    │
         │      body: {"publisher_id": 2}                            │
         │  })                                                       │
         │                                                           ▼
         │                                                api/publishers.php
         │                                                │
         │                                                │  DELETE
         │                                                │  validate: ID required? ✓
         │                                                │
         │                                                │  $publisher->hasBooks(2, false)?
         │                                                │  (checks ALL books, active or not)
         │                                                │
         │                                                ├─── yes (has books)
         │                                                │    │
         │                                                │    │  HTTP 422
         │                                                │    │  {"error":"Cannot delete:
         │                                                │    │   publisher has associated books"}
         │                                                │    │
         │                                                └─── no (safe to delete)
         │                                                     │
         │                                                     │  $publisher->delete(2)
         │                                                     │
         │                                                     │  DELETE FROM publisher
         │                                                     │  WHERE publisher_id = :id
         │                                                     │
         │                                                     │  {"deleted": true}
         │                                                     │
 2  JSON ◄─────────────────────────────────────────────────────┘
    │
    ├─── response ok? ──► redirect to /publishers
    │
    └─── response error (409/422)?
         │
         │  alert("Cannot delete: publisher has associated books")
         │  form stays open
         ▼
```

### Step by step

**Step 1–2** — identical to the list page: `.htaccess` rewrites,
the router loads `pages/publisher.php` inside the HTML shell.
The form arrives empty.

**Step 3 — checkEdit** — `PublisherForm` reads the URL. If there
is no `?id`, the form stays empty — it is an Add. The Active
checkbox and Delete button stay hidden because they make no sense
on a record that does not exist yet.

If `?id=2` is present, JavaScript fetches that single record from
the API and fills the form fields, including the checkbox state.
The title changes from "Add" to "Edit", and the checkbox and
Delete button become visible.

One form, two modes. The hidden `<input>` for the ID decides which.

**Step 4 — save** — the user clicks Save. JavaScript prevents the
default form submission (no page reload) and runs validation. If
the name is empty, it shows an inline error and stops.

If validation passes, JavaScript checks the hidden ID:
- **Empty** → POST (create a new record)
- **Has a value** → PUT (update the existing record, including status)

The server validates again (never trust the client), normalizes the
input (`ucwords`, `strtolower`, `trim`), checks for duplicates, and
checks whether a disable is allowed (a publisher with active books
cannot be disabled). Then it executes the query.

**Step 5 — response** — if the server returns OK, JavaScript
redirects to the list page. If the server returns an error (e.g.,
409 Conflict for a duplicate name, or 422 for disabling with active
books), JavaScript shows the server's error message inline under
the field.

**Delete** — happens from the same form page. The user clicks
Delete, confirms the dialog, and JavaScript sends a DELETE request.
The server checks referential integrity — a publisher with any books
(active or disabled) cannot be deleted. On success, the record is
permanently removed (`DELETE FROM`) and the user is redirected to
the list.

### Disable vs Delete

The form offers two ways to remove a record:

- **Disable** — uncheck Active, click Save.
  SQL: `UPDATE status=0`. Reversible. Checks for active books.
- **Delete** — click Delete.
  SQL: `DELETE FROM`. Permanent. Checks for any books.

Disabling keeps the record in the database but grayed out in the
list. It can be reversed by checking Active again.

Deleting removes the record forever. The safety check is stricter:
it looks for *any* associated books, not just active ones, because
even a disabled book still holds a foreign key reference.

### The key insight

The form page loads **once or twice**, depending on the mode:

| Mode | Trips | What happens                              |
|------|-------|-------------------------------------------|
| Add  | 1     | HTML arrives, form is empty, done         |
| Edit | 2     | HTML arrives, then fetch loads the record  |

Saving always adds **one more trip**: the POST or PUT to the API.
On success, the redirect to the list triggers the full list flow
again (steps 1–9 from the first diagram).

## The complete picture

All four CRUD operations, one entity, two pages:

- **Read** — list page, `GET`, 2 trips.
  Table fills with data.
- **Create** — form page, `POST`, 2 trips.
  Empty form → save → list.
- **Update** — form page, `PUT`, 3 trips.
  Form loads record → save → list.
- **Disable** — form page, `PUT`, 3 trips.
  Uncheck Active → save → gray row.
- **Delete** — form page, `DELETE`, 2 trips.
  Click Delete → confirm → gone.

The list is read-only. Every write operation goes through the form.
This is the single point of control.

Every other entity (Category, Author, Book) follows the same
pattern. The files change, the flow does not.

## Authentication

Bibliotheca has a single admin user. The login flow adds one
layer on top of everything described above.

### The login

1. The user visits `/login` (default credentials: `admin` /
   `bibliotheca`). The front controller loads `pages/login.php`,
   which includes `js/login.js`.
2. The user submits username and password. JavaScript sends
   a POST to `api/auth.php` with JSON body.
3. The API calls `Auth::login()`, which queries the `user`
   table and compares the password with `password_verify()`.
4. If valid, `session_regenerate_id(true)` creates a new
   session (preventing fixation), and `$_SESSION['user_id']`
   is set. The API returns `{"authenticated": true}`.
5. JavaScript redirects to the home page.

### Changing the password

The default password should be changed immediately. Generate
a new hash from the command line:

```bash
php -r "echo password_hash('yournewpassword', PASSWORD_DEFAULT);"
```

Then update the database:

```bash
sqlite3 sql/bibliotheca.db
UPDATE user SET password = '$2y$10$...' WHERE username = 'admin';
```

Replace `$2y$10$...` with the hash you generated. The username
can be changed the same way.

### Protecting writes

Every API endpoint checks authentication before processing
POST, PUT, or DELETE requests:

```php
Csrf::start();
Csrf::verify();
Auth::require();  // 401 if not logged in
```

`Auth::require()` reads `$_SESSION['user_id']`. If it is not
set, it responds with HTTP 401 and exits.

### Hiding the UI

The front controller sets `$isLoggedIn = Auth::check()` and
exposes it to templates and JavaScript:

- **PHP templates** — the "Add new" buttons are wrapped in
  `<?php if ($isLoggedIn): ?>` blocks.
- **JavaScript** — the `<meta name="authenticated">` tag
  carries the state. `auth.js` reads it into `AUTH.authenticated`,
  and list views check this before rendering Edit buttons.
- **Form pages** — the front controller redirects to `/login`
  if the user tries to access a form page without being
  logged in.

The API is the real guard. The UI changes are convenience —
they prevent confusion, not attacks.

## See also

This overview shows the full journey. The chapters break it into
layers:

- [Chapter 03 — Project Structure](03_structure.md) — where files live and why
- [Chapter 04 — Backend](04_backend.md) — DBMS, Model, Controller
- [Chapter 05 — Frontend](05_frontend.md) — routing, fetch, DOM
- [Chapter 06 — CRUD](06_crud.md) — the four operations in detail
