# Chapter 10 — Security

## What we already have

Previous chapters covered the essentials:

- **SQL injection** — prepared statements with named parameters (Chapter 04).
- **XSS** — `htmlspecialchars()` in PHP, `textContent` in JavaScript (Chapter 07).
- **CSRF** — session-based tokens on every state-changing request (Chapter 06).
- **Input validation** — server-side checks on every field (Chapter 07).

These are non-negotiable. This chapter covers the next layer: what
happens *around* your code.

## Session fixation

A session fixation attack works like this: the attacker gives you
a session ID they already know (via a crafted URL or a cookie), then
waits for you to log in. Now they share your authenticated session.

The defense is simple: regenerate the session ID after any privilege
change.

```php
session_start();
session_regenerate_id(true);  // true = delete the old session file
```

The `true` parameter is important. Without it, the old session file
survives and the attacker's ID still works.

In Bibliotheca we do not have authentication, but the principle
applies to the CSRF session. Our `Csrf::start()` already calls
`session_start()` with secure cookie settings:

```php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);
```

- `cookie_httponly` — JavaScript cannot read the session cookie.
  This blocks XSS attacks that try to steal session IDs.
- `cookie_samesite` — the browser will not send the cookie with
  requests from other sites. This is a second layer of CSRF defense.

If you add authentication later, call `session_regenerate_id(true)`
immediately after a successful login.

## Security headers

HTTP headers tell the browser how to behave. Without them, the
browser uses permissive defaults that attackers can exploit.

Add these headers in your front controller or in Apache configuration:

```php
// Prevent MIME-type sniffing — browser trusts the Content-Type header
header('X-Content-Type-Options: nosniff');

// Deny embedding in iframes — blocks clickjacking attacks
header('X-Frame-Options: DENY');

// Only send the origin as referrer, not the full URL with parameters
header('Referrer-Policy: strict-origin-when-cross-origin');

// Control what browser features the page can use
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
```

### Content-Security-Policy

CSP is the most powerful security header. It tells the browser
exactly which resources are allowed to load:

```php
header("Content-Security-Policy: default-src 'self'; "
     . "style-src 'self' https://cdnjs.cloudflare.com; "
     . "font-src https://cdnjs.cloudflare.com; "
     . "script-src 'self'");
```

What this means:

- `default-src 'self'` — by default, only load resources from our
  own domain.
- `style-src 'self' https://cdnjs.cloudflare.com` — CSS from us
  and from the Font Awesome CDN.
- `font-src https://cdnjs.cloudflare.com` — fonts only from Font
  Awesome.
- `script-src 'self'` — JavaScript only from our own domain. No
  inline scripts, no external scripts. If an attacker injects a
  `<script>` tag, the browser refuses to execute it.

CSP does not replace `htmlspecialchars()`. It is a safety net — if
your escaping has a gap, CSP catches the fall.

## Rate limiting

Without rate limiting, anyone can hammer your API with thousands
of requests per second. This can exhaust your server or brute-force
data through your endpoints.

A simple approach for a small application: count requests per IP
in the session.

```php
function checkRateLimit(int $maxRequests = 60, int $windowSeconds = 60): void
{
    $now = time();
    $key = 'rate_limit';

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }

    if ($now - $_SESSION[$key]['start'] > $windowSeconds) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }

    $_SESSION[$key]['count']++;

    if ($_SESSION[$key]['count'] > $maxRequests) {
        http_response_code(429);
        header('Retry-After: ' . $windowSeconds);
        echo json_encode(['error' => 'Too many requests']);
        exit;
    }
}
```

HTTP 429 (Too Many Requests) is the correct status code. The
`Retry-After` header tells the client how long to wait.

For production systems, rate limiting belongs in Apache
(`mod_ratelimit`), Nginx, or a reverse proxy — not in PHP.
But understanding the mechanism at the application level is
what matters for learning.

## HTTPS

Everything above is pointless if the connection is not encrypted.
Without HTTPS:

- Anyone on the network can read your session cookies.
- Anyone can modify the HTML in transit and inject scripts.
- Security headers can be stripped by a man-in-the-middle.

In development on localhost, HTTP is fine. In production, HTTPS is
mandatory. There are no exceptions.

With Let's Encrypt and `certbot`, there is no excuse not to have it:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache
```

## The layers

Security is not one thing. It is layers, each catching what the
previous one missed:

| Layer              | Protects against                |
|--------------------|---------------------------------|
| Prepared statements | SQL injection                  |
| Output escaping    | XSS                             |
| CSRF tokens        | Cross-site request forgery      |
| Input validation   | Bad data, out-of-range values   |
| Session management | Session fixation, session theft |
| Security headers   | Clickjacking, MIME sniffing, CSP bypass |
| Rate limiting      | Brute force, denial of service  |
| HTTPS              | Eavesdropping, tampering        |

No single layer is sufficient. Together, they make an application
that is genuinely hard to attack.

## The rule

Security is not a feature you add at the end. It is a property of
every line of code you write, every header you set, every default
you accept or override. The attacker only needs one gap. You need
them all closed.

## Next

[The Apocryphal Chapter — Beyond the Launch](apocrypha.md)

