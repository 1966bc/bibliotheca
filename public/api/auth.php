<?php

/**
 * Authentication REST API endpoint.
 *
 * Handles HTTP methods:
 *   GET    — Check current authentication status
 *   POST   — Log in (body: {username, password})
 *   DELETE — Log out
 *
 * Responses are JSON with appropriate HTTP status codes:
 *   200 OK, 400 Bad Request, 401 Unauthorized, 405 Method Not Allowed
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Csrf.php';

header('Content-Type: application/json; charset=utf-8');

try {
    Csrf::start();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {

        echo json_encode(['authenticated' => Auth::check()]);

    } elseif ($method === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            exit;
        }

        $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');

        if (Auth::login($db, $username, $password)) {
            echo json_encode(['authenticated' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }

    } elseif ($method === 'DELETE') {

        Auth::logout();
        echo json_encode(['authenticated' => false]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
