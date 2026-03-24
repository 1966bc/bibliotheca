# Chapter 06 — CRUD

## The four operations

CRUD stands for Create, Read, Update, Delete. Every data-driven
application does these four things. Nothing more, nothing less.

| Operation | HTTP Method | SQL       | Example                     |
|-----------|-------------|-----------|-----------------------------|
| Create    | POST        | INSERT    | Add a new publisher         |
| Read      | GET         | SELECT    | List all publishers         |
| Update    | PUT         | UPDATE    | Change a publisher's name   |
| Delete    | DELETE       | UPDATE*   | Deactivate a publisher      |

*We use soft delete — `UPDATE status = 0` instead of `DELETE FROM`.
The record stays in the database but disappears from the application.

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
3. Controller calls Model's `delete` (sets status = 0).
4. JavaScript reloads the list.

## Next

[Chapter 07 — Validation](07_validation.md)
