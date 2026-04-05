# Chapter 07 — Validation

## Three lines of defense

Validation is not optional. It is part of the craft.

1. **HTML** — we set constraints directly on the form fields:
   `required`, `maxlength`, `type="number"`, `min`, `max`.
   The browser enforces these before any code runs. We add
   `novalidate` to the `<form>` tag so the browser does not
   show its own popups — we want to control the error messages
   ourselves.

2. **JavaScript** — we delegate the interactive checks to the
   `validate()` method, which runs before sending the request.
   Inline error messages appear under each field, red borders
   highlight invalid inputs. This is for the user experience:
   fast feedback, no round-trip to the server.

3. **PHP** — we delegate the final check to the server. Never
   trust the client. The server validates everything again,
   because anyone can bypass JavaScript with a single line in
   the browser console.

Each layer validates as if it were the only one. HTML does not
know JavaScript exists. JavaScript does not assume the server
will catch what it missed. The server does not trust anything
that came before it. They each see the data from their own
point of view, and they each reject bad input independently.
When you write the validation for one layer, forget the others
exist. Do not think "HTML already checks this" or "the server
will catch it anyway". Each layer must stand on its own and do its job to the
best of its capabilities.

## What we validate in our project

- **Required fields** — cannot be empty after trimming.
- **String length** — names capped at 100 characters, titles at 255.
- **Numeric ranges** — pages between 1 and 99999, published year
  from 1000 to the current year.
- **Date format and range** — author birthdate must be a valid
  `YYYY-MM-DD`, year >= 1000, not in the future.
- **Duplicates** — server-side check with the model's `exists()`
  method. Returns HTTP 409 (Conflict) if the record already exists.
- **Dependencies** — returns HTTP 422 (Unprocessable Entity) if the
  record has linked books and cannot be deleted or disabled.
- **Input filtering** — numeric fields only accept digit keys.

## Input normalization

Before data reaches the database, the server normalizes it.
This is not cosmetic — it is data entry control. If you let
"eINAUDI", " Einaudi ", and "einaudi" into the database, you
have three records for the same publisher. Same with numbers: 
if a field expects an integer (like pages or published year), 
then only digits should be allowed. 
Not letters, not decimals, not special characters. 
The type of number matters: an integer is not a float, a year is not a price. 
Look at the column type in the database table:
if it says `INTEGER`, the input field should only accept
digits. A winning strategy: define what you expect based on
where the data will be stored, and reject everything else at
the point of entry.

- `trim()` — no leading or trailing spaces.
- `ucwords(strtolower())` — "eINAUDI" becomes "Einaudi".

The user types whatever they want. The server stores it in a
consistent format, every time.

## Sanitization

Formatting makes input look right. Sanitization makes it *safe*.

Every string that enters the API goes through three filters before
anything else happens:

```php
$name = mb_substr(strip_tags(trim($data['name'] ?? '')), 0, 100);
```

- **`trim()`** — removes leading and trailing whitespace.
- **`strip_tags()`** — removes any HTML or PHP tags. A name like
  `<script>alert('xss')</script>` becomes `alert('xss')`. The
  dangerous part is gone before the value reaches the database.
- **`mb_substr()`** — enforces a maximum length. Even if someone
  sends a megabyte of text, only the first 100 characters survive.

The order matters: trim first, strip second, truncate last.
And this happens on the server — never trust the client's
`maxlength` attribute alone.

## Error display: how do we tell the user something is wrong?

No `alert()`. We use inline messages:

```html
<span class="error" id="publisher-name-error"></span>
```

Each input field has a matching `<span>` for its error message.
The JavaScript class has two methods for this:

```javascript
showError(fieldId, message) {
    const input = document.querySelector('#' + fieldId);
    const error = document.querySelector(
        '#' + fieldId + '-error'
    );
    input.classList.add('invalid');
    error.textContent = message;
}
```

```javascript
clearErrors() {
    const errors = document.querySelectorAll('.error');
    for (let i = 0; i < errors.length; i++) {
        errors[i].textContent = '';
    }
    const invalids = document.querySelectorAll('.invalid');
    for (let i = 0; i < invalids.length; i++) {
        invalids[i].classList.remove('invalid');
    }
}
```

`showError` marks one field as invalid and shows the message.
`clearErrors` resets all fields before a new validation pass.
The `validate()` method calls `clearErrors` first, then checks
each field and calls `showError` for every problem it finds.

## Server errors

When the server rejects the input (409 for duplicates, 422 for
dependencies, 400 for bad data), JavaScript reads the response
and shows the message inline — same place, same pattern:

```javascript
if (!response.ok) {
    const result = await response.json();
    this.showError('publisher-name', result.error);
    return;
}
```

Whether the error comes from client-side validation or from the
server, the user sees it in the same way.

## Exceptions: who catches what

Validation handles *expected* problems — empty fields, duplicates.
But what about *unexpected* failures? The database file is
corrupted. The disk is full. A table was dropped. These are
exceptions.

In PHP, PDO is configured with `ERRMODE_EXCEPTION`: when
something goes wrong, it throws a `PDOException`. The question
is: who catches it?

**Not DBMS.** The database wrapper does not know *how* to handle
the error. Should it return null? An empty array? Log something?
It depends on who is calling. A web API needs a JSON response.
A CLI script needs a console message. A test needs an assertion
failure. DBMS cannot know any of this, so it lets the exception
rise.

**Not the Model.** Publisher, Book, Author — they have the same
problem. They do not know the context. They pass the exception
upward.

**The controller catches it.** The API file is the only place
that knows it must respond with JSON and an HTTP status code:

```php
try {
    $db = new DBMS(
        __DIR__ . '/../../sql/bibliotheca.db'
    );
    $publisher = new Publisher($db);
    // ... all the logic ...
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
```

**JavaScript catches too.** Network failures (server down,
timeout) throw exceptions at the `fetch` level:

```javascript
try {
    const response = await fetch(this.API);
    if (!response.ok) {
        throw new Error(response.statusText);
    }
    const data = await response.json();
    this.render(data);
} catch (error) {
    alert('Unable to load data.');
}
```

The principle: **catch where you can act.** The lower layers
report the problem (by throwing). The upper layers handle it
(by catching). An exception is a signal that travels upward —
like pain traveling from an organ to the brain. The kidney does
not decide how to inform the patient. The brain does.

## Next

[Chapter 08 — Permissions](08_permissions.md)
