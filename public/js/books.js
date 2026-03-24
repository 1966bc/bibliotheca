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

            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'Delete';
            deleteBtn.className = 'btn-delete';
            deleteBtn.addEventListener('click', () => {
                this.remove(book.book_id);
            });
            actionsDiv.appendChild(deleteBtn);
            actionsCell.appendChild(actionsDiv);

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }

    async remove(id) {
        if (!confirm('Delete this book?')) {
            return;
        }

        const response = await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({book_id: id}),
        });

        if (!response.ok) {
            const result = await response.json();
            alert(result.error);
            return;
        }

        this.load();
    }
}

const booksView = new BooksView();
