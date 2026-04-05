# Chapter 10 — Security

## What we already have

Previous chapters covered the essentials:

- **SQL injection** — prepared statements with named parameters (Chapter 04).
- **XSS** — `htmlspecialchars()` in PHP, `textContent` in JavaScript (Chapter 07).
- **Input validation** — server-side checks on every field (Chapter 07).
- **File permissions** — keeping sensitive files outside the web root (Chapter 08).

These are non-negotiable. They are part of the code you have
already written. This chapter adds the layers that protect
*around* your code.

## CSRF — protecting write operations

In Chapter 05 we saw that JavaScript sends an `X-CSRF-Token`
header with every POST, PUT, and DELETE request. But what is
CSRF, and why do we need that token?

### The attack

Imagine you have Bibliotheca open in your browser. A malicious
website in another tab contains hidden JavaScript that sends a
POST request to your `api/publishers.php` with
`{"name": "HACKED"}`. The browser sends it — from its
perspective, it is just another HTTP request to localhost, and
it attaches your session cookie automatically.

This is **CSRF** (Cross-Site Request Forgery): a malicious site
tricks the browser into making requests to *your* application,
using *your* session.

### The defense: a secret token

The solution is a **CSRF token**: a long random string that
only our pages know. The malicious site cannot read it because
the browser's **Same-Origin Policy** prevents one site from
reading another site's content.

The flow:

1. When PHP renders the page (`index.php`), it generates a
   random token and stores it in the session:
   ```php
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   ```

2. The token is injected into the HTML as a `<meta>` tag:
   ```html
   <meta name="csrf-token" content="a7f3b9c2e1d4...">
   ```

3. JavaScript reads the token and includes it in every
   POST, PUT, and DELETE request — the same header we saw
   in Chapter 05:
   ```javascript
   headers: {
       'Content-Type': 'application/json',
       'X-CSRF-Token': document.querySelector(
           'meta[name="csrf-token"]'
       ).content
   }
   ```

4. The controller (the API file) verifies the token before
   processing the request:
   ```php
   Csrf::verify();  // 403 if token is missing or wrong
   ```

The malicious site cannot read the `<meta>` tag from our page
(Same-Origin Policy), so it cannot send the correct token. The
controller rejects its request with `403 Forbidden`.

### The Csrf class

The `Csrf` class (`src/Csrf.php`) has three methods:

- **`Csrf::start()`** — starts a PHP session (with secure
  cookie settings).
- **`Csrf::token()`** — returns the token, generating one
  if needed.
- **`Csrf::verify()`** — compares the `X-CSRF-Token` header
  against the session; exits with 403 on mismatch.

GET requests do not need CSRF protection — they only read data,
they never modify it. Only POST, PUT, and DELETE are checked.

### Verify it works from the terminal

```bash
# Without token: 403
curl -s -o /dev/null -w "%{http_code}" \
     -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -d '{"name": "Test"}'
```

```bash
# With token: 200
TOKEN=$(curl -s -c /tmp/c.txt \
    http://localhost/bibliotheca/public/ \
    | grep csrf-token \
    | sed 's/.*content="\([^"]*\)".*/\1/')

curl -s -o /dev/null -w "%{http_code}" \
     -b /tmp/c.txt -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -H "X-CSRF-Token: $TOKEN" \
     -d '{"name": "Test"}'
```

The first request has no token — the server rejects it. The
second extracts the token from the page and sends it — the
server accepts it. Try both from your terminal.

## Sessions and session fixation

HTTP is stateless: each request is independent, the server does
not remember who you are. **Sessions** solve this. When you
visit the application, PHP creates a session — a small file on
the server identified by a random ID. The browser stores this
ID in a cookie and sends it with every request. That is how
the server knows you are still you.

When you log in, PHP writes your user ID into the session.
From that point, every request carries your identity.

A **session fixation** attack exploits this: the attacker gives
you a session ID they already know (via a crafted URL or a
cookie), then waits for you to log in. Now they share your
authenticated session.

The defense is simple: regenerate the session ID after any privilege
change.

```php
session_start();
session_regenerate_id(true);  // true = delete the old session file
```

The `true` parameter is important. Without it, the old session file
survives and the attacker's ID still works.

Bibliotheca calls `session_regenerate_id(true)` in `Auth::login()`
immediately after verifying credentials. Our `Csrf::start()`
calls `session_start()` with secure cookie settings:

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

### How it works

HTTPS uses TLS (Transport Layer Security) to encrypt the connection.
The server holds a **certificate** — a file that proves its identity.
When the browser connects, the server presents the certificate, they
negotiate encryption keys, and from that point every byte is encrypted.

The certificate contains the domain name, the public key, the issuer,
and an expiry date. It is signed by a **Certificate Authority** (CA)
that the browser trusts. If the signature does not match or the
certificate is expired, the browser shows a warning.

### Where it lives

On a Debian/Apache server, certificates typically live in
`/etc/letsencrypt/live/yourdomain/`. Two files matter:

- `fullchain.pem` — the certificate (public)
- `privkey.pem` — the private key (never share this)

Apache references them in the virtual host configuration:

```apache
SSLCertificateFile /etc/letsencrypt/live/yourdomain/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain/privkey.pem
```

### Getting a certificate

With Let's Encrypt and `certbot`, it takes two commands:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache
```

`certbot` obtains the certificate, configures Apache, and sets up
automatic renewal. The certificate expires every 90 days, but
`certbot` renews it automatically via a systemd timer.

## The layers

Security is not one thing. It is layers, each catching what the
previous one missed:

- **Prepared statements** → SQL injection
- **Output escaping** → XSS
- **CSRF tokens** → cross-site request forgery
- **Input validation** → bad data, out-of-range values
- **Session management** → session fixation, session theft
- **Security headers** → clickjacking, MIME sniffing, CSP bypass
- **Rate limiting** → brute force, denial of service
- **HTTPS** → eavesdropping, tampering

No single layer is sufficient. Together, they make an application
that is genuinely hard to attack.

## The rule

Security is not a feature you add at the end. It is a property
of every line of code you write, every header you set, every
default you accept or override.

Remember the principle from Chapter 07: each layer validates
as if it were the only one. The same applies here. Prepared
statements do not assume CSP will block the injection. CSRF
tokens do not assume HTTPS will stop the attacker. Each
defense stands on its own.

The attacker only needs one gap. You need them all closed.

## Next

[The Apocryphal Chapter — Beyond the Launch](apocrypha.md)

