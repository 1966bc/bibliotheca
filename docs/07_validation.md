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

## Next

[Chapter 08 — Permissions](08_permissions.md)
