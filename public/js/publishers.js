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

            const nameCell = document.createElement('td');
            nameCell.textContent = publisher.name;
            row.appendChild(nameCell);

            const actionsCell = document.createElement('td');

            const editBtn = document.createElement('button');
            editBtn.textContent = 'Edit';
            editBtn.addEventListener('click', () => {
                window.location.href = '/bibliotheca/public/publisher?id=' + publisher.publisher_id;
            });
            actionsCell.appendChild(editBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'Delete';
            deleteBtn.addEventListener('click', () => {
                this.remove(publisher.publisher_id);
            });
            actionsCell.appendChild(deleteBtn);

            row.appendChild(actionsCell);
            this.table.appendChild(row);
        }
    }

    async remove(id) {
        if (!confirm('Delete this publisher?')) {
            return;
        }

        await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({publisher_id: id}),
        });

        this.load();
    }
}

const publishersView = new PublishersView();
