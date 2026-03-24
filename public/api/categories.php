<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/DBMS.php';
require_once __DIR__ . '/../../src/Category.php';

header('Content-Type: application/json; charset=utf-8');

$db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');
$category = new Category($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    if (isset($_GET['id'])) {
        $row = $category->getById((int) $_GET['id']);

        if ($row) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
    } else {
        echo json_encode($category->getAll());
    }

} elseif ($method === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $name = ucwords(strtolower(trim($data['name'] ?? '')));

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    if ($category->exists($name)) {
        http_response_code(409);
        echo json_encode(['error' => 'Category already exists']);
        exit;
    }

    $id = $category->insert($name);
    echo json_encode(['category_id' => $id, 'name' => $name]);

} elseif ($method === 'PUT') {

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['category_id'] ?? 0);
    $name = ucwords(strtolower(trim($data['name'] ?? '')));

    if ($id === 0 || $name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'ID and name are required']);
        exit;
    }

    if ($category->exists($name, $id)) {
        http_response_code(409);
        echo json_encode(['error' => 'Category already exists']);
        exit;
    }

    $category->update($id, $name);
    echo json_encode(['category_id' => $id, 'name' => $name]);

} elseif ($method === 'DELETE') {

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['category_id'] ?? 0);

    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    $category->delete($id);
    echo json_encode(['deleted' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
