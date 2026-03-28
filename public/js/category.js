'use strict';

/**
 * Category add/edit form — handles creating and updating categories.
 *
 * Instantiated automatically on the category form page.
 * Detects edit mode from the `?id=` URL parameter.
 * Validates input client-side, then sends POST (create) or PUT (update)
 * requests to the REST API. Also handles hard-delete with confirmation.
 */
class CategoryForm {

    /**
     * Initialize form references, bind event listeners, and check for edit mode.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/categories.php';
        this.form = document.querySelector('#category-form');
        this.inputId = document.querySelector('#category-id');
        this.inputName = document.querySelector('#category-name');
        this.inputStatus = document.querySelector('#category-status');
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
     * Fetch a category by ID and populate the form fields for editing.
     *
     * @param {number} id - Category ID to load
     * @returns {Promise<void>}
     */
    async loadRecord(id) {
        try {
            const response = await fetch(this.API + '?id=' + id);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const category = await response.json();

            this.inputId.value = category.category_id;
            this.inputName.value = category.name;
            this.inputStatus.checked = category.status === 1;
            this.statusGroup.hidden = false;
            this.btnDelete.hidden = false;
            this.title.textContent = 'Edit Category';
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
            this.showError('category-name', 'Name is required');
            return false;
        }

        return true;
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
        for (const error of errors) {
            error.textContent = '';
        }

        const invalids = this.form.querySelectorAll('.invalid');
        for (const input of invalids) {
            input.classList.remove('invalid');
        }
    }

    /**
     * Save the category: POST for new, PUT for existing.
     * On success, redirects to the categories list.
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
                        category_id: parseInt(id),
                        name: name,
                        status: this.inputStatus.checked ? 1 : 0,
                    }),
                });
            }

            if (!response.ok) {
                const result = await response.json();
                this.showError('category-name', result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/categories';
        } catch (error) {
            alert('Unable to save. Please try again later.');
        }
    }

    /**
     * Delete the category after user confirmation.
     *
     * @returns {Promise<void>}
     */
    async remove() {
        if (!confirm('Permanently delete this category? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;

        try {
            const response = await fetch(this.API, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({category_id: parseInt(id)}),
            });

            if (!response.ok) {
                const result = await response.json();
                alert(result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/categories';
        } catch (error) {
            alert('Unable to delete. Please try again later.');
        }
    }
}

const categoryForm = new CategoryForm();
