-- Bibliotheca — Sample Data

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

-- Books
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (1, 2, 'Sette brevi lezioni di fisica', 88, 2014);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, '1984', 328, 1949);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'The C Programming Language', 228, 1978);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'The Hitchhiker''s Guide to the Galaxy', 215, 1979);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (3, 3, 'The Pragmatic Programmer', 352, 1999);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (4, 1, 'Foundation', 244, 1951);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, '2001: A Space Odyssey', 297, 1968);

-- Book-Author relationships
INSERT INTO book_author (book_id, author_id) VALUES (1, 1);
INSERT INTO book_author (book_id, author_id) VALUES (2, 2);
INSERT INTO book_author (book_id, author_id) VALUES (3, 3);
INSERT INTO book_author (book_id, author_id) VALUES (3, 4);
INSERT INTO book_author (book_id, author_id) VALUES (4, 5);
INSERT INTO book_author (book_id, author_id) VALUES (5, 6);
INSERT INTO book_author (book_id, author_id) VALUES (5, 7);
INSERT INTO book_author (book_id, author_id) VALUES (6, 8);
INSERT INTO book_author (book_id, author_id) VALUES (7, 9);
