<?php

/**
 * CSRF token management — generate and verify tokens using PHP sessions.
 *
 * The token is generated once per session and stored in $_SESSION.
 * Pages inject it via a <meta> tag; JavaScript reads it and sends it
 * as an X-CSRF-Token header on state-changing requests (POST/PUT/DELETE).
 * API endpoints call verify() before processing writes.
 *
 * Usage in pages (index.php):
 *   Csrf::start();
 *   <meta name="csrf-token" content="<?= Csrf::token() ?>">
 *
 * Usage in API endpoints:
 *   Csrf::start();
 *   if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
 *       Csrf::verify();  // exits with 403 on failure
 *   }
 */

declare(strict_types=1);

class Csrf
{
    /**
     * Start a session (if not already started) with secure cookie settings.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => true,
            ]);
        }
    }

    /**
     * Get the CSRF token, generating one if it does not exist yet.
     *
     * @return string The 64-character hex token
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the CSRF token from the X-CSRF-Token request header.
     * On failure, sends a 403 response and exits.
     */
    public static function verify(): void
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!hash_equals(self::token(), $header)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }
}
