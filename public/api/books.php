<?php

/**
 * Book REST API endpoint.
 *
 * Handles HTTP methods:
 *   GET    — List all books (with authors), or one by ID (?id=N) with authors array
 *   POST   — Create a new book (body: {publisher_id, category_id, title, pages?, published?})
 *   PUT    — Update a book (body: {book_id, publisher_id, category_id, title, pages?, published?, status})
 *   DELETE — Hard-delete a book and its book_author records (body: {book_id})
 *
 * Responses are JSON with appropriate HTTP status codes:
 *   200 OK, 400 Bad Request, 404 Not Found, 405 Method Not Allowed
 *
 * Business rules:
 *   - Title, publisher_id, and category_id are required for create/update
 *   - Pages and published year are optional (nullable integers)
 *   - Deletion cascades to the book_author junction table
 *
 * @see Book The model class used for database operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Book.php';
require_once __DIR__ . '/../../src/Author.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
    $book = new Book($db);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {

        if (isset($_GET['id'])) {
            $row = $book->getById((int) $_GET['id']);

            if ($row) {
                $row['authors'] = $book->getAuthors((int) $_GET['id']);
                echo json_encode($row);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
        } else {
            echo json_encode($book->getAll());
        }

    } elseif ($method === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);
        $publisherId = (int) ($data['publisher_id'] ?? 0);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $title = mb_substr(trim(strip_tags($data['title'] ?? '')), 0, 255);
        $pages = isset($data['pages']) && $data['pages'] !== '' ? (int) $data['pages'] : null;
        $published = isset($data['published']) && $data['published'] !== '' ? (int) $data['published'] : null;

        if ($publisherId === 0 || $categoryId === 0 || $title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Publisher, category and title are required']);
            exit;
        }

        $id = $book->insert($publisherId, $categoryId, $title, $pages, $published);

        if (!empty($data['author_ids'])) {
            $book->setAuthors($id, $data['author_ids']);
        }

        echo json_encode(['book_id' => $id, 'title' => $title]);

    } elseif ($method === 'PUT') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['book_id'] ?? 0);
        $publisherId = (int) ($data['publisher_id'] ?? 0);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $title = mb_substr(trim(strip_tags($data['title'] ?? '')), 0, 255);
        $pages = isset($data['pages']) && $data['pages'] !== '' ? (int) $data['pages'] : null;
        $published = isset($data['published']) && $data['published'] !== '' ? (int) $data['published'] : null;
        $status = (int) ($data['status'] ?? 1);

        if ($id === 0 || $publisherId === 0 || $categoryId === 0 || $title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ID, publisher, category and title are required']);
            exit;
        }

        $book->update($id, $publisherId, $categoryId, $title, $pages, $published, $status);

        if (isset($data['author_ids'])) {
            $book->setAuthors($id, $data['author_ids']);
        }

        echo json_encode(['book_id' => $id, 'title' => $title, 'status' => $status]);

    } elseif ($method === 'DELETE') {

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['book_id'] ?? 0);

        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        $book->delete($id);
        echo json_encode(['deleted' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
