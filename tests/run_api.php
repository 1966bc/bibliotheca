<?php

/**
 * API integration tests — test REST endpoints via HTTP.
 *
 * Execute from the project root:
 *     php tests/run_api.php
 *
 * Requirements:
 *   - Apache must be running with the site accessible at localhost
 *
 * These tests create and then delete their own data. Records created
 * during testing use the prefix "ZZZ Test" to avoid collisions.
 */

declare(strict_types=1);

require_once __DIR__ . '/TestRunner.php';

$BASE = 'http://localhost/bibliotheca/public';

// ---------------------------------------------------------------------------
// HTTP helper — makes requests and returns status + decoded JSON
// ---------------------------------------------------------------------------

/**
 * Fetch a CSRF token by loading the home page and extracting it
 * from the <meta name="csrf-token"> tag. Returns the token string
 * and the session cookie for subsequent requests.
 *
 * @param  string $baseUrl The application base URL
 * @return array{token: string, cookie: string}
 */
function getCsrfToken(string $baseUrl): array
{
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'follow_location' => true,
    ]]);

    $body = @file_get_contents($baseUrl . '/', false, $context);

    if ($body === false) {
        return ['token' => '', 'cookie' => ''];
    }

    // Extract session cookie from response headers ($http_response_header is set by file_get_contents)
    $cookie = '';
    foreach ($http_response_header as $header) {
        if (preg_match('/^Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $header, $m)) {
            $cookie = $m[1];
        }
    }

    // Extract CSRF token from meta tag
    $token = '';
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $body, $m)) {
        $token = $m[1];
    }

    return ['token' => $token, 'cookie' => $cookie];
}

/**
 * Make an HTTP request to an API endpoint.
 *
 * Uses file_get_contents with stream context (no curl dependency).
 *
 * @param  string      $method  HTTP method (GET, POST, PUT, DELETE)
 * @param  string      $url     Full URL
 * @param  array|null  $body    JSON body (for POST/PUT/DELETE)
 * @param  string      $token   CSRF token
 * @param  string      $cookie  Session cookie
 * @return array{status: int, body: array}
 */
function apiRequest(string $method, string $url, ?array $body, string $token = '', string $cookie = ''): array
{
    $headers = "Content-Type: application/json\r\n";

    if ($token !== '') {
        $headers .= "X-CSRF-Token: {$token}\r\n";
    }

    if ($cookie !== '') {
        $headers .= "Cookie: {$cookie}\r\n";
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => $headers,
            'ignore_errors' => true,
        ],
    ];

    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Extract status code from response headers
    $status = 0;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
        $status = (int) ($m[1] ?? 0);
    }

    // Extract any new session cookie
    $newCookie = '';
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('/^Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $h, $cm)) {
            $newCookie = $cm[1];
        }
    }

    $decoded = json_decode($response ?: '', true) ?? [];

    return ['status' => $status, 'body' => $decoded, 'cookie' => $newCookie];
}

// ---------------------------------------------------------------------------
// Setup — get CSRF token and session cookie
// ---------------------------------------------------------------------------

$csrf = getCsrfToken($BASE);

if ($csrf['token'] === '' || $csrf['cookie'] === '') {
    echo "\n  ERROR: Could not obtain CSRF token. Is Apache running?\n";
    echo "  Tried: {$BASE}/\n\n";
    exit(1);
}

$token  = $csrf['token'];
$cookie = $csrf['cookie'];

// Log in as admin before running tests
$API_AUTH = $BASE . '/api/auth.php';
$loginResult = apiRequest('POST', $API_AUTH, [
    'username' => 'admin',
    'password' => 'bibliotheca',
], $token, $cookie);

if ($loginResult['status'] !== 200) {
    echo "\n  ERROR: Could not log in as admin.\n";
    echo "  Response: " . json_encode($loginResult['body']) . "\n\n";
    exit(1);
}

// session_regenerate_id changes the cookie — use the new one
if ($loginResult['cookie'] !== '') {
    $cookie = $loginResult['cookie'];
}

$API_PUBLISHERS = $BASE . '/api/publishers.php';
$API_CATEGORIES = $BASE . '/api/categories.php';
$API_AUTHORS    = $BASE . '/api/authors.php';
$API_BOOKS      = $BASE . '/api/books.php';

$t = new TestRunner();

// ---------------------------------------------------------------------------
// Publisher API tests
// ---------------------------------------------------------------------------

$t->test('API Publishers: GET returns array', function () use ($t, $API_PUBLISHERS) {
    $r = apiRequest('GET', $API_PUBLISHERS, null);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue(is_array($r['body']));
});

$t->test('API Publishers: POST creates a publisher', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'ZZZ Test Publisher'], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertNotNull($r['body']['publisher_id'] ?? null);
    $GLOBALS['test_publisher_id'] = $r['body']['publisher_id'];
});

$t->test('API Publishers: POST normalizes name', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'zzz test normalized'], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('Zzz Test Normalized', $r['body']['name']);
    $GLOBALS['test_publisher_id_2'] = $r['body']['publisher_id'];
});

$t->test('API Publishers: POST rejects duplicate (409)', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'ZZZ Test Publisher'], $token, $cookie);
    $t->assertEqual(409, $r['status']);
});

$t->test('API Publishers: POST rejects empty name (400)', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => ''], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Publishers: GET by ID returns record', function () use ($t, $API_PUBLISHERS) {
    $id = $GLOBALS['test_publisher_id'];
    $r = apiRequest('GET', $API_PUBLISHERS . '?id=' . $id, null);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('Zzz Test Publisher', $r['body']['name']);
});

$t->test('API Publishers: GET by invalid ID returns 404', function () use ($t, $API_PUBLISHERS) {
    $r = apiRequest('GET', $API_PUBLISHERS . '?id=999999', null);
    $t->assertEqual(404, $r['status']);
});

$t->test('API Publishers: PUT updates a publisher', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $id = $GLOBALS['test_publisher_id'];
    $r = apiRequest('PUT', $API_PUBLISHERS, [
        'publisher_id' => $id,
        'name' => 'ZZZ Test Updated',
        'status' => 1,
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('Zzz Test Updated', $r['body']['name']);
});

$t->test('API Publishers: DELETE removes publisher', function () use ($t, $API_PUBLISHERS, $token, $cookie) {
    $id = $GLOBALS['test_publisher_id'];
    $r = apiRequest('DELETE', $API_PUBLISHERS, ['publisher_id' => $id], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue($r['body']['deleted']);

    // Clean up second test publisher
    $id2 = $GLOBALS['test_publisher_id_2'];
    apiRequest('DELETE', $API_PUBLISHERS, ['publisher_id' => $id2], $token, $cookie);
});

// ---------------------------------------------------------------------------
// Category API tests
// ---------------------------------------------------------------------------

$t->test('API Categories: GET returns array', function () use ($t, $API_CATEGORIES) {
    $r = apiRequest('GET', $API_CATEGORIES, null);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue(is_array($r['body']));
});

$t->test('API Categories: POST creates a category', function () use ($t, $API_CATEGORIES, $token, $cookie) {
    $r = apiRequest('POST', $API_CATEGORIES, ['name' => 'ZZZ Test Category'], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_category_id'] = $r['body']['category_id'];
});

$t->test('API Categories: POST rejects duplicate (409)', function () use ($t, $API_CATEGORIES, $token, $cookie) {
    $r = apiRequest('POST', $API_CATEGORIES, ['name' => 'ZZZ Test Category'], $token, $cookie);
    $t->assertEqual(409, $r['status']);
});

$t->test('API Categories: DELETE removes category', function () use ($t, $API_CATEGORIES, $token, $cookie) {
    $id = $GLOBALS['test_category_id'];
    $r = apiRequest('DELETE', $API_CATEGORIES, ['category_id' => $id], $token, $cookie);
    $t->assertEqual(200, $r['status']);
});

// ---------------------------------------------------------------------------
// Author API tests
// ---------------------------------------------------------------------------

$t->test('API Authors: GET returns array', function () use ($t, $API_AUTHORS) {
    $r = apiRequest('GET', $API_AUTHORS, null);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue(is_array($r['body']));
});

$t->test('API Authors: POST creates an author', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_AUTHORS, [
        'first_name' => 'ZZZ Test',
        'last_name' => 'Author',
        'birthdate' => '1990-01-15',
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_author_id'] = $r['body']['author_id'];
});

$t->test('API Authors: POST rejects duplicate (409)', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_AUTHORS, [
        'first_name' => 'ZZZ Test',
        'last_name' => 'Author',
    ], $token, $cookie);
    $t->assertEqual(409, $r['status']);
});

$t->test('API Authors: POST rejects missing last name (400)', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_AUTHORS, ['first_name' => 'ZZZ Test'], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Authors: POST rejects invalid birthdate (400)', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_AUTHORS, [
        'first_name' => 'ZZZ Test',
        'last_name' => 'BadDate',
        'birthdate' => '3000-01-01',
    ], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Authors: POST rejects malformed birthdate (400)', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_AUTHORS, [
        'first_name' => 'ZZZ Test',
        'last_name' => 'BadFormat',
        'birthdate' => 'not-a-date',
    ], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Authors: PUT updates an author', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $id = $GLOBALS['test_author_id'];
    $r = apiRequest('PUT', $API_AUTHORS, [
        'author_id' => $id,
        'first_name' => 'ZZZ Test',
        'last_name' => 'Updated',
        'birthdate' => '1990-06-20',
        'status' => 1,
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('Updated', $r['body']['last_name']);
});

$t->test('API Authors: DELETE removes author', function () use ($t, $API_AUTHORS, $token, $cookie) {
    $id = $GLOBALS['test_author_id'];
    $r = apiRequest('DELETE', $API_AUTHORS, ['author_id' => $id], $token, $cookie);
    $t->assertEqual(200, $r['status']);
});

// ---------------------------------------------------------------------------
// Book API tests
// ---------------------------------------------------------------------------

// Create supporting records for book tests
$t->test('API Books: setup — create publisher, category, author', function () use ($t, $API_PUBLISHERS, $API_CATEGORIES, $API_AUTHORS, $token, $cookie) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'ZZZ Test Book Publisher'], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_book_pub_id'] = $r['body']['publisher_id'];

    $r = apiRequest('POST', $API_CATEGORIES, ['name' => 'ZZZ Test Book Category'], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_book_cat_id'] = $r['body']['category_id'];

    $r = apiRequest('POST', $API_AUTHORS, [
        'first_name' => 'ZZZ Test',
        'last_name' => 'Book Author',
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_book_auth_id'] = $r['body']['author_id'];
});

$t->test('API Books: GET returns array', function () use ($t, $API_BOOKS) {
    $r = apiRequest('GET', $API_BOOKS, null);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue(is_array($r['body']));
});

$t->test('API Books: POST creates a book with authors', function () use ($t, $API_BOOKS, $token, $cookie) {
    $r = apiRequest('POST', $API_BOOKS, [
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => 'ZZZ Test Book Title',
        'pages' => 123,
        'published' => 2020,
        'author_ids' => [$GLOBALS['test_book_auth_id']],
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $GLOBALS['test_book_id'] = $r['body']['book_id'];
});

$t->test('API Books: POST rejects missing title (400)', function () use ($t, $API_BOOKS, $token, $cookie) {
    $r = apiRequest('POST', $API_BOOKS, [
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => '',
    ], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Books: POST rejects invalid published year (400)', function () use ($t, $API_BOOKS, $token, $cookie) {
    $r = apiRequest('POST', $API_BOOKS, [
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => 'ZZZ Test Future Book',
        'published' => 3000,
    ], $token, $cookie);
    $t->assertEqual(400, $r['status']);
});

$t->test('API Books: GET by ID returns book with authors', function () use ($t, $API_BOOKS) {
    $id = $GLOBALS['test_book_id'];
    $r = apiRequest('GET', $API_BOOKS . '?id=' . $id, null);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('ZZZ Test Book Title', $r['body']['title']);
    $t->assertTrue(is_array($r['body']['authors']));
    $t->assertCount(1, $r['body']['authors']);
});

$t->test('API Books: PUT updates a book', function () use ($t, $API_BOOKS, $token, $cookie) {
    $id = $GLOBALS['test_book_id'];
    $r = apiRequest('PUT', $API_BOOKS, [
        'book_id' => $id,
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => 'ZZZ Test Updated Title',
        'pages' => 456,
        'published' => 2024,
        'status' => 1,
        'author_ids' => [],
    ], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertEqual('ZZZ Test Updated Title', $r['body']['title']);
});

$t->test('API Books: DELETE removes book', function () use ($t, $API_BOOKS, $token, $cookie) {
    $id = $GLOBALS['test_book_id'];
    $r = apiRequest('DELETE', $API_BOOKS, ['book_id' => $id], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $t->assertTrue($r['body']['deleted']);
});

// ---------------------------------------------------------------------------
// Dependency tests (422)
// ---------------------------------------------------------------------------

$t->test('API Publishers: cannot delete publisher with books (422)', function () use ($t, $API_PUBLISHERS, $API_BOOKS, $token, $cookie) {
    // Create a book linked to the test publisher
    $r = apiRequest('POST', $API_BOOKS, [
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => 'ZZZ Test Dependency Book',
    ], $token, $cookie);
    $GLOBALS['test_dep_book_id'] = $r['body']['book_id'];

    // Try to delete the publisher
    $r = apiRequest('DELETE', $API_PUBLISHERS, [
        'publisher_id' => $GLOBALS['test_book_pub_id'],
    ], $token, $cookie);
    $t->assertEqual(422, $r['status']);
});

$t->test('API Authors: cannot disable author with books (422)', function () use ($t, $API_AUTHORS, $API_BOOKS, $token, $cookie) {
    // Link the author to the dependency book
    $bookId = $GLOBALS['test_dep_book_id'];
    $authId = $GLOBALS['test_book_auth_id'];
    apiRequest('PUT', $API_BOOKS, [
        'book_id' => $bookId,
        'publisher_id' => $GLOBALS['test_book_pub_id'],
        'category_id' => $GLOBALS['test_book_cat_id'],
        'title' => 'ZZZ Test Dependency Book',
        'status' => 1,
        'author_ids' => [$authId],
    ], $token, $cookie);

    // Try to disable the author
    $r = apiRequest('PUT', $API_AUTHORS, [
        'author_id' => $authId,
        'first_name' => 'Zzz Test',
        'last_name' => 'Book Author',
        'status' => 0,
    ], $token, $cookie);
    $t->assertEqual(422, $r['status']);
});

// ---------------------------------------------------------------------------
// CSRF tests
// ---------------------------------------------------------------------------

$t->test('API: POST without CSRF token is rejected', function () use ($t, $API_PUBLISHERS) {
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'ZZZ No Token'], '', '');
    $t->assertTrue($r['status'] >= 400);
});

$t->test('API: POST without login returns 401', function () use ($t, $API_PUBLISHERS, $BASE) {
    // Get a fresh session (not logged in) with its own CSRF token
    $fresh = getCsrfToken($BASE);
    $r = apiRequest('POST', $API_PUBLISHERS, ['name' => 'ZZZ No Auth'], $fresh['token'], $fresh['cookie']);
    $t->assertEqual(401, $r['status']);
});

// ---------------------------------------------------------------------------
// Method not allowed
// ---------------------------------------------------------------------------

$t->test('API: PATCH returns 405', function () use ($t, $API_PUBLISHERS) {
    $r = apiRequest('PATCH', $API_PUBLISHERS, ['name' => 'ZZZ Patch'], '', '');
    $t->assertEqual(405, $r['status']);
});

// ---------------------------------------------------------------------------
// Cleanup — remove all test data
// ---------------------------------------------------------------------------

$t->test('API: cleanup test data', function () use ($t, $API_BOOKS, $API_AUTHORS, $API_PUBLISHERS, $API_CATEGORIES, $token, $cookie) {
    // Delete the dependency book first
    apiRequest('DELETE', $API_BOOKS, ['book_id' => $GLOBALS['test_dep_book_id']], $token, $cookie);

    // Now delete author, publisher, category
    apiRequest('DELETE', $API_AUTHORS, ['author_id' => $GLOBALS['test_book_auth_id']], $token, $cookie);
    $r = apiRequest('DELETE', $API_PUBLISHERS, ['publisher_id' => $GLOBALS['test_book_pub_id']], $token, $cookie);
    $t->assertEqual(200, $r['status']);
    $r = apiRequest('DELETE', $API_CATEGORIES, ['category_id' => $GLOBALS['test_book_cat_id']], $token, $cookie);
    $t->assertEqual(200, $r['status']);
});

// ---------------------------------------------------------------------------
// Run all tests
// ---------------------------------------------------------------------------

$t->run();
