'use strict';

/**
 * Categories list view — fetches and renders the categories table.
 *
 * Instantiated automatically when the categories list page loads.
 * Fetches all categories from the API and renders them as table rows,
 * marking disabled categories with the 'row-disabled' CSS class.
 */
class CategoriesView {

    /**
     * Initialize the view, grab the table body reference, and load data.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/categories.php';
        this.table = document.querySelector('#category-table tbody');
        this.load();
    }

    /**
     * Fetch all categories from the REST API.
     *
     * @returns {Promise<void>}
     */
    async load() {
        try {
            const response = await fetch(this.API);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const categories = await response.json();
            this.render(categories);
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
     * Render category rows into the table body.
     *
     * Each row contains the category name and an Edit button.
     * Disabled categories (status === 0) get the 'row-disabled' class.
     *
     * @param {Array<Object>} categories - Array of category objects from the API
     */
    render(categories) {
        this.table.textContent = '';

        for (let i = 0; i < categories.length; i++) {
            const category = categories[i];
            const row = document.createElement('tr');

            if (category.status === 0) {
                row.className = 'row-disabled';
            }

            const nameCell = document.createElement('td');
            nameCell.textContent = category.name;
            row.appendChild(nameCell);

            const actionsCell = document.createElement('td');

            if (AUTH.authenticated) {
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'actions';

                const editBtn = document.createElement('button');
                editBtn.textContent = 'Edit';
                editBtn.addEventListener('click', () => {
                    window.location.href = '/bibliotheca/public/category?id=' + category.category_id;
                });
                actionsDiv.appendChild(editBtn);

                actionsCell.appendChild(actionsDiv);
            }

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }
}

const categoriesView = new CategoriesView();
