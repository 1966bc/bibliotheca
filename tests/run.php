<?php

/**
 * Test entry point — run all unit tests.
 *
 * Execute from the project root:
 *     php tests/run.php
 *
 * Tests use a separate in-memory SQLite database so the real data is never touched.
 * The schema is recreated fresh for each test run, ensuring isolation.
 */

declare(strict_types=1);

require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../src/DBMS.php';
require_once __DIR__ . '/../src/Publisher.php';
require_once __DIR__ . '/../src/Category.php';
require_once __DIR__ . '/../src/Author.php';
require_once __DIR__ . '/../src/Book.php';

/**
 * Create an in-memory database with the full schema for testing.
 *
 * @return DBMS A fresh database connection with empty tables
 */
function createTestDb(): DBMS
{
    $db = new DBMS(':memory:');

    // Read and execute the DDL schema
    $ddl = file_get_contents(__DIR__ . '/../sql/ddl/create_table.sql');
    $pdo = new PDO('sqlite::memory:');
    // We need to access the internal PDO, so recreate with schema
    $db = new DBMS(':memory:');

    // Use fetchAll to execute raw SQL via a workaround:
    // insert the schema by reading the SQL file and executing statements
    $schemaFile = __DIR__ . '/../sql/ddl/create_table.sql';
    $schema = file_get_contents($schemaFile);

    // Execute each statement separately (SQLite doesn't support multi-statement exec via PDO::prepare)
    // We use the DBMS insert method with a trick: wrap in a transaction via raw fetch
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        fn($s) => $s !== ''
    );

    foreach ($statements as $stmt) {
        // Use fetchAll as a generic executor (returns empty array for DDL)
        $db->fetchAll($stmt);
    }

    return $db;
}

$t = new TestRunner();

// ---------------------------------------------------------------------------
// DBMS tests
// ---------------------------------------------------------------------------

$t->test('DBMS: insert returns auto-increment ID', function () use ($t) {
    $db = createTestDb();
    $id = $db->insert(
        "INSERT INTO publisher (name) VALUES (:name)",
        [':name' => 'Test Publisher']
    );
    $t->assertGreaterThan(0, $id);
});

$t->test('DBMS: fetchOne returns a row', function () use ($t) {
    $db = createTestDb();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'Acme']);
    $row = $db->fetchOne("SELECT name FROM publisher WHERE name = :name", [':name' => 'Acme']);
    $t->assertNotNull($row);
    $t->assertEqual('Acme', $row['name']);
});

$t->test('DBMS: fetchOne returns null for missing row', function () use ($t) {
    $db = createTestDb();
    $row = $db->fetchOne("SELECT * FROM publisher WHERE publisher_id = :id", [':id' => 999]);
    $t->assertNull($row);
});

$t->test('DBMS: fetchAll returns empty array for no results', function () use ($t) {
    $db = createTestDb();
    $rows = $db->fetchAll("SELECT * FROM publisher");
    $t->assertCount(0, $rows);
});

$t->test('DBMS: update returns affected row count', function () use ($t) {
    $db = createTestDb();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'Old']);
    $count = $db->update(
        "UPDATE publisher SET name = :name WHERE name = :old",
        [':name' => 'New', ':old' => 'Old']
    );
    $t->assertEqual(1, $count);
});

$t->test('DBMS: delete returns affected row count', function () use ($t) {
    $db = createTestDb();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'ToDelete']);
    $count = $db->delete("DELETE FROM publisher WHERE name = :name", [':name' => 'ToDelete']);
    $t->assertEqual(1, $count);
});

$t->test('DBMS: query returns rows without parameters', function () use ($t) {
    $db = createTestDb();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'Adelphi']);
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'Einaudi']);
    $rows = $db->query("SELECT name FROM publisher ORDER BY name");
    $t->assertCount(2, $rows);
    $t->assertEqual('Adelphi', $rows[0]['name']);
});

$t->test('DBMS: query rejects SQL with named parameters', function () use ($t) {
    $db = createTestDb();
    $caught = false;
    try {
        $db->query("SELECT * FROM publisher WHERE name = :name");
    } catch (\InvalidArgumentException $e) {
        $caught = true;
    }
    $t->assertTrue($caught);
});

$t->test('DBMS: exec executes DDL statements', function () use ($t) {
    $db = createTestDb();
    $db->exec("CREATE TABLE test_exec (id INTEGER PRIMARY KEY)");
    $rows = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'test_exec'");
    $t->assertCount(1, $rows);
});

$t->test('DBMS: exec rejects SQL with named parameters', function () use ($t) {
    $db = createTestDb();
    $caught = false;
    try {
        $db->exec("DELETE FROM publisher WHERE name = :name");
    } catch (\InvalidArgumentException $e) {
        $caught = true;
    }
    $t->assertTrue($caught);
});

$t->test('DBMS: transaction commit persists changes', function () use ($t) {
    $db = createTestDb();
    $db->beginTransaction();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'Committed']);
    $db->commit();
    $row = $db->fetchOne("SELECT name FROM publisher WHERE name = :name", [':name' => 'Committed']);
    $t->assertNotNull($row);
});

$t->test('DBMS: transaction rollBack discards changes', function () use ($t) {
    $db = createTestDb();
    $db->beginTransaction();
    $db->insert("INSERT INTO publisher (name) VALUES (:name)", [':name' => 'RolledBack']);
    $db->rollBack();
    $row = $db->fetchOne("SELECT name FROM publisher WHERE name = :name", [':name' => 'RolledBack']);
    $t->assertNull($row);
});

// ---------------------------------------------------------------------------
// Publisher tests
// ---------------------------------------------------------------------------

$t->test('Publisher: insert and getAll', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('Adelphi');
    $t->assertGreaterThan(0, $id);

    $all = $pub->getAll();
    $t->assertCount(1, $all);
    $t->assertEqual('Adelphi', $all[0]['name']);
});

$t->test('Publisher: getById returns record', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('Einaudi');
    $row = $pub->getById($id);
    $t->assertNotNull($row);
    $t->assertEqual('Einaudi', $row['name']);
});

$t->test('Publisher: getById returns null for missing ID', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $row = $pub->getById(999);
    $t->assertNull($row);
});

$t->test('Publisher: getActive excludes disabled', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id1 = $pub->insert('Active Press');
    $id2 = $pub->insert('Disabled Press');
    $pub->update($id2, 'Disabled Press', 0);

    $active = $pub->getActive();
    $t->assertCount(1, $active);
    $t->assertEqual('Active Press', $active[0]['name']);
});

$t->test('Publisher: exists detects duplicates case-insensitively', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $pub->insert('Mondadori');
    $t->assertTrue($pub->exists('mondadori'));
    $t->assertTrue($pub->exists('MONDADORI'));
    $t->assertFalse($pub->exists('Feltrinelli'));
});

$t->test('Publisher: exists excludes given ID', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('Rizzoli');
    // Same name but excluding own ID should return false (for update scenario)
    $t->assertFalse($pub->exists('Rizzoli', $id));
});

$t->test('Publisher: update changes name and status', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('Old Name');
    $pub->update($id, 'New Name', 0);

    $row = $pub->getById($id);
    $t->assertEqual('New Name', $row['name']);
    $t->assertEqual(0, (int) $row['status']);
});

$t->test('Publisher: hasBooks returns false with no books', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('Empty Publisher');
    $t->assertFalse($pub->hasBooks($id));
});

$t->test('Publisher: delete removes record', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);

    $id = $pub->insert('To Delete');
    $count = $pub->delete($id);
    $t->assertEqual(1, $count);
    $t->assertNull($pub->getById($id));
});

// ---------------------------------------------------------------------------
// Category tests
// ---------------------------------------------------------------------------

$t->test('Category: insert and getAll', function () use ($t) {
    $db = createTestDb();
    $cat = new Category($db);

    $id = $cat->insert('Fiction');
    $t->assertGreaterThan(0, $id);

    $all = $cat->getAll();
    $t->assertCount(1, $all);
    $t->assertEqual('Fiction', $all[0]['name']);
});

$t->test('Category: getActive excludes disabled', function () use ($t) {
    $db = createTestDb();
    $cat = new Category($db);

    $id1 = $cat->insert('Active Cat');
    $id2 = $cat->insert('Disabled Cat');
    $cat->update($id2, 'Disabled Cat', 0);

    $active = $cat->getActive();
    $t->assertCount(1, $active);
});

$t->test('Category: exists detects duplicates', function () use ($t) {
    $db = createTestDb();
    $cat = new Category($db);

    $cat->insert('Science');
    $t->assertTrue($cat->exists('science'));
    $t->assertFalse($cat->exists('History'));
});

$t->test('Category: delete removes record', function () use ($t) {
    $db = createTestDb();
    $cat = new Category($db);

    $id = $cat->insert('To Delete');
    $cat->delete($id);
    $t->assertNull($cat->getById($id));
});

// ---------------------------------------------------------------------------
// Author tests
// ---------------------------------------------------------------------------

$t->test('Author: insert and getAll', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $id = $auth->insert('Italo', 'Calvino', '1923-10-15');
    $t->assertGreaterThan(0, $id);

    $all = $auth->getAll();
    $t->assertCount(1, $all);
    $t->assertEqual('Calvino', $all[0]['last_name']);
});

$t->test('Author: insert with null birthdate', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $id = $auth->insert('Anonymous', 'Author', null);
    $row = $auth->getById($id);
    $t->assertNull($row['birthdate']);
});

$t->test('Author: exists detects duplicate full name', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $auth->insert('Umberto', 'Eco', null);
    $t->assertTrue($auth->exists('umberto', 'eco'));
    $t->assertFalse($auth->exists('Umberto', 'Calvino'));
});

$t->test('Author: getAll orders by last_name, first_name', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $auth->insert('Italo', 'Calvino', null);
    $auth->insert('Umberto', 'Eco', null);
    $auth->insert('Andrea', 'Camilleri', null);

    $all = $auth->getAll();
    $t->assertEqual('Calvino', $all[0]['last_name']);
    $t->assertEqual('Camilleri', $all[1]['last_name']);
    $t->assertEqual('Eco', $all[2]['last_name']);
});

$t->test('Author: update changes data', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $id = $auth->insert('Old', 'Name', null);
    $auth->update($id, 'New', 'Name', '1950-01-01', 0);

    $row = $auth->getById($id);
    $t->assertEqual('New', $row['first_name']);
    $t->assertEqual('1950-01-01', $row['birthdate']);
    $t->assertEqual(0, (int) $row['status']);
});

$t->test('Author: delete removes record', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $id = $auth->insert('To', 'Delete', null);
    $auth->delete($id);
    $t->assertNull($auth->getById($id));
});

$t->test('Author: getActive excludes disabled', function () use ($t) {
    $db = createTestDb();
    $auth = new Author($db);

    $id1 = $auth->insert('Active', 'Author', null);
    $id2 = $auth->insert('Disabled', 'Author2', null);
    $auth->update($id2, 'Disabled', 'Author2', null, 0);

    $active = $auth->getActive();
    $t->assertCount(1, $active);
    $t->assertEqual('Author', $active[0]['last_name']);
});

// ---------------------------------------------------------------------------
// Book tests
// ---------------------------------------------------------------------------

$t->test('Book: insert and getById', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $book = new Book($db);

    $pubId = $pub->insert('Einaudi');
    $catId = $cat->insert('Fiction');
    $bookId = $book->insert($pubId, $catId, 'Il Sentiero Dei Nidi Di Ragno', 180, 1947);

    $t->assertGreaterThan(0, $bookId);

    $row = $book->getById($bookId);
    $t->assertNotNull($row);
    $t->assertEqual('Il Sentiero Dei Nidi Di Ragno', $row['title']);
    $t->assertEqual(180, (int) $row['pages']);
    $t->assertEqual(1947, (int) $row['published']);
    $t->assertEqual('Einaudi', $row['publisher']);
    $t->assertEqual('Fiction', $row['category']);
});

$t->test('Book: insert with null pages and published', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $book = new Book($db);

    $pubId = $pub->insert('Mondadori');
    $catId = $cat->insert('Essay');
    $bookId = $book->insert($pubId, $catId, 'Unknown Details', null, null);

    $row = $book->getById($bookId);
    $t->assertNull($row['pages']);
    $t->assertNull($row['published']);
});

$t->test('Book: getAll includes authors via GROUP_CONCAT', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Adelphi');
    $catId = $cat->insert('Philosophy');
    $authId = $auth->insert('Friedrich', 'Nietzsche', '1844-10-15');
    $bookId = $book->insert($pubId, $catId, 'Thus Spoke Zarathustra', 352, 1883);

    // Link author to book
    $db->insert(
        "INSERT INTO book_author (book_id, author_id) VALUES (:book_id, :author_id)",
        [':book_id' => $bookId, ':author_id' => $authId]
    );

    $all = $book->getAll();
    $t->assertCount(1, $all);
    $t->assertEqual('Friedrich Nietzsche', $all[0]['authors']);
});

$t->test('Book: getAuthors returns linked authors', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $a1 = $auth->insert('Author', 'One', null);
    $a2 = $auth->insert('Author', 'Two', null);
    $bookId = $book->insert($pubId, $catId, 'Multi-Author Book', 200, 2020);

    $db->insert("INSERT INTO book_author (book_id, author_id) VALUES (:bid, :aid)", [':bid' => $bookId, ':aid' => $a1]);
    $db->insert("INSERT INTO book_author (book_id, author_id) VALUES (:bid, :aid)", [':bid' => $bookId, ':aid' => $a2]);

    $authors = $book->getAuthors($bookId);
    $t->assertCount(2, $authors);
});

$t->test('Book: setAuthors replaces existing authors', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $a1 = $auth->insert('Author', 'One', null);
    $a2 = $auth->insert('Author', 'Two', null);
    $a3 = $auth->insert('Author', 'Three', null);
    $bookId = $book->insert($pubId, $catId, 'Test Book', 100, 2020);

    // Set initial authors
    $book->setAuthors($bookId, [$a1, $a2]);
    $t->assertCount(2, $book->getAuthors($bookId));

    // Replace with different authors
    $book->setAuthors($bookId, [$a3]);
    $authors = $book->getAuthors($bookId);
    $t->assertCount(1, $authors);
    $t->assertEqual('Three', $authors[0]['last_name']);
});

$t->test('Book: setAuthors with empty array clears authors', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $a1 = $auth->insert('Author', 'One', null);
    $bookId = $book->insert($pubId, $catId, 'Test Book', 100, 2020);

    $book->setAuthors($bookId, [$a1]);
    $t->assertCount(1, $book->getAuthors($bookId));

    $book->setAuthors($bookId, []);
    $t->assertCount(0, $book->getAuthors($bookId));
});

$t->test('Book: update changes data', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $bookId = $book->insert($pubId, $catId, 'Original Title', 100, 2000);

    $book->update($bookId, $pubId, $catId, 'Updated Title', 200, 2024, 0);

    $row = $book->getById($bookId);
    $t->assertEqual('Updated Title', $row['title']);
    $t->assertEqual(200, (int) $row['pages']);
    $t->assertEqual(0, (int) $row['status']);
});

$t->test('Book: delete removes book and book_author records', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $authId = $auth->insert('To', 'Delete', null);
    $bookId = $book->insert($pubId, $catId, 'Delete Me', 50, 2020);

    $db->insert("INSERT INTO book_author (book_id, author_id) VALUES (:bid, :aid)", [':bid' => $bookId, ':aid' => $authId]);

    $book->delete($bookId);
    $t->assertNull($book->getById($bookId));

    // Verify book_author junction was cleaned up
    $junction = $db->fetchAll("SELECT * FROM book_author WHERE book_id = :id", [':id' => $bookId]);
    $t->assertCount(0, $junction);
});

$t->test('Publisher: hasBooks detects linked books', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $book = new Book($db);

    $pubId = $pub->insert('Has Books Publisher');
    $catId = $cat->insert('Category');
    $book->insert($pubId, $catId, 'A Book', 100, 2020);

    $t->assertTrue($pub->hasBooks($pubId));
    $t->assertTrue($pub->hasBooks($pubId, false));
});

$t->test('Author: hasBooks detects linked books via junction', function () use ($t) {
    $db = createTestDb();
    $pub = new Publisher($db);
    $cat = new Category($db);
    $auth = new Author($db);
    $book = new Book($db);

    $pubId = $pub->insert('Publisher');
    $catId = $cat->insert('Category');
    $authId = $auth->insert('Linked', 'Author', null);
    $bookId = $book->insert($pubId, $catId, 'Linked Book', 100, 2020);

    $db->insert("INSERT INTO book_author (book_id, author_id) VALUES (:bid, :aid)", [':bid' => $bookId, ':aid' => $authId]);

    $t->assertTrue($auth->hasBooks($authId));
    $t->assertTrue($auth->hasBooks($authId, false));
});

// ---------------------------------------------------------------------------
// Run all tests
// ---------------------------------------------------------------------------

$t->run();
