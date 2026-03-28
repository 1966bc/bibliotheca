<?php

/**
 * Front controller — single entry point for all page requests.
 *
 * All URLs are rewritten to this file by .htaccess. The `?route=` GET parameter
 * determines which page template to load from `pages/`.
 *
 * Flow:
 *   1. Extract and sanitize the route parameter
 *   2. Validate against the allowed routes whitelist
 *   3. Load the corresponding page template inside the HTML layout
 *
 * If the route is not in the whitelist or the file does not exist,
 * a 404 error page is served.
 *
 * @see .htaccess URL rewriting rules
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Csrf.php';
Csrf::start();

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
    'about',
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
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token()) ?>">
    <link rel="icon" type="image/svg+xml" href="/bibliotheca/public/favicon.svg">
    <title>Bibliotheca</title>
    <link rel="stylesheet" href="/bibliotheca/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <a href="/bibliotheca/public/" class="logo">Bibliotheca</a>
        <nav>
            <a href="/bibliotheca/public/">Home</a>
            <a href="/bibliotheca/public/publishers">Publishers</a>
            <a href="/bibliotheca/public/categories">Categories</a>
            <a href="/bibliotheca/public/authors">Authors</a>
            <a href="/bibliotheca/public/about" title="About"><i class="fas fa-circle-info"></i></a>
        </nav>
    </header>
    <main>
        <?php require $page; ?>
    </main>
    <footer>
        <p>Bibliotheca — A didactic project — Copyleft 2026<?= date('Y') !== '2026' ? '-' . date('Y') : '' ?></p>
    </footer>
</body>
</html>
