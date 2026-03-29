<?php

/**
 * Authentication — single admin user login via session.
 *
 * Passwords are stored using password_hash() (bcrypt by default).
 * After a successful login the session is regenerated to prevent
 * session fixation attacks.
 *
 * Usage:
 *   Auth::login($db, $username, $password);  // returns true/false
 *   Auth::logout();
 *   Auth::check();                           // returns true if logged in
 */

declare(strict_types=1);

class Auth
{
    /**
     * Attempt to log in with the given credentials.
     *
     * @param  DBMS   $db       Database connection
     * @param  string $username The submitted username
     * @param  string $password The submitted plaintext password
     * @return bool True if login succeeded
     */
    public static function login(DBMS $db, string $username, string $password): bool
    {
        $row = $db->fetchOne(
            "SELECT user_id, password FROM user WHERE username = :username",
            [':username' => $username]
        );

        if ($row === null || !password_verify($password, $row['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['user_id'];

        return true;
    }

    /**
     * Log out the current user by clearing the session.
     */
    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    /**
     * Check if the current session is authenticated.
     *
     * @return bool True if logged in
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require authentication for the current request.
     * Sends 401 and exits if not logged in.
     */
    public static function require(): void
    {
        if (!self::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
}
