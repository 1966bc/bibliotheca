'use strict';

class AuthorForm {

    constructor() {
        this.API = '/bibliotheca/public/api/authors.php';
        this.form = document.querySelector('#author-form');
        this.inputId = document.querySelector('#author-id');
        this.inputFirstName = document.querySelector('#author-first-name');
        this.inputLastName = document.querySelector('#author-last-name');
        this.inputBirthdate = document.querySelector('#author-birthdate');
        this.inputStatus = document.querySelector('#author-status');
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
        const author = await response.json();

        this.inputId.value = author.author_id;
        this.inputFirstName.value = author.first_name;
        this.inputLastName.value = author.last_name;
        this.inputBirthdate.value = author.birthdate || '';
        this.inputStatus.checked = author.status === 1;
        this.statusGroup.hidden = false;
        this.btnDelete.hidden = false;
        this.title.textContent = 'Edit Author';
        this.inputFirstName.focus();
    }

    validate() {
        this.clearErrors();
        let valid = true;

        if (this.inputFirstName.value.trim() === '') {
            this.showError('author-first-name', 'First name is required');
            valid = false;
        }

        if (this.inputLastName.value.trim() === '') {
            this.showError('author-last-name', 'Last name is required');
            valid = false;
        }

        const birthdate = this.inputBirthdate.value;
        if (birthdate !== '') {
            const year = new Date(birthdate).getFullYear();

            if (year < 1000) {
                this.showError('author-birthdate', 'Year must be 1000 or later');
                valid = false;
            } else if (year > new Date().getFullYear()) {
                this.showError('author-birthdate', 'Birthdate cannot be in the future');
                valid = false;
            }
        }

        return valid;
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
        const firstName = this.inputFirstName.value.trim();
        const lastName = this.inputLastName.value.trim();
        const birthdate = this.inputBirthdate.value;

        const payload = {
            first_name: firstName,
            last_name: lastName,
            birthdate: birthdate,
        };

        let response;

        if (id === '') {
            response = await fetch(this.API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
        } else {
            payload.author_id = parseInt(id);
            payload.status = this.inputStatus.checked ? 1 : 0;
            response = await fetch(this.API, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
        }

        if (!response.ok) {
            const result = await response.json();
            this.showError('author-last-name', result.error);
            return;
        }

        window.location.href = '/bibliotheca/public/authors';
    }

    async remove() {
        if (!confirm('Permanently delete this author? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;

        const response = await fetch(this.API, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({author_id: parseInt(id)}),
        });

        if (!response.ok) {
            const result = await response.json();
            alert(result.error);
            return;
        }

        window.location.href = '/bibliotheca/public/authors';
    }
}

const authorForm = new AuthorForm();
