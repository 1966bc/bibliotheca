<?php

/**
 * Author REST API endpoint.
 *
 * Handles HTTP methods:
 *   GET    — List all authors, or one by ID (?id=N)
 *   POST   — Create a new author (body: {first_name, last_name, birthdate?})
 *   PUT    — Update an author (body: {author_id, first_name, last_name, birthdate?, status})
 *   DELETE — Hard-delete an author (body: {author_id})
 *
 * Responses are JSON with appropriate HTTP status codes:
 *   200 OK, 400 Bad Request, 404 Not Found, 405 Method Not Allowed, 409 Conflict
 *
 * Business rules:
 *   - Names are normalized with ucwords(strtolower()) before storage
 *   - Duplicate full names (case-insensitive) are rejected with 409
 *   - Cannot disable an author that has active books (409)
 *   - Cannot delete an author that has any books at all (409)
 *
 * @see Author The model class used for database operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Author.php';
require_once __DIR__ . '/../../src/Csrf.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
    $author = new Author($db);

    $method = $_SERVER['REQUEST_METHOD'];

    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        Csrf::start();
        Csrf::verify();
    }

    if ($method === 'GET') {

        if (isset($_GET['id'])) {
            $row = $author->getById((int) $_GET['id']);

            if ($row) {
                echo json_encode($row);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
        } elseif (isset($_GET['active'])) {
            echo json_encode($author->getActive());
        } else {
            echo json_encode($author->getAll());
        }

    } elseif ($method === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);
        $firstName = mb_substr(ucwords(strtolower(trim(strip_tags($data['first_name'] ?? '')))), 0, 100);
        $lastName = mb_substr(ucwords(strtolower(trim(strip_tags($data['last_name'] ?? '')))), 0, 100);
        $birthdate = trim($data['birthdate'] ?? '') ?: null;

        if ($firstName === '' || $lastName === '') {
            http_response_code(400);
            echo json_encode(['error' => 'First name and last name are required']);
            exit;
        }

        if ($author->exists($firstName, $lastName)) {
            http_response_code(409);
            echo json_encode(['error' => 'Author already exists']);
            exit;
        }

        $id = $author->insert($firstName, $lastName, $birthdate);
        echo json_encode(['author_id' => $id, 'first_name' => $firstName, 'last_name' => $lastName]);

    } elseif ($method === 'PUT') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['author_id'] ?? 0);
        $firstName = mb_substr(ucwords(strtolower(trim(strip_tags($data['first_name'] ?? '')))), 0, 100);
        $lastName = mb_substr(ucwords(strtolower(trim(strip_tags($data['last_name'] ?? '')))), 0, 100);
        $birthdate = trim($data['birthdate'] ?? '') ?: null;
        $status = (int) ($data['status'] ?? 1);

        if ($id === 0 || $firstName === '' || $lastName === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ID, first name and last name are required']);
            exit;
        }

        if ($author->exists($firstName, $lastName, $id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Author already exists']);
            exit;
        }

        if ($status === 0 && $author->hasBooks($id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Cannot disable: author has associated books']);
            exit;
        }

        $author->update($id, $firstName, $lastName, $birthdate, $status);
        echo json_encode(['author_id' => $id, 'first_name' => $firstName, 'last_name' => $lastName, 'status' => $status]);

    } elseif ($method === 'DELETE') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['author_id'] ?? 0);

        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        if ($author->hasBooks($id, false)) {
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete: author has associated books']);
            exit;
        }

        $author->delete($id);
        echo json_encode(['deleted' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
