<?php

declare(strict_types=1);

/**
 * Author model — manages author records in the database.
 *
 * Handles CRUD operations for the `author` table.
 * Authors are linked to books via the `book_author` junction table (many-to-many).
 * Supports soft deletes via the `status` column (1 = active, 0 = disabled)
 * and referential integrity checks before deletion or deactivation.
 *
 * @see DBMS The database wrapper used for all queries
 */
class Author
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
     * Get all authors ordered by last name, then first name.
     *
     * @return array List of authors (each with author_id, first_name, last_name, birthdate, status)
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT author_id, first_name, last_name, birthdate, status
             FROM author
             ORDER BY last_name, first_name"
        );
    }

    /**
     * Find an author by its primary key.
     *
     * @param  int        $id Author ID
     * @return array|null     Author data, or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT author_id, first_name, last_name, birthdate, status
                FROM author
                WHERE author_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Check if an active author with the given full name already exists.
     *
     * Uses case-insensitive comparison on both first and last name.
     * Optionally excludes a specific ID to allow updates.
     *
     * @param  string $firstName First name to check
     * @param  string $lastName  Last name to check
     * @param  int    $excludeId Author ID to exclude (for updates)
     * @return bool              True if a duplicate exists
     */
    public function exists(string $firstName, string $lastName, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM author
                WHERE LOWER(first_name) = LOWER(:first_name)
                AND LOWER(last_name) = LOWER(:last_name)
                AND status = 1
                AND author_id != :exclude_id";

        $row = $this->db->fetchOne($sql, [
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':exclude_id' => $excludeId,
        ]);

        return $row['total'] > 0;
    }

    /**
     * Create a new author.
     *
     * @param  string      $firstName Author's first name
     * @param  string      $lastName  Author's last name
     * @param  string|null $birthdate Date of birth in YYYY-MM-DD format, or null
     * @return int                    The auto-generated author_id
     */
    public function insert(string $firstName, string $lastName, ?string $birthdate): int
    {
        $sql = "INSERT INTO author (first_name, last_name, birthdate)
                VALUES (:first_name, :last_name, :birthdate)";

        return $this->db->insert($sql, [
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':birthdate'  => $birthdate,
        ]);
    }

    /**
     * Update an existing author's data.
     *
     * @param  int         $id        Author ID
     * @param  string      $firstName New first name
     * @param  string      $lastName  New last name
     * @param  string|null $birthdate New birthdate (YYYY-MM-DD) or null
     * @param  int         $status    New status (1 = active, 0 = disabled)
     * @return int                    Number of rows affected (0 or 1)
     */
    public function update(int $id, string $firstName, string $lastName, ?string $birthdate, int $status): int
    {
        $sql = "UPDATE author
                SET first_name = :first_name,
                    last_name = :last_name,
                    birthdate = :birthdate,
                    status = :status
                WHERE author_id = :id";

        return $this->db->update($sql, [
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':birthdate'  => $birthdate,
            ':status'     => $status,
            ':id'         => $id,
        ]);
    }

    /**
     * Check if an author has associated books via the book_author junction table.
     *
     * Used to prevent disabling or deleting an author that is linked to books.
     *
     * @param  int  $id         Author ID
     * @param  bool $activeOnly If true, only count active books; if false, count all
     * @return bool             True if associated books exist
     */
    public function hasBooks(int $id, bool $activeOnly = true): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book_author ba
                JOIN book b ON ba.book_id = b.book_id
                WHERE ba.author_id = :id";

        if ($activeOnly) {
            $sql .= " AND b.status = 1";
        }

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    /**
     * Permanently delete an author (hard delete).
     *
     * The API layer must verify hasBooks() before calling this method.
     * Note: book_author junction records are not deleted here — they must be
     * cleaned up separately or prevented by the integrity check.
     *
     * @param  int $id Author ID
     * @return int     Number of rows deleted (0 or 1)
     */
    public function delete(int $id): int
    {
        $sql = "DELETE FROM author
                WHERE author_id = :id";

        return $this->db->delete($sql, [':id' => $id]);
    }
}
