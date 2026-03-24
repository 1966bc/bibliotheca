'use strict';

class BooksView {

    constructor() {
        this.API = '/bibliotheca/public/api/books.php';
        this.table = document.querySelector('#book-table tbody');
        this.load();
    }

    async load() {
        const response = await fetch(this.API);
        const books = await response.json();
        this.render(books);
    }

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
