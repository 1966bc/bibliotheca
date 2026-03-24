<?php

declare(strict_types=1);

/**
 * Book model — manages book records in the database.
 *
 * Handles CRUD operations for the `book` table.
 * Each book belongs to one publisher and one category (foreign keys).
 * Authors are linked via the `book_author` junction table (many-to-many).
 * Deletion cascades to `book_author` records to maintain referential integrity.
 *
 * @see DBMS The database wrapper used for all queries
 */
class Book
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
     * Get all books with their publisher, category, and concatenated author names.
     *
     * Uses JOINs to resolve foreign keys and GROUP_CONCAT to merge
     * multiple authors into a single comma-separated string.
     *
     * @return array List of books (each with book_id, title, pages, published, status, publisher, category, authors)
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT b.book_id, b.title, b.pages, b.published, b.status,
                    p.name AS publisher, c.name AS category,
                    GROUP_CONCAT(a.first_name || ' ' || a.last_name, ', ') AS authors
             FROM book b
             JOIN publisher p ON b.publisher_id = p.publisher_id
             JOIN category c ON b.category_id = c.category_id
             LEFT JOIN book_author ba ON b.book_id = ba.book_id
             LEFT JOIN author a ON ba.author_id = a.author_id
             GROUP BY b.book_id
             ORDER BY b.title"
        );
    }

    /**
     * Find a book by its primary key, including publisher and category names.
     *
     * Does not include authors — use getAuthors() separately.
     *
     * @param  int        $id Book ID
     * @return array|null     Book data with publisher/category names, or null
     */
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

    /**
     * Get all authors associated with a book, ordered by last name.
     *
     * Queries the book_author junction table to resolve the many-to-many relationship.
     *
     * @param  int   $bookId Book ID
     * @return array         List of authors (each with author_id, first_name, last_name)
     */
    public function getAuthors(int $bookId): array
    {
        $sql = "SELECT a.author_id, a.first_name, a.last_name
                FROM author a
                JOIN book_author ba ON a.author_id = ba.author_id
                WHERE ba.book_id = :book_id
                ORDER BY a.last_name";

        return $this->db->fetchAll($sql, [':book_id' => $bookId]);
    }

    /**
     * Replace all authors for a book (delete + insert in a transaction).
     *
     * @param  int   $bookId    Book ID
     * @param  array $authorIds List of author IDs to associate
     * @return void
     */
    public function setAuthors(int $bookId, array $authorIds): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->delete(
                "DELETE FROM book_author WHERE book_id = :book_id",
                [':book_id' => $bookId]
            );

            $sql = "INSERT INTO book_author (book_id, author_id)
                    VALUES (:book_id, :author_id)";

            foreach ($authorIds as $authorId) {
                $this->db->insert($sql, [
                    ':book_id'   => $bookId,
                    ':author_id' => (int) $authorId,
                ]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Create a new book.
     *
     * @param  int      $publisherId Foreign key to publisher table
     * @param  int      $categoryId  Foreign key to category table
     * @param  string   $title       Book title
     * @param  int|null $pages       Number of pages, or null if unknown
     * @param  int|null $published   Publication year, or null if unknown
     * @return int                   The auto-generated book_id
     */
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

    /**
     * Update an existing book's data.
     *
     * @param  int      $id          Book ID
     * @param  int      $publisherId New publisher foreign key
     * @param  int      $categoryId  New category foreign key
     * @param  string   $title       New title
     * @param  int|null $pages       New page count, or null
     * @param  int|null $published   New publication year, or null
     * @param  int      $status      New status (1 = active, 0 = disabled)
     * @return int                   Number of rows affected (0 or 1)
     */
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

    /**
     * Permanently delete a book and its book_author junction records (hard delete).
     *
     * First removes all entries from book_author for this book,
     * then deletes the book itself. This two-step approach ensures
     * referential integrity without relying on ON DELETE CASCADE.
     *
     * @param  int $id Book ID
     * @return int     Number of book rows deleted (0 or 1)
     */
    public function delete(int $id): int
    {
        $this->db->beginTransaction();

        try {
            $this->db->delete(
                "DELETE FROM book_author WHERE book_id = :book_id",
                [':book_id' => $id]
            );

            $count = $this->db->delete(
                "DELETE FROM book WHERE book_id = :id",
                [':id' => $id]
            );

            $this->db->commit();

            return $count;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
