# Chapter 06 — CRUD

## The sign of the four

CRUD stands for Create, Read, Update, Delete. Every data-driven
application does these four things. Nothing more, nothing less.

In the previous chapter we learned that HTTP has a method for
each operation. Here is the complete mapping, from the browser
to the database:

| Operation | HTTP Method | SQL       | Example                  |
|-----------|-------------|-----------|--------------------------|
| Create    | `POST`      | `INSERT`  | add a new publisher      |
| Read      | `GET`       | `SELECT`  | list all publishers      |
| Update    | `PUT`       | `UPDATE`  | change a publisher's name|
| Delete    | `DELETE`    | `DELETE`  | remove a publisher       |

Three layers, one intention. The HTTP method arrives at the
API file (e.g. `api/publishers.php`). In MVC architecture,
this file is called the **controller**: it receives the
request and decides what to do. It does not talk to the
database directly. Instead, it calls the **model**: a PHP
class (file) that lives in `src/` (outside `public/`, because it
handles data, not HTTP requests). Each model — `Publisher.php`,
`Category.php`, `Author.php`, `Book.php` — implements the
CRUD methods internally: `insert`, `getAll`, `getById`,
`update`, `delete`. None of them talks to the database
directly either. They all use `DBMS.php`, a class we wrote
that wraps PDO and exposes simple methods: `fetchOne`,
`fetchAll`, `execute`. Every model receives a `DBMS` instance
in its constructor and uses it for every query. One class for
database access, shared by all models. The controller calls
the model, the model calls DBMS, DBMS talks to SQLite, and
the controller returns the result as JSON.

## Soft delete vs hard delete

Bibliotheca uses a `status` column (1 = active, 0 = disabled).
When you disable a record, the row stays in the database — it
just appears greyed out in the list. This is a **soft delete**:
a PUT that sets `status` to 0.

When you delete a record, it is gone. `DELETE FROM` removes the
row entirely. This is a **hard delete**: permanent, irreversible.

Why both? In a real application, you often want to keep the
data. An author who is temporarily unavailable should not
disappear from the database — their books still reference them.
Disabling is safer. Deleting is for cleanup.

## From form to database

In a classic website, clicking a button loads a new page. In
Bibliotheca, JavaScript intercepts the action, calls `fetch`
with the right HTTP method, and updates the page without
reloading. Let us follow each operation step by step.

### Create, POST

1. The user fills the form and clicks Save.
2. JavaScript builds the `payload` object from the form fields.
3. `fetch` sends a POST with the payload as JSON body.
4. The controller validates and calls the model's `insert`.
5. The model executes `INSERT INTO` via PDO.
6. The controller returns the new record as JSON.
7. JavaScript redirects to the list page.

### Read, GET

1. The page loads and JavaScript calls `fetch` with GET.
2. The controller calls the model's `getAll` or `getById`.
3. The model queries via PDO and returns an array.
4. The controller encodes the array as JSON.
5. JavaScript receives the data and calls `render` to build
   the table rows or populate the form fields.

### Update, PUT

1. The user clicks Edit — the browser goes to the form page
   with `?id=` in the URL.
2. `checkEdit` detects the ID and calls `load(id)`.
3. `load` fetches the record via GET, then `render` populates
   the form.
4. The user modifies the fields and clicks Save.
5. JavaScript builds the `payload` and adds the record ID.
6. `fetch` sends a PUT with the payload as JSON body.
7. The controller validates and calls the model's `update`.
8. JavaScript redirects to the list page.

### Delete, DELETE

1. The user clicks Delete — a confirmation dialog appears.
2. JavaScript builds the `payload` with the record ID.
3. `fetch` sends a DELETE with the payload as JSON body.
4. The controller calls the model's `delete`
   (`DELETE FROM` — permanent).
5. JavaScript redirects to the list page.

Notice how every operation follows the same shape: build the
payload, choose the method, call `fetch`, handle the response.
The only things that change are the HTTP method and the data
inside the payload.

## Unsupported methods

Our controllers dispatch on four methods: GET, POST, PUT, DELETE.
What happens if someone sends a PATCH? The `else` branch returns
HTTP 405 (Method Not Allowed) — but not alone. RFC 7231 asks the
server to include an `Allow` header listing the methods it does
accept:

```php
http_response_code(405);
header('Allow: GET, POST, PUT, DELETE');
echo json_encode(['error' => 'Method not allowed']);
```

It costs one line and tells the client exactly what is on the
menu. Small habit, correct by the book.

## Next

[Chapter 07 — Validation](07_validation.md)
