<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Publisher.php';

header('Content-Type: application/json; charset=utf-8');

$db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
$publisher = new Publisher($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    if (isset($_GET['id'])) {
        $row = $publisher->getById((int) $_GET['id']);

        if ($row) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
    } else {
        echo json_encode($publisher->getAll());
    }

} elseif ($method === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $name = ucwords(strtolower(trim($data['name'] ?? '')));

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
    $name = ucwords(strtolower(trim($data['name'] ?? '')));

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

    $publisher->update($id, $name);
    echo json_encode(['publisher_id' => $id, 'name' => $name]);

} elseif ($method === 'DELETE') {

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['publisher_id'] ?? 0);

    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    if ($publisher->hasBooks($id)) {
        http_response_code(409);
        echo json_encode(['error' => 'Cannot delete: publisher has associated books']);
        exit;
    }

    $publisher->delete($id);
    echo json_encode(['deleted' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
