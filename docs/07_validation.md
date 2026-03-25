# Chapter 07 — Validation

## Three lines of defense

Validation is not optional. It is part of the craft.

1. **HTML** — `required`, `maxlength`, `type="number"`, `min`, `max`.
   The browser provides the first filter. We add `novalidate` to the
   form to disable browser popups and handle everything ourselves.

2. **JavaScript** — checks before sending the request. Inline error
   messages under each field, red borders on invalid inputs. This is
   for the user experience.

3. **PHP** — the final wall. Never trust the client. The server
   validates everything again, because anyone can bypass JavaScript.

## What we validate

- **Required fields** — cannot be empty.
- **String length** — `maxlength="100"` on text inputs.
- **Numeric ranges** — pages between 1 and 99999, published year
  from 1450 (because print wasn't invented yet) to current year + 1.
- **Date ranges** — author birthdate between 1000 and current year.
- **Duplicates** — server-side check with `exists()` method.
  Returns HTTP 409 (Conflict) if the record already exists.
- **Input filtering** — numeric fields only accept digit keys.

## Formatting

The server normalizes input before saving:

- `ucwords(strtolower())` — "eINAUDI" becomes "Einaudi".
- `trim()` — no leading or trailing spaces.

The user types whatever they want. The server stores it correctly.

## Sanitization

Formatting makes input look right. Sanitization makes it *safe*.

Every string that enters the API goes through three filters before
anything else happens:

```php
$name = mb_substr(strip_tags(trim($data['name'] ?? '')), 0, 100);
```

- **`strip_tags()`** — removes any HTML or PHP tags. A name like
  `<script>alert('xss')</script>` becomes `alert('xss')`. The
  dangerous part is gone before the value reaches the database.
- **`trim()`** — removes leading and trailing whitespace.
- **`mb_substr()`** — enforces a maximum length. Names are capped
  at 100 characters, titles at 255. Even if someone sends a megabyte
  of text, only the first 100 characters survive.

The order matters: strip first, trim second, truncate last.
And this happens on the server — never trust the client's
`maxlength` attribute alone.

## Error display

No `alert()` — that is from the 1990s. We use inline messages:

```html
<span class="error" id="publisher-name-error"></span>
```

```javascript
showError(fieldId, message) {
    const input = document.querySelector('#' + fieldId);
    const error = document.querySelector('#' + fieldId + '-error');
    input.classList.add('invalid');
    error.textContent = message;
}
```

The error appears under the field. The field gets a red border.
Clear and immediate feedback.

## Server errors

When the server returns an error (e.g., 409 for duplicates),
JavaScript reads the response and shows the message inline:

```javascript
if (!response.ok) {
    const result = await response.json();
    this.showError('publisher-name', result.error);
    return;
}
```

Same pattern, same place, whether the error comes from the client
or the server.

## Exceptions: who catches what

Validation handles *expected* problems — empty fields, duplicates.
But what about *unexpected* failures? The database file is corrupted.
The disk is full. A table was dropped. These are exceptions.

In PHP, PDO is configured with `ERRMODE_EXCEPTION`: when something
goes wrong, it throws a `PDOException`. The question is: who catches it?

**Not DBMS.** The database wrapper does not know *how* to handle the error.
Should it return null? An empty array? Log something? It depends on
who is calling. A web API needs a JSON response. A CLI script needs
a console message. A test needs an assertion failure. DBMS cannot
know any of this, so it lets the exception rise.

**Not the Model.** Publisher, Book, Author — they have the same problem.
They do not know the context. They pass the exception upward.

**The API endpoint catches it.** This is the only place that knows
it must respond with JSON and an HTTP status code:

```php
try {
    $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
    $publisher = new Publisher($db);
    // ... all the logic ...
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
```

**JavaScript catches it too.** Network failures (server down, timeout)
throw exceptions at the `fetch()` level:

```javascript
try {
    const response = await fetch(this.API);
    if (!response.ok) {
        throw new Error(response.statusText);
    }
    const data = await response.json();
    this.render(data);
} catch (error) {
    // show a message to the user
}
```

The principle: **catch where you can act.** The lower layers report
the problem (by throwing). The upper layers handle it (by catching).
An exception is a signal that travels upward — like pain traveling
from an organ to the brain. The kidney does not decide how to inform
the patient. The brain does.

## Next

[Chapter 08 — Permissions](08_permissions.md)
