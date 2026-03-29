<?php

/**
 * Publisher REST API endpoint.
 *
 * Handles HTTP methods:
 *   GET    — List all publishers, active only (?active=1), or one by ID (?id=N)
 *   POST   — Create a new publisher (body: {name})
 *   PUT    — Update a publisher (body: {publisher_id, name, status})
 *   DELETE — Hard-delete a publisher (body: {publisher_id})
 *
 * Responses are JSON with appropriate HTTP status codes:
 *   200 OK, 400 Bad Request, 404 Not Found, 405 Method Not Allowed, 409 Conflict,
 *   422 Unprocessable Entity
 *
 * Business rules:
 *   - Names are normalized with ucwords(strtolower()) before storage
 *   - Duplicate names (case-insensitive) are rejected with 409
 *   - Cannot disable a publisher that has active books (422)
 *   - Cannot delete a publisher that has any books at all (422)
 *
 * @see Publisher The model class used for database operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Publisher.php';
require_once __DIR__ . '/../../src/Csrf.php';
require_once __DIR__ . '/../../src/Auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
    $publisher = new Publisher($db);

    $method = $_SERVER['REQUEST_METHOD'];

    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        Csrf::start();
        Csrf::verify();
        Auth::require();
    }

    if ($method === 'GET') {

        if (isset($_GET['id'])) {
            $row = $publisher->getById((int) $_GET['id']);

            if ($row) {
                echo json_encode($row);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
        } elseif (isset($_GET['active'])) {
            echo json_encode($publisher->getActive());
        } else {
            echo json_encode($publisher->getAll());
        }

    } elseif ($method === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);
        $name = mb_substr(ucwords(strtolower(trim(strip_tags($data['name'] ?? '')))), 0, 100);

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        if ($publisher->exists($name)) {
            http_response_code(409);
            echo json_encode(['error' => 'Publisher already exists']);
            exit;
        }

        $id = $publisher->insert($name);
        echo json_encode(['publisher_id' => $id, 'name' => $name]);

    } elseif ($method === 'PUT') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['publisher_id'] ?? 0);
        $name = mb_substr(ucwords(strtolower(trim(strip_tags($data['name'] ?? '')))), 0, 100);
        $status = (int) ($data['status'] ?? 1);

        if ($id === 0 || $name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ID and name are required']);
            exit;
        }

        if ($publisher->exists($name, $id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Publisher already exists']);
            exit;
        }

        if ($status === 0 && $publisher->hasBooks($id)) {
            http_response_code(422);
            echo json_encode(['error' => 'Cannot disable: publisher has associated books']);
            exit;
        }

        $publisher->update($id, $name, $status);
        echo json_encode(['publisher_id' => $id, 'name' => $name, 'status' => $status]);

    } elseif ($method === 'DELETE') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['publisher_id'] ?? 0);

        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        if ($publisher->hasBooks($id, false)) {
            http_response_code(422);
            echo json_encode(['error' => 'Cannot delete: publisher has associated books']);
            exit;
        }

        $publisher->delete($id);
        echo json_encode(['deleted' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
