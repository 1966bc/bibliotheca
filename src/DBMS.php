<?php

declare(strict_types=1);

/**
 * Database Management System — PDO wrapper for SQLite.
 *
 * Provides a thin abstraction over PDO with convenience methods for
 * common database operations (query, fetch, insert, update, delete).
 *
 * PDO offers two ways to execute SQL:
 * - query()   — for fixed SQL with no parameters (e.g. SELECT * FROM publisher)
 * - prepare() + execute() — for SQL with user input bound via named parameters
 *
 * This class exposes both so the developer learns when to use each.
 * Rule of thumb: if the SQL has parameters, always use prepare().
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
     * Execute a fixed SQL query with no parameters.
     *
     * Use this for simple queries where no user input is involved.
     * For queries with parameters, use fetchOne() or fetchAll() instead,
     * which use prepared statements.
     *
     * @param  string $sql SQL query without placeholders
     * @return array       Array of associative arrays (one per row)
     */
    public function query(string $sql): array
    {
        if (preg_match('/:[a-zA-Z_]/', $sql)) {
            throw new \InvalidArgumentException(
                'query() does not accept parameters. Use fetchAll() or fetchOne() with prepared statements instead.'
            );
        }

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Execute a SQL statement that does not return rows.
     *
     * Use this for DDL statements (CREATE TABLE, DROP TABLE) and
     * commands like PRAGMA. Returns the number of affected rows,
     * which is 0 for DDL.
     *
     * @param  string $sql SQL statement without placeholders
     * @return int         Number of affected rows
     */
    public function exec(string $sql): int
    {
        if (preg_match('/:[a-zA-Z_]/', $sql)) {
            throw new \InvalidArgumentException(
                'exec() does not accept parameters. Use insert(), update() or delete() with prepared statements instead.'
            );
        }

        return (int) $this->pdo->exec($sql);
    }

    /**
     * Begin a database transaction.
     *
     * All statements after this call will be held in a pending state
     * until commit() is called. If an error occurs, call rollBack()
     * to undo everything since beginTransaction().
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction, making all changes permanent.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Roll back the current transaction, undoing all changes.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
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
