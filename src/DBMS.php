<?php

declare(strict_types=1);

/**
 * Database Management System — PDO wrapper for SQLite.
 *
 * Provides a thin abstraction over PDO with convenience methods for
 * common database operations (fetch, insert, update, delete).
 * All queries use prepared statements with named parameters to prevent SQL injection.
 *
 * Usage:
 *     $db = new DBMS('/path/to/database.db');
 *     $row = $db->fetchOne("SELECT * FROM book WHERE book_id = :id", [':id' => 1]);
 *
 * @see https://www.php.net/manual/en/book.pdo.php PDO documentation
 */
class DBMS
{
    /** @var PDO The PDO connection instance */
    private PDO $pdo;

    /**
     * Open a SQLite connection and enable foreign key enforcement.
     *
     * PDO is configured with:
     * - ERRMODE_EXCEPTION: throws PDOException on errors instead of silent failures
     * - FETCH_ASSOC: returns associative arrays (column names as keys)
     *
     * @param string $path Absolute or relative path to the SQLite database file
     * @throws PDOException If the connection cannot be established
     */
    public function __construct(string $path)
    {
        $this->pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * Execute a query and return a single row.
     *
     * @param  string     $sql    SQL query with named placeholders (e.g. `:id`)
     * @param  array      $params Associative array of parameter bindings
     * @return array|null         Associative array of the row, or null if not found
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Execute a query and return all matching rows.
     *
     * @param  string $sql    SQL query with named placeholders
     * @param  array  $params Associative array of parameter bindings
     * @return array          Array of associative arrays (one per row)
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Execute an INSERT statement and return the new row's ID.
     *
     * @param  string $sql    INSERT query with named placeholders
     * @param  array  $params Associative array of parameter bindings
     * @return int            The auto-generated ID of the inserted row
     */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Execute an UPDATE statement and return the number of affected rows.
     *
     * @param  string $sql    UPDATE query with named placeholders
     * @param  array  $params Associative array of parameter bindings
     * @return int            Number of rows affected by the update
     */
    public function update(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Execute a DELETE statement and return the number of affected rows.
     *
     * @param  string $sql    DELETE query with named placeholders
     * @param  array  $params Associative array of parameter bindings
     * @return int            Number of rows deleted
     */
    public function delete(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
