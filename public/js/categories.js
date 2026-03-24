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

            const nameCell = document.createElement('td');
            nameCell.textContent = category.name;
            row.appendChild(nameCell);

            const actionsCell = document.createElement('td');

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/category?id=' + category.category_id;
            });
            actionsCell.appendChild(editBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'Delete';
            deleteBtn.addEventListener('click', () => {
                this.remove(category.category_id);
            });
            actionsCell.appendChild(deleteBtn);

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }

    async remove(id) {
        if (!confirm('Delete this category?')) {
            return;
        }

        await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({category_id: id}),
        });

        this.load();
    }
}

const categoriesView = new CategoriesView();
