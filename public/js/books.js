'use strict';

/**
 * Books list view — fetches, filters, sorts, paginates and renders the books table.
 *
 * Instantiated automatically when the home page loads.
 * Fetches all books from the API, supports client-side search filtering,
 * column sorting (click header to toggle asc/desc), and pagination.
 */
class BooksView {

    /**
     * Initialize the view, grab DOM references, and load data.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/books.php';
        this.PAGE_SIZE = 5;
        this.table = document.querySelector('#book-table tbody');
        this.pagination = document.getElementById('pagination');
        this.searchInput = document.getElementById('book-search');
        this.headers = document.querySelectorAll('#book-table th[data-sort]');
        this.books = [];
        this.filtered = [];
        this.page = 1;
        this.sortColumn = null;
        this.sortAsc = true;
        this.searchInput.addEventListener('input', () => this.filter());
        this.initSort();
        this.load();
    }

    /**
     * Attach click listeners to sortable column headers.
     */
    initSort() {
        for (let i = 0; i < this.headers.length; i++) {
            const th = this.headers[i];
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const column = th.dataset.sort;

                if (this.sortColumn === column) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortColumn = column;
                    this.sortAsc = true;
                }

                this.sort();
                this.page = 1;
                this.updateHeaders();
                this.update();
            });
        }
    }

    /**
     * Update header arrows to reflect current sort state.
     */
    updateHeaders() {
        for (let i = 0; i < this.headers.length; i++) {
            const th = this.headers[i];
            const label = th.textContent.replace(/ [▲▼]$/, '');

            if (th.dataset.sort === this.sortColumn) {
                th.textContent = label + (this.sortAsc ? ' ▲' : ' ▼');
            } else {
                th.textContent = label;
            }
        }
    }

    /**
     * Sort the filtered array by the current sort column and direction.
     */
    sort() {
        if (this.sortColumn === null) {
            return;
        }

        const col = this.sortColumn;
        const dir = this.sortAsc ? 1 : -1;
        const isNumeric = col === 'pages' || col === 'published';

        this.filtered.sort((a, b) => {
            let valA = a[col];
            let valB = b[col];

            if (isNumeric) {
                valA = valA || 0;
                valB = valB || 0;
                return (valA - valB) * dir;
            }

            valA = (valA || '').toLowerCase();
            valB = (valB || '').toLowerCase();

            if (valA < valB) {
                return -1 * dir;
            }
            if (valA > valB) {
                return 1 * dir;
            }
            return 0;
        });
    }

    /**
     * Fetch all books from the REST API.
     *
     * @returns {Promise<void>}
     */
    async load() {
        try {
            const response = await fetch(this.API);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            this.books = await response.json();
            this.filtered = this.books.slice();
            this.page = 1;
            this.update();
        } catch (error) {
            this.table.textContent = '';
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.textContent = 'Unable to load data. Please try again later.';
            row.appendChild(cell);
            this.table.appendChild(row);
        }
    }

    /**
     * Filter books by title or authors based on the search input value.
     * Resets to page 1 on every new search. Preserves current sort.
     */
    filter() {
        const term = this.searchInput.value.toLowerCase().trim();

        if (term === '') {
            this.filtered = this.books.slice();
        } else {
            this.filtered = this.books.filter(book => {
                const title = book.title.toLowerCase();
                const authors = (book.authors || '').toLowerCase();
                return title.includes(term) || authors.includes(term);
            });
        }

        this.sort();
        this.page = 1;
        this.update();
    }

    /**
     * Render the current page of books and the pagination controls.
     */
    update() {
        const start = (this.page - 1) * this.PAGE_SIZE;
        const pageBooks = this.filtered.slice(start, start + this.PAGE_SIZE);
        this.render(pageBooks);
        this.renderPagination();
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

        for (let i = 0; i < books.length; i++) {
            const book = books[i];
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

            if (AUTH.authenticated) {
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'actions';

                const editBtn = document.createElement('button');
                editBtn.textContent = 'Edit';
                editBtn.addEventListener('click', () => {
                    window.location.href = '/bibliotheca/public/book?id=' + book.book_id;
                });
                actionsDiv.appendChild(editBtn);

                actionsCell.appendChild(actionsDiv);
            }

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }

    /**
     * Render pagination controls (Prev / page numbers / Next).
     * Hidden when all results fit in one page.
     */
    renderPagination() {
        this.pagination.textContent = '';
        const totalPages = Math.ceil(this.filtered.length / this.PAGE_SIZE);

        if (totalPages <= 1) {
            return;
        }

        const prev = document.createElement('button');
        prev.textContent = 'Prev';
        prev.disabled = this.page === 1;
        prev.addEventListener('click', () => this.goToPage(this.page - 1));
        this.pagination.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            if (i === this.page) {
                btn.className = 'active';
            }
            btn.addEventListener('click', () => this.goToPage(i));
            this.pagination.appendChild(btn);
        }

        const next = document.createElement('button');
        next.textContent = 'Next';
        next.disabled = this.page === totalPages;
        next.addEventListener('click', () => this.goToPage(this.page + 1));
        this.pagination.appendChild(next);
    }

    /**
     * Navigate to a specific page.
     *
     * @param {number} page - The page number to display
     */
    goToPage(page) {
        this.page = page;
        this.update();
    }
}

const booksView = new BooksView();
