-- Bibliotheca — Sample Data

-- Publishers
INSERT INTO publisher (name) VALUES ('Adelphi');
INSERT INTO publisher (name) VALUES ('Einaudi');
INSERT INTO publisher (name) VALUES ('Feltrinelli');
INSERT INTO publisher (name) VALUES ('Mondadori');
INSERT INTO publisher (name) VALUES ('Laterza');
INSERT INTO publisher (name) VALUES ('Penguin Books');
INSERT INTO publisher (name) VALUES ('Addison-Wesley');
INSERT INTO publisher (name) VALUES ('Gallimard');

-- Categories
INSERT INTO category (name) VALUES ('Fiction');
INSERT INTO category (name) VALUES ('Science');
INSERT INTO category (name) VALUES ('Philosophy');
INSERT INTO category (name) VALUES ('History');
INSERT INTO category (name) VALUES ('Computer Science');

-- Authors
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Italo', 'Calvino', '1923-10-15');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Primo', 'Levi', '1919-07-31');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Umberto', 'Eco', '1932-01-05');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Carlo', 'Rovelli', '1956-05-03');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Donald', 'Knuth', '1938-01-10');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('George', 'Orwell', '1903-06-25');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Albert', 'Camus', '1913-11-07');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Brian', 'Kernighan', '1942-01-01');
INSERT INTO author (first_name, last_name, birthdate) VALUES ('Dennis', 'Ritchie', '1941-09-09');

-- Books
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Il sentiero dei nidi di ragno', 153, 1947);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (2, 1, 'Se questo è un uomo', 230, 1947);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (4, 1, 'Il nome della rosa', 512, 1980);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (1, 2, 'Sette brevi lezioni di fisica', 88, 2014);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (7, 5, 'The Art of Computer Programming', 672, 1968);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (6, 1, '1984', 328, 1949);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (8, 3, 'Le Mythe de Sisyphe', 195, 1942);
INSERT INTO book (publisher_id, category_id, title, pages, published) VALUES (7, 5, 'The C Programming Language', 228, 1978);

-- Book-Author relationships
INSERT INTO book_author (book_id, author_id) VALUES (1, 1);
INSERT INTO book_author (book_id, author_id) VALUES (2, 2);
INSERT INTO book_author (book_id, author_id) VALUES (3, 3);
INSERT INTO book_author (book_id, author_id) VALUES (4, 4);
INSERT INTO book_author (book_id, author_id) VALUES (5, 5);
INSERT INTO book_author (book_id, author_id) VALUES (6, 6);
INSERT INTO book_author (book_id, author_id) VALUES (7, 7);
INSERT INTO book_author (book_id, author_id) VALUES (8, 8);
INSERT INTO book_author (book_id, author_id) VALUES (8, 9);
