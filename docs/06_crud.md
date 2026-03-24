# Chapter 06 — CRUD

## The four operations

CRUD stands for Create, Read, Update, Delete. Every data-driven
application does these four things. Nothing more, nothing less.

| Operation | HTTP Method | SQL       | Example                     |
|-----------|-------------|-----------|-----------------------------|
| Create    | POST        | INSERT    | Add a new publisher         |
| Read      | GET         | SELECT    | List all publishers         |
| Update    | PUT         | UPDATE    | Change a publisher's name   |
| Delete    | DELETE       | DELETE    | Remove a publisher          |

Bibliotheca also uses a `status` column (1 = active, 0 = disabled).
Disabling a record is an Update (PUT), not a Delete — the record stays
in the database but appears greyed out in the list. Deleting is permanent:
`DELETE FROM` removes the record entirely.

## From two methods to four

If you have only used HTML forms, you know two HTTP methods:

```html
<form method="GET">   <!-- read / search -->
<form method="POST">  <!-- send data -->
```

That is all a classic `<form>` can do. To handle Update and Delete you
would need to add a parameter and branch on it:

```
POST /api/books.php?action=create
POST /api/books.php?action=update
POST /api/books.php?action=delete
```

It works, but the URL says nothing about the intention — the meaning is
hidden inside a parameter.

With `fetch` in JavaScript, you are no longer limited to GET and POST.
You can use all four HTTP methods:

```
POST   /api/books.php   → create
GET    /api/books.php   → read
PUT    /api/books.php   → update
DELETE /api/books.php   → delete
```

Same URL, different verb. The method *is* the intention — no extra
parameters, no ambiguity. Anyone reading the code knows immediately
what the request does.

This is the key shift: in a classic website, the browser navigates
to a new page on every action. In a single-page application (SPA),
JavaScript talks directly to the API via `fetch`, sends the right
HTTP method, and updates only the part of the page that changed —
without a full reload.

```javascript
// The browser can only do GET and POST.
// With fetch, you choose freely:
await fetch('/api/books.php', {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ book_id: 3 })
});
```

That is why Bibliotheca has no traditional `<form>` elements that
submit to a new page. Every form is intercepted by JavaScript, which
calls `fetch` with the appropriate method, and the page never reloads.

## The complete flow

**Create:**
1. User fills the form and clicks Save.
2. JavaScript sends a POST with JSON body.
3. Controller validates, calls Model's `insert`.
4. Model executes the INSERT via PDO.
5. Controller returns the new record as JSON.
6. JavaScript redirects to the list page.

**Read:**
1. JavaScript calls `fetch` with GET.
2. Controller calls Model's `getAll` or `getById`.
3. Model queries via PDO, returns array.
4. Controller encodes as JSON.
5. JavaScript builds the table.

**Update:**
1. User clicks Edit — goes to the form page with `?id=`.
2. JavaScript loads the record via GET.
3. User modifies and clicks Save.
4. JavaScript sends a PUT with JSON body.
5. Controller validates, calls Model's `update`.
6. JavaScript redirects to the list page.

**Delete:**
1. User clicks Delete — confirmation dialog appears.
2. JavaScript sends a DELETE with the record ID.
3. Controller calls Model's `delete` (`DELETE FROM` — permanent).
4. JavaScript reloads the list.

## Next

[Chapter 07 — Validation](07_validation.md)
