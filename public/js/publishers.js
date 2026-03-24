'use strict';

/**
 * Publishers list view — fetches and renders the publishers table.
 *
 * Instantiated automatically when the publishers list page loads.
 * Fetches all publishers from the API and renders them as table rows,
 * marking disabled publishers with the 'row-disabled' CSS class.
 */
class PublishersView {

    /**
     * Initialize the view, grab the table body reference, and load data.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/publishers.php';
        this.table = document.querySelector('#publisher-table tbody');
        this.load();
    }

    /**
     * Fetch all publishers from the REST API.
     *
     * @returns {Promise<void>}
     */
    async load() {
        try {
            const response = await fetch(this.API);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const publishers = await response.json();
            this.render(publishers);
        } catch (error) {
            this.table.textContent = '';
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 2;
            cell.textContent = 'Unable to load data. Please try again later.';
            row.appendChild(cell);
            this.table.appendChild(row);
        }
    }

    /**
     * Render publisher rows into the table body.
     *
     * Each row contains the publisher name and an Edit button.
     * Disabled publishers (status === 0) get the 'row-disabled' class.
     *
     * @param {Array<Object>} publishers - Array of publisher objects from the API
     */
    render(publishers) {
        this.table.textContent = '';

        for (const publisher of publishers) {
            const row = document.createElement('tr');

            if (publisher.status === 0) {
                row.className = 'row-disabled';
            }

            const nameCell = document.createElement('td');
            nameCell.textContent = publisher.name;
            row.appendChild(nameCell);

            const actionsCell = document.createElement('td');
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'actions';

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/publisher?id=' + publisher.publisher_id;
            });
            actionsDiv.appendChild(editBtn);

            actionsCell.appendChild(actionsDiv);
            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }
}

const publishersView = new PublishersView();
