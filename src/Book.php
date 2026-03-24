<?php

declare(strict_types=1);

class Book
{
    private DBMS $db;

    public function __construct(DBMS $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT b.book_id, b.title, b.pages, b.published, b.status,
                       p.name AS publisher, c.name AS category
                FROM book b
                JOIN publisher p ON b.publisher_id = p.publisher_id
                JOIN category c ON b.category_id = c.category_id
                ORDER BY b.title";

        return $this->db->fetchAll($sql);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT b.book_id, b.publisher_id, b.category_id,
                       b.title, b.pages, b.published, b.status,
                       p.name AS publisher, c.name AS category
                FROM book b
                JOIN publisher p ON b.publisher_id = p.publisher_id
                JOIN category c ON b.category_id = c.category_id
                WHERE b.book_id = :id";

        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function getAuthors(int $bookId): array
    {
        $sql = "SELECT a.author_id, a.first_name, a.last_name
                FROM author a
                JOIN book_author ba ON a.author_id = ba.author_id
                WHERE ba.book_id = :book_id
                ORDER BY a.last_name";

        return $this->db->fetchAll($sql, [':book_id' => $bookId]);
    }

    public function insert(int $publisherId, int $categoryId, string $title, ?int $pages, ?int $published): int
    {
        $sql = "INSERT INTO book (publisher_id, category_id, title, pages, published)
                VALUES (:publisher_id, :category_id, :title, :pages, :published)";

        return $this->db->insert($sql, [
            ':publisher_id' => $publisherId,
            ':category_id'  => $categoryId,
            ':title'        => $title,
            ':pages'        => $pages,
            ':published'    => $published,
        ]);
    }

    public function update(int $id, int $publisherId, int $categoryId, string $title, ?int $pages, ?int $published, int $status): int
    {
        $sql = "UPDATE book
                SET publisher_id = :publisher_id,
                    category_id = :category_id,
                    title = :title,
                    pages = :pages,
                    published = :published,
                    status = :status
                WHERE book_id = :id";

        return $this->db->update($sql, [
            ':publisher_id' => $publisherId,
            ':category_id'  => $categoryId,
            ':title'        => $title,
            ':pages'        => $pages,
            ':published'    => $published,
            ':status'       => $status,
            ':id'           => $id,
        ]);
    }

    public function delete(int $id): int
    {
        $this->db->delete(
            "DELETE FROM book_author WHERE book_id = :book_id",
            [':book_id' => $id]
        );

        $sql = "DELETE FROM book
                WHERE book_id = :id";

        return $this->db->delete($sql, [':id' => $id]);
    }
}
