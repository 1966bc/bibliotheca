<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Author.php';

header('Content-Type: application/json; charset=utf-8');

$db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
$author = new Author($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    if (isset($_GET['id'])) {
        $row = $author->getById((int) $_GET['id']);

        if ($row) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
    } else {
        echo json_encode($author->getAll());
    }

} elseif ($method === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $firstName = ucwords(strtolower(trim($data['first_name'] ?? '')));
    $lastName = ucwords(strtolower(trim($data['last_name'] ?? '')));
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
    $firstName = ucwords(strtolower(trim($data['first_name'] ?? '')));
    $lastName = ucwords(strtolower(trim($data['last_name'] ?? '')));
    $birthdate = trim($data['birthdate'] ?? '') ?: null;

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

    $author->update($id, $firstName, $lastName, $birthdate);
    echo json_encode(['author_id' => $id, 'first_name' => $firstName, 'last_name' => $lastName]);

} elseif ($method === 'DELETE') {

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['author_id'] ?? 0);

    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    $author->delete($id);
    echo json_encode(['deleted' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
