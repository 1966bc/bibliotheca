<?php

declare(strict_types=1);

/**
 * Publisher model — manages publisher records in the database.
 *
 * Handles CRUD operations for the `publisher` table.
 * Supports soft deletes via the `status` column (1 = active, 0 = disabled)
 * and referential integrity checks before deletion or deactivation.
 *
 * @see DBMS The database wrapper used for all queries
 */
class Publisher
{
    /** @var DBMS Database connection instance */
    private DBMS $db;

    /**
     * @param DBMS $db Database connection instance
     */
    public function __construct(DBMS $db)
    {
        $this->db = $db;
    }

    /**
     * Get all publishers, including disabled ones, ordered by name.
     *
     * @return array List of publishers (each with publisher_id, name, status)
     */
    public function getAll(): array
    {
        $sql = "SELECT publisher_id, name, status
                FROM publisher
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get only active publishers (status = 1), ordered by name.
     *
     * Used to populate dropdown selects in the book form,
     * where only active publishers should be available.
     *
     * @return array List of active publishers
     */
    public function getActive(): array
    {
        $sql = "SELECT publisher_id, name, status
                FROM publisher
                WHERE status = 1
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    /**
     * Find a publisher by its primary key.
     *
     * @param  int        $id Publisher ID
     * @return array|null     Publisher data, or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT publisher_id, name, status
                FROM publisher
                WHERE publisher_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Check if an active publisher with the given name already exists.
     *
     * Uses case-insensitive comparison. Optionally excludes a specific ID
     * to allow updates without triggering a false duplicate.
     *
     * @param  string $name      Publisher name to check
     * @param  int    $excludeId Publisher ID to exclude from the check (for updates)
     * @return bool              True if a duplicate exists
     */
    public function exists(string $name, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM publisher
                WHERE LOWER(name) = LOWER(:name)
                AND status = 1
                AND publisher_id != :exclude_id";

        $row = $this->db->fetchOne($sql, [':name' => $name, ':exclude_id' => $excludeId]);

        return $row['total'] > 0;
    }

    /**
     * Create a new publisher.
     *
     * @param  string $name Publisher name (already normalized by the API layer)
     * @return int          The auto-generated publisher_id
     */
    public function insert(string $name): int
    {
        $sql = "INSERT INTO publisher (name) VALUES (:name)";

        return $this->db->insert($sql, [':name' => $name]);
    }

    /**
     * Update an existing publisher's name and status.
     *
     * @param  int    $id     Publisher ID
     * @param  string $name   New name
     * @param  int    $status New status (1 = active, 0 = disabled)
     * @return int            Number of rows affected (0 or 1)
     */
    public function update(int $id, string $name, int $status): int
    {
        $sql = "UPDATE publisher
                SET name = :name, status = :status
                WHERE publisher_id = :id";

        return $this->db->update($sql, [':name' => $name, ':status' => $status, ':id' => $id]);
    }

    /**
     * Check if a publisher has associated books.
     *
     * Used to prevent disabling (activeOnly=true) or deleting (activeOnly=false)
     * a publisher that still has books referencing it.
     *
     * @param  int  $id         Publisher ID
     * @param  bool $activeOnly If true, only count active books; if false, count all
     * @return bool             True if associated books exist
     */
    public function hasBooks(int $id, bool $activeOnly = true): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book
                WHERE publisher_id = :id";

        if ($activeOnly) {
            $sql .= " AND status = 1";
        }

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    /**
     * Permanently delete a publisher (hard delete).
     *
     * The API layer must verify hasBooks() before calling this method
     * to avoid foreign key constraint violations.
     *
     * @param  int $id Publisher ID
     * @return int     Number of rows deleted (0 or 1)
     */
    public function delete(int $id): int
    {
        $sql = "DELETE FROM publisher
                WHERE publisher_id = :id";

        return $this->db->delete($sql, [':id' => $id]);
    }
}
