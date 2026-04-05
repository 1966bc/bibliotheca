'use strict';

/**
 * Author add/edit form — handles creating and updating authors.
 *
 * Instantiated automatically on the author form page.
 * Detects edit mode from the `?id=` URL parameter.
 * Validates first name, last name (required), and birthdate (optional, year >= 1000, not future).
 * Sends POST (create) or PUT (update) to the REST API.
 */
class AuthorForm {

    /**
     * Initialize form references, bind event listeners, and check for edit mode.
     */
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

    /**
     * Check if the URL contains an `?id=` parameter to enter edit mode.
     */
    checkEdit() {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');

        if (id) {
            this.load(parseInt(id));
        }
    }

    /**
     * Fetch an author by ID and populate the form fields for editing.
     *
     * @param {number} id - Author ID to load
     * @returns {Promise<void>}
     */
    async load(id) {
        try {
            const response = await fetch(this.API + '?id=' + id);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const author = await response.json();
            this.render(author);
        } catch (error) {
            alert('Unable to load record. Please try again later.');
        }
    }

    render(author) {
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

    /**
     * Validate the form fields.
     *
     * Rules:
     *   - First name: required
     *   - Last name: required
     *   - Birthdate: optional, but if set, year must be >= 1000 and not in the future
     *
     * @returns {boolean} True if all validations pass
     */
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

    /**
     * Display a validation error on a specific form field.
     *
     * @param {string} fieldId - The DOM ID of the input element
     * @param {string} message - The error message to display
     */
    showError(fieldId, message) {
        const input = document.querySelector('#' + fieldId);
        const error = document.querySelector('#' + fieldId + '-error');
        input.classList.add('invalid');
        error.textContent = message;
    }

    /**
     * Clear all validation errors and remove 'invalid' CSS classes.
     */
    clearErrors() {
        const errors = this.form.querySelectorAll('.error');
        for (let i = 0; i < errors.length; i++) {
            errors[i].textContent = '';
        }

        const invalids = this.form.querySelectorAll('.invalid');
        for (let i = 0; i < invalids.length; i++) {
            invalids[i].classList.remove('invalid');
        }
    }

    /**
     * Save the author: POST for new, PUT for existing.
     * On success, redirects to the authors list.
     *
     * @returns {Promise<void>}
     */
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

        try {
            let response;

            if (id === '') {
                response = await fetch(this.API, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(payload),
                });
            } else {
                payload.author_id = parseInt(id);
                payload.status = this.inputStatus.checked ? 1 : 0;
                response = await fetch(this.API, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(payload),
                });
            }

            if (!response.ok) {
                const result = await response.json();
                this.showError('author-last-name', result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/authors';
        } catch (error) {
            alert('Unable to save. Please try again later.');
        }
    }

    /**
     * Delete the author after user confirmation.
     *
     * @returns {Promise<void>}
     */
    async remove() {
        if (!confirm('Permanently delete this author? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;
        const payload = {author_id: parseInt(id)};

        try {
            const response = await fetch(this.API, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const result = await response.json();
                alert(result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/authors';
        } catch (error) {
            alert('Unable to delete. Please try again later.');
        }
    }
}

const authorForm = new AuthorForm();
