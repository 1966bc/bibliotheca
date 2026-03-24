'use strict';

/**
 * Books list view — fetches and renders the books table.
 *
 * Instantiated automatically when the books/home page loads.
 * Fetches all books from the API and renders them as table rows
 * with columns for title, authors, publisher, category, pages, published, and actions.
 */
class BooksView {

    /**
     * Initialize the view, grab the table body reference, and load data.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/books.php';
        this.table = document.querySelector('#book-table tbody');
        this.load();
    }

    /**
     * Fetch all books from the REST API.
     *
     * @returns {Promise<void>}
     */
    async load() {
        const response = await fetch(this.API);
        const books = await response.json();
        this.render(books);
    }

    /**
     * Render book rows into the table body.
     *
     * Each row shows title, authors, publisher, category, pages, published year,
     * and an Edit button. Disabled books (status === 0) get the 'row-disabled' class.
     *
     * @param {Array<Object>} books - Array of book objects from the API
     */
    render(books) {
        this.table.textContent = '';

        for (const book of books) {
            const row = document.createElement('tr');

            if (book.status === 0) {
                row.className = 'row-disabled';
            }

            const titleCell = document.createElement('td');
            titleCell.textContent = book.title;
            row.appendChild(titleCell);

            const authorsCell = document.createElement('td');
            authorsCell.textContent = book.authors || '';
            row.appendChild(authorsCell);

            const publisherCell = document.createElement('td');
            publisherCell.textContent = book.publisher;
            row.appendChild(publisherCell);

            const categoryCell = document.createElement('td');
            categoryCell.textContent = book.category;
            row.appendChild(categoryCell);

            const pagesCell = document.createElement('td');
            pagesCell.textContent = book.pages || '';
            row.appendChild(pagesCell);

            const publishedCell = document.createElement('td');
            publishedCell.textContent = book.published || '';
            row.appendChild(publishedCell);

            const actionsCell = document.createElement('td');
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'actions';

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/book?id=' + book.book_id;
            });
            actionsDiv.appendChild(editBtn);

            actionsCell.appendChild(actionsDiv);
            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }
}

const booksView = new BooksView();
