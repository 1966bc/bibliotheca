<?php

declare(strict_types=1);

$route = $_GET['route'] ?? 'home';
$route = trim($route, '/');

if ($route === '') {
    $route = 'home';
}

$allowed = [
    'home',
    'publishers', 'publisher',
    'categories', 'category',
    'authors', 'author',
    'books', 'book',
];

if (!in_array($route, $allowed, true)) {
    http_response_code(404);
    $route = '404';
}

$page = __DIR__ . "/pages/{$route}.php";

if (!is_file($page)) {
    http_response_code(404);
    $page = __DIR__ . '/pages/404.php';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotheca</title>
    <link rel="stylesheet" href="/bibliotheca/public/css/style.css">
</head>
<body>
    <header>
        <h1><a href="/bibliotheca/public/">Bibliotheca</a></h1>
        <nav>
            <a href="/bibliotheca/public/">Home</a>
            <a href="/bibliotheca/public/publishers">Publishers</a>
            <a href="/bibliotheca/public/categories">Categories</a>
            <a href="/bibliotheca/public/authors">Authors</a>
            <a href="/bibliotheca/public/books">Books</a>
        </nav>
    </header>
    <main>
        <?php require $page; ?>
    </main>
    <footer>
        <p>Bibliotheca — A didactic project</p>
    </footer>
</body>
</html>
