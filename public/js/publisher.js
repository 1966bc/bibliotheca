'use strict';

class PublisherForm {

    constructor() {
        this.API = '/bibliotheca/public/api/publishers.php';
        this.form = document.querySelector('#publisher-form');
        this.inputId = document.querySelector('#publisher-id');
        this.inputName = document.querySelector('#publisher-name');
        this.inputStatus = document.querySelector('#publisher-status');
        this.statusGroup = document.querySelector('#status-group');
        this.btnDelete = document.querySelector('#btn-delete');
        this.title = document.querySelector('#form-title');

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.save();
        });

        this.btnDelete.addEventListener('click', () => {
            this.remove();
        });

        this.checkEdit();
    }

    checkEdit() {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');

        if (id) {
            this.loadRecord(parseInt(id));
        }
    }

    async loadRecord(id) {
        const response = await fetch(this.API + '?id=' + id);
        const publisher = await response.json();

        this.inputId.value = publisher.publisher_id;
        this.inputName.value = publisher.name;
        this.inputStatus.checked = publisher.status === 1;
        this.statusGroup.hidden = false;
        this.btnDelete.hidden = false;
        this.title.textContent = 'Edit Publisher';
        this.inputName.focus();
    }

    validate() {
        this.clearErrors();

        if (this.inputName.value.trim() === '') {
            this.showError('publisher-name', 'Name is required');
            return false;
        }

        return true;
    }

    showError(fieldId, message) {
        const input = document.querySelector('#' + fieldId);
        const error = document.querySelector('#' + fieldId + '-error');
        input.classList.add('invalid');
        error.textContent = message;
    }

    clearErrors() {
        const errors = this.form.querySelectorAll('.error');
        for (const error of errors) {
            error.textContent = '';
        }

        const invalids = this.form.querySelectorAll('.invalid');
        for (const input of invalids) {
            input.classList.remove('invalid');
        }
    }

    async save() {
        if (!this.validate()) {
            return;
        }

        const id = this.inputId.value;
        const name = this.inputName.value.trim();
        let response;

        if (id === '') {
            response = await fetch(this.API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({name: name}),
            });
        } else {
            response = await fetch(this.API, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    publisher_id: parseInt(id),
                    name: name,
                    status: this.inputStatus.checked ? 1 : 0,
                }),
            });
        }

        if (!response.ok) {
            const result = await response.json();
            this.showError('publisher-name', result.error);
            return;
        }

        window.location.href = '/bibliotheca/public/publishers';
    }

    async remove() {
        if (!confirm('Permanently delete this publisher? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;

        const response = await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({publisher_id: parseInt(id)}),
        });

        if (!response.ok) {
            const result = await response.json();
            alert(result.error);
            return;
        }

        window.location.href = '/bibliotheca/public/publishers';
    }
}

const publisherForm = new PublisherForm();
