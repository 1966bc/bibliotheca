'use strict';

class PublishersView {

    constructor() {
        this.API = '/bibliotheca/public/api/publishers.php';
        this.table = document.querySelector('#publisher-table tbody');
        this.load();
    }

    async load() {
        const response = await fetch(this.API);
        const publishers = await response.json();
        this.render(publishers);
    }

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
