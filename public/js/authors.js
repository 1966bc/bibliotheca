'use strict';

/**
 * Authors list view — fetches and renders the authors table.
 *
 * Instantiated automatically when the authors list page loads.
 * Fetches all authors from the API and renders them as table rows
 * with columns for name (Last, First), birthdate, and actions.
 */
class AuthorsView {

    /**
     * Initialize the view, grab the table body reference, and load data.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/authors.php';
        this.table = document.querySelector('#author-table tbody');
        this.load();
    }

    /**
     * Fetch all authors from the REST API.
     *
     * @returns {Promise<void>}
     */
    async load() {
        const response = await fetch(this.API);
        const authors = await response.json();
        this.render(authors);
    }

    /**
     * Render author rows into the table body.
     *
     * Each row shows "Last, First" name, birthdate, and an Edit button.
     * Disabled authors (status === 0) get the 'row-disabled' class.
     *
     * @param {Array<Object>} authors - Array of author objects from the API
     */
    render(authors) {
        this.table.textContent = '';

        for (const author of authors) {
            const row = document.createElement('tr');

            if (author.status === 0) {
                row.className = 'row-disabled';
            }

            const nameCell = document.createElement('td');
            nameCell.textContent = author.last_name + ', ' + author.first_name;
            row.appendChild(nameCell);

            const birthCell = document.createElement('td');
            birthCell.textContent = author.birthdate || '';
            row.appendChild(birthCell);

            const actionsCell = document.createElement('td');
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'actions';

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/author?id=' + author.author_id;
            });
            actionsDiv.appendChild(editBtn);

            actionsCell.appendChild(actionsDiv);
            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }
}

const authorsView = new AuthorsView();
