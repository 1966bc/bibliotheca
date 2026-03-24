'use strict';

class AuthorsView {

    constructor() {
        this.API = '/bibliotheca/public/api/authors.php';
        this.table = document.querySelector('#author-table tbody');
        this.load();
    }

    async load() {
        const response = await fetch(this.API);
        const authors = await response.json();
        this.render(authors);
    }

    render(authors) {
        this.table.textContent = '';

        for (const author of authors) {
            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            nameCell.textContent = author.last_name + ', ' + author.first_name;
            row.appendChild(nameCell);

            const birthCell = document.createElement('td');
            birthCell.textContent = author.birthdate || '';
            row.appendChild(birthCell);

            const actionsCell = document.createElement('td');

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/author?id=' + author.author_id;
            });
            actionsCell.appendChild(editBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'Delete';
            deleteBtn.addEventListener('click', () => {
                this.remove(author.author_id);
            });
            actionsCell.appendChild(deleteBtn);

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }

    async remove(id) {
        if (!confirm('Delete this author?')) {
            return;
        }

        await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({author_id: id}),
        });

        this.load();
    }
}

const authorsView = new AuthorsView();
