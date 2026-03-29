-- Bibliotheca — Sample Data

-- Admin user (password: bibliotheca)
INSERT INTO user (username, password) VALUES (
    'admin',
    '$2y$10$a37/WOXqfPSXGF/Sk04Jkebb3qMLcNH./sQ9GNsv234swfMmcQBUW'
);

-- Publishers
INSERT INTO publisher (name) VALUES ('Adelphi');
INSERT INTO publisher (name) VALUES ('Penguin Books');
INSERT INTO publisher (name) VALUES ('Addison-Wesley');
INSERT INTO publisher (name) VALUES ('Mondadori');

-- Categories
INSERT INTO category (name) VALUES ('Fiction');
INSERT INTO category (name) VALUES ('Science');
INSERT INTO category (name) VALUES ('Computer Science');

-- Authors
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Carlo', 'Rovelli', '1956-05-03');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('George', 'Orwell', '1903-06-25');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Brian', 'Kernighan', '1942-01-01');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Dennis', 'Ritchie', '1941-09-09');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Douglas', 'Adams', '1952-03-11');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Andrew', 'Hunt', '1964-01-01');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('David', 'Thomas', '1956-01-01');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Isaac', 'Asimov', '1920-01-02');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Arthur C.', 'Clarke', '1917-12-16');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Ray', 'Bradbury', '1920-08-22');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Frank', 'Herbert', '1920-10-08');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Philip K.', 'Dick', '1928-12-16');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Aldous', 'Huxley', '1894-07-26');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Donald', 'Knuth', '1938-01-10');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Robert C.', 'Martin', '1952-12-05');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Herman', 'Melville', '1819-08-01');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Jack', 'London', '1876-01-12');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Jules', 'Verne', '1828-02-08');

-- Books (publisher_id, category_id, title, pages, published)
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, '1984', 328, 1949);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, '2001: A Space Odyssey', 297, 1968);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Brave New World', 288, 1932);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'Clean Code', 464, 2008);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Do Androids Dream Of Electric Sheep?', 210, 1968);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Dune', 412, 1965);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Fahrenheit 451', 194, 1953);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (4, 1, 'Foundation', 244, 1951);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Martin Eden', 480, 1909);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Moby Dick', 635, 1851);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'The Art Of Computer Programming', 672, 1968);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'The C Programming Language', 228, 1978);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'The Hitchhiker''s Guide to the Galaxy', 215, 1979);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'The Pragmatic Programmer', 352, 1999);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Twenty Thousand Leagues Under The Seas', 368, 1870);

-- Book-Author relationships (book_id, author_id)
INSERT INTO book_author (book_id, author_id) VALUES (1, 2);   -- 1984 — Orwell
INSERT INTO book_author (book_id, author_id) VALUES (2, 9);   -- 2001 — Clarke
INSERT INTO book_author (book_id, author_id) VALUES (3, 13);  -- Brave New World — Huxley
INSERT INTO book_author (book_id, author_id) VALUES (4, 15);  -- Clean Code — Martin
INSERT INTO book_author (book_id, author_id) VALUES (5, 12);  -- Do Androids Dream — Dick
INSERT INTO book_author (book_id, author_id) VALUES (6, 11);  -- Dune — Herbert
INSERT INTO book_author (book_id, author_id) VALUES (7, 10);  -- Fahrenheit 451 — Bradbury
INSERT INTO book_author (book_id, author_id) VALUES (8, 8);   -- Foundation — Asimov
INSERT INTO book_author (book_id, author_id) VALUES (9, 17);  -- Martin Eden — London
INSERT INTO book_author (book_id, author_id) VALUES (10, 16); -- Moby Dick — Melville
INSERT INTO book_author (book_id, author_id) VALUES (11, 14); -- Art Of Computer Programming — Knuth
INSERT INTO book_author (book_id, author_id) VALUES (12, 3);  -- C Programming Language — Kernighan
INSERT INTO book_author (book_id, author_id) VALUES (12, 4);  -- C Programming Language — Ritchie
INSERT INTO book_author (book_id, author_id) VALUES (13, 5);  -- Hitchhiker's Guide — Adams
INSERT INTO book_author (book_id, author_id) VALUES (14, 6);  -- Pragmatic Programmer — Hunt
INSERT INTO book_author (book_id, author_id) VALUES (14, 7);  -- Pragmatic Programmer — Thomas
INSERT INTO book_author (book_id, author_id) VALUES (15, 18); -- Twenty Thousand Leagues — Verne
