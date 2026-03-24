<?php

declare(strict_types=1);

/**
 * Category model — manages category records in the database.
 *
 * Handles CRUD operations for the `category` table.
 * Supports soft deletes via the `status` column (1 = active, 0 = disabled)
 * and referential integrity checks before deletion or deactivation.
 *
 * @see DBMS The database wrapper used for all queries
 */
class Category
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
     * Get all categories, including disabled ones, ordered by name.
     *
     * @return array List of categories (each with category_id, name, status)
     */
    public function getAll(): array
    {
        $sql = "SELECT category_id, name, status
                FROM category
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get only active categories (status = 1), ordered by name.
     *
     * Used to populate dropdown selects in the book form.
     *
     * @return array List of active categories
     */
    public function getActive(): array
    {
        $sql = "SELECT category_id, name, status
                FROM category
                WHERE status = 1
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    /**
     * Find a category by its primary key.
     *
     * @param  int        $id Category ID
     * @return array|null     Category data, or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT category_id, name, status
                FROM category
                WHERE category_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Check if an active category with the given name already exists.
     *
     * Uses case-insensitive comparison. Optionally excludes a specific ID
     * to allow updates without triggering a false duplicate.
     *
     * @param  string $name      Category name to check
     * @param  int    $excludeId Category ID to exclude from the check (for updates)
     * @return bool              True if a duplicate exists
     */
    public function exists(string $name, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM category
                WHERE LOWER(name) = LOWER(:name)
                AND status = 1
                AND category_id != :exclude_id";

        $row = $this->db->fetchOne($sql, [':name' => $name, ':exclude_id' => $excludeId]);

        return $row['total'] > 0;
    }

    /**
     * Create a new category.
     *
     * @param  string $name Category name (already normalized by the API layer)
     * @return int          The auto-generated category_id
     */
    public function insert(string $name): int
    {
        $sql = "INSERT INTO category (name) VALUES (:name)";

        return $this->db->insert($sql, [':name' => $name]);
    }

    /**
     * Update an existing category's name and status.
     *
     * @param  int    $id     Category ID
     * @param  string $name   New name
     * @param  int    $status New status (1 = active, 0 = disabled)
     * @return int            Number of rows affected (0 or 1)
     */
    public function update(int $id, string $name, int $status): int
    {
        $sql = "UPDATE category
                SET name = :name, status = :status
                WHERE category_id = :id";

        return $this->db->update($sql, [':name' => $name, ':status' => $status, ':id' => $id]);
    }

    /**
     * Check if a category has associated books.
     *
     * Used to prevent disabling (activeOnly=true) or deleting (activeOnly=false)
     * a category that still has books referencing it.
     *
     * @param  int  $id         Category ID
     * @param  bool $activeOnly If true, only count active books; if false, count all
     * @return bool             True if associated books exist
     */
    public function hasBooks(int $id, bool $activeOnly = true): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book
                WHERE category_id = :id";

        if ($activeOnly) {
            $sql .= " AND status = 1";
        }

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    /**
     * Permanently delete a category (hard delete).
     *
     * The API layer must verify hasBooks() before calling this method
     * to avoid foreign key constraint violations.
     *
     * @param  int $id Category ID
     * @return int     Number of rows deleted (0 or 1)
     */
    public function delete(int $id): int
    {
        $sql = "DELETE FROM category
                WHERE category_id = :id";

        return $this->db->delete($sql, [':id' => $id]);
    }
}
