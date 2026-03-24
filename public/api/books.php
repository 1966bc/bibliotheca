<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Book.php';

header('Content-Type: application/json; charset=utf-8');

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
    $title = trim($data['title'] ?? '');
    $pages = isset($data['pages']) && $data['pages'] !== '' ? (int) $data['pages'] : null;
    $published = isset($data['published']) && $data['published'] !== '' ? (int) $data['published'] : null;

    if ($publisherId === 0 || $categoryId === 0 || $title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Publisher, category and title are required']);
        exit;
    }

    $id = $book->insert($publisherId, $categoryId, $title, $pages, $published);
    echo json_encode(['book_id' => $id, 'title' => $title]);

} elseif ($method === 'PUT') {

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['book_id'] ?? 0);
    $publisherId = (int) ($data['publisher_id'] ?? 0);
    $categoryId = (int) ($data['category_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $pages = isset($data['pages']) && $data['pages'] !== '' ? (int) $data['pages'] : null;
    $published = isset($data['published']) && $data['published'] !== '' ? (int) $data['published'] : null;

    if ($id === 0 || $publisherId === 0 || $categoryId === 0 || $title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'ID, publisher, category and title are required']);
        exit;
    }

    $book->update($id, $publisherId, $categoryId, $title, $pages, $published);
    echo json_encode(['book_id' => $id, 'title' => $title]);

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
