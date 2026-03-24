<?php

declare(strict_types=1);

class Author
{
    private DBMS $db;

    public function __construct(DBMS $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT author_id, first_name, last_name, birthdate, status
                FROM author
                WHERE status = 1
                ORDER BY last_name, first_name";

        return $this->db->fetchAll($sql);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT author_id, first_name, last_name, birthdate, status
                FROM author
                WHERE author_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

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

    public function update(int $id, string $firstName, string $lastName, ?string $birthdate): int
    {
        $sql = "UPDATE author
                SET first_name = :first_name,
                    last_name = :last_name,
                    birthdate = :birthdate
                WHERE author_id = :id";

        return $this->db->update($sql, [
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':birthdate'  => $birthdate,
            ':id'         => $id,
        ]);
    }

    public function hasBooks(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book_author ba
                JOIN book b ON ba.book_id = b.book_id
                WHERE ba.author_id = :id
                AND b.status = 1";

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    public function delete(int $id): int
    {
        $sql = "UPDATE author
                SET status = 0
                WHERE author_id = :id";

        return $this->db->update($sql, [':id' => $id]);
    }
}
