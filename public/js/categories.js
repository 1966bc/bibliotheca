'use strict';

class CategoriesView {

    constructor() {
        this.API = '/bibliotheca/public/api/categories.php';
        this.table = document.querySelector('#category-table tbody');
        this.load();
    }

    async load() {
        const response = await fetch(this.API);
        const categories = await response.json();
        this.render(categories);
    }

    render(categories) {
        this.table.textContent = '';

        for (const category of categories) {
            const row = document.createElement('tr');

            if (category.status === 0) {
                row.className = 'row-disabled';
            }

            const nameCell = document.createElement('td');
            nameCell.textContent = category.name;
            row.appendChild(nameCell);

            const actionsCell = document.createElement('td');
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'actions';

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/category?id=' + category.category_id;
            });
            actionsDiv.appendChild(editBtn);

            actionsCell.appendChild(actionsDiv);
            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }
}

const categoriesView = new CategoriesView();
