'use strict';

/**
 * Publisher add/edit form — handles creating and updating publishers.
 *
 * Instantiated automatically on the publisher form page.
 * Detects edit mode from the `?id=` URL parameter.
 * Validates input client-side, then sends POST (create) or PUT (update)
 * requests to the REST API. Also handles hard-delete with confirmation.
 */
class PublisherForm {

    /**
     * Initialize form references, bind event listeners, and check for edit mode.
     */
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

    /**
     * Check if the URL contains an `?id=` parameter to enter edit mode.
     * If present, loads the existing record into the form.
     */
    checkEdit() {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');

        if (id) {
            this.loadRecord(parseInt(id));
        }
    }

    /**
     * Fetch a publisher by ID and populate the form fields for editing.
     * Shows the status checkbox and delete button (hidden in create mode).
     *
     * @param {number} id - Publisher ID to load
     * @returns {Promise<void>}
     */
    async loadRecord(id) {
        try {
            const response = await fetch(this.API + '?id=' + id);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const publisher = await response.json();

            this.inputId.value = publisher.publisher_id;
            this.inputName.value = publisher.name;
            this.inputStatus.checked = publisher.status === 1;
            this.statusGroup.hidden = false;
            this.btnDelete.hidden = false;
            this.title.textContent = 'Edit Publisher';
            this.inputName.focus();
        } catch (error) {
            alert('Unable to load record. Please try again later.');
        }
    }

    /**
     * Validate the form: name must not be empty.
     *
     * @returns {boolean} True if the form is valid
     */
    validate() {
        this.clearErrors();

        if (this.inputName.value.trim() === '') {
            this.showError('publisher-name', 'Name is required');
            return false;
        }

        return true;
    }

    /**
     * Display a validation error on a specific form field.
     * Adds the 'invalid' CSS class and sets the error message.
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
     * Save the publisher to the server via the REST API.
     *
     * Validates the form first. If the hidden field publisher-id is empty,
     * if (id === '') sends a POST request (create new). 
     * If it has a value, sends a PUT
     * request (update existing). On success, redirects to the publishers
     * list. On API error (e.g. duplicate name, 409), shows the server
     * error message on the form field.
     *
     * @returns {Promise<void>}
     */
    async save() {
        if (!this.validate()) {
            return;
        }

        const id = this.inputId.value;
        const name = this.inputName.value.trim();

        try {
            let response;

            if (id === '') {
                response = await fetch(this.API, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify({name: name}),
                });
            } else {
                response = await fetch(this.API, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
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
        } catch (error) {
            alert('Unable to save. Please try again later.');
        }
    }

    /**
     * Delete the publisher after user confirmation.
     * Sends a DELETE request to the API. On error (e.g. has books), shows an alert.
     *
     * @returns {Promise<void>}
     */
    async remove() {
        if (!confirm('Permanently delete this publisher? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;

        try {
            const response = await fetch(this.API, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({publisher_id: parseInt(id)}),
            });

            if (!response.ok) {
                const result = await response.json();
                alert(result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/publishers';
        } catch (error) {
            alert('Unable to delete. Please try again later.');
        }
    }
}

const publisherForm = new PublisherForm();
