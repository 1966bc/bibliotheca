<?php

declare(strict_types=1);

class Publisher
{
    private DBMS $db;

    public function __construct(DBMS $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT publisher_id, name, status
                FROM publisher
                WHERE status = 1
                ORDER BY name";

        return $this->db->fetchAll($sql);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT publisher_id, name, status
                FROM publisher
                WHERE publisher_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

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

    public function insert(string $name): int
    {
        $sql = "INSERT INTO publisher (name) VALUES (:name)";

        return $this->db->insert($sql, [':name' => $name]);
    }

    public function update(int $id, string $name): int
    {
        $sql = "UPDATE publisher
                SET name = :name
                WHERE publisher_id = :id";

        return $this->db->update($sql, [':name' => $name, ':id' => $id]);
    }

    public function hasBooks(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM book
                WHERE publisher_id = :id
                AND status = 1";

        $row = $this->db->fetchOne($sql, [':id' => $id]);

        return $row['total'] > 0;
    }

    public function delete(int $id): int
    {
        $sql = "UPDATE publisher
                SET status = 0
                WHERE publisher_id = :id";

        return $this->db->update($sql, [':id' => $id]);
    }
}
