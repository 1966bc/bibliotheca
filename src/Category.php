<?php

declare(strict_types=1);

class Category
{
    private DBMS $db;

    public function __construct(DBMS $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT category_id, name, status
                FROM category
                WHERE status = 1
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT category_id, name, status
                FROM category
                WHERE category_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

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

    public function insert(string $name): int
    {
        $sql = "INSERT INTO category (name) VALUES (:name)";

        return $this->db->insert($sql, [':name' => $name]);
    }

    public function update(int $id, string $name): int
    {
        $sql = "UPDATE category
                SET name = :name
                WHERE category_id = :id";

        return $this->db->update($sql, [':name' => $name, ':id' => $id]);
    }

    public function hasBooks(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book
                WHERE category_id = :id
                AND status = 1";

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    public function delete(int $id): int
    {
        $sql = "UPDATE category
                SET status = 0
                WHERE category_id = :id";

        return $this->db->update($sql, [':id' => $id]);
    }
}
