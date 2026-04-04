'use strict';

/**
 * Book add/edit form — handles creating and updating books.
 *
 * Instantiated automatically on the book form page.
 * Detects edit mode from the `?id=` URL parameter.
 * Loads publisher and category dropdowns from the API (active records only),
 * applies numeric-only input filters on pages/published fields,
 * validates all fields, and sends POST/PUT/DELETE to the REST API.
 */
class BookForm {

    /**
     * Initialize form references, bind event listeners, set up numeric filters,
     * and start loading the select dropdowns.
     */
    constructor() {
        this.API = '/bibliotheca/public/api/books.php';
        this.form = document.querySelector('#book-form');
        this.inputId = document.querySelector('#book-id');
        this.inputTitle = document.querySelector('#book-title');
        this.selectPublisher = document.querySelector('#book-publisher');
        this.selectCategory = document.querySelector('#book-category');
        this.selectAuthors = document.querySelector('#book-authors');
        this.inputPages = document.querySelector('#book-pages');
        this.inputPublished = document.querySelector('#book-published');
        this.inputStatus = document.querySelector('#book-status');
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

        // Allow only digits in numeric fields
        const numericFilter = (e) => {
            const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];

            if (allowed.includes(e.key)) {
                return;
            }

            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
            }
        };

        this.inputPages.addEventListener('keydown', numericFilter);
        this.inputPublished.addEventListener('keydown', numericFilter);

        this.loadSelects();
    }

    /**
     * Fetch active publishers and categories from the API and populate
     * their respective `<select>` dropdowns. Then check for edit mode.
     *
     * @returns {Promise<void>}
     */
    async loadSelects() {
        try {
            const publisherRes = await fetch('/bibliotheca/public/api/publishers.php?active=1');

            if (!publisherRes.ok) {
                throw new Error(publisherRes.statusText);
            }

            const publishers = await publisherRes.json();

            for (let i = 0; i < publishers.length; i++) {
                const option = document.createElement('option');
                option.value = publishers[i].publisher_id;
                option.textContent = publishers[i].name;
                this.selectPublisher.appendChild(option);
            }

            const categoryRes = await fetch('/bibliotheca/public/api/categories.php?active=1');

            if (!categoryRes.ok) {
                throw new Error(categoryRes.statusText);
            }

            const categories = await categoryRes.json();

            for (let i = 0; i < categories.length; i++) {
                const option = document.createElement('option');
                option.value = categories[i].category_id;
                option.textContent = categories[i].name;
                this.selectCategory.appendChild(option);
            }

            const authorRes = await fetch('/bibliotheca/public/api/authors.php?active=1');

            if (!authorRes.ok) {
                throw new Error(authorRes.statusText);
            }

            const authors = await authorRes.json();

            for (let i = 0; i < authors.length; i++) {
                const option = document.createElement('option');
                option.value = authors[i].author_id;
                option.textContent = authors[i].last_name + ', ' + authors[i].first_name;
                this.selectAuthors.appendChild(option);
            }

            this.checkEdit();
        } catch (error) {
            alert('Unable to load form data. Please try again later.');
        }
    }

    /**
     * Check if the URL contains an `?id=` parameter to enter edit mode.
     */
    checkEdit() {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');

        if (id) {
            this.loadRecord(parseInt(id));
        }
    }

    /**
     * Fetch a book by ID and populate the form fields for editing.
     *
     * @param {number} id - Book ID to load
     * @returns {Promise<void>}
     */
    async loadRecord(id) {
        try {
            const response = await fetch(this.API + '?id=' + id);

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const book = await response.json();

            this.inputId.value = book.book_id;
            this.inputTitle.value = book.title;
            this.selectPublisher.value = book.publisher_id;
            this.selectCategory.value = book.category_id;
            this.inputPages.value = book.pages || '';
            this.inputPublished.value = book.published || '';
            if (book.authors) {
                const authorIds = book.authors.map(a => String(a.author_id));
                for (let i = 0; i < this.selectAuthors.options.length; i++) {
                    this.selectAuthors.options[i].selected = authorIds.includes(this.selectAuthors.options[i].value);
                }
            }

            this.inputStatus.checked = book.status === 1;
            this.statusGroup.hidden = false;
            this.btnDelete.hidden = false;
            this.title.textContent = 'Edit Book';
            this.inputTitle.focus();
        } catch (error) {
            alert('Unable to load record. Please try again later.');
        }
    }

    /**
     * Validate all form fields.
     *
     * Rules:
     *   - Title: required
     *   - Publisher: required (select)
     *   - Category: required (select)
     *   - Pages: required, between 1 and 99999
     *   - Published: required, between 1450 (invention of printing press) and next year
     *
     * @returns {boolean} True if all validations pass
     */
    validate() {
        this.clearErrors();
        let valid = true;

        if (this.inputTitle.value.trim() === '') {
            this.showError('book-title', 'Title is required');
            valid = false;
        }

        if (this.selectPublisher.value === '') {
            this.showError('book-publisher', 'Publisher is required');
            valid = false;
        }

        if (this.selectCategory.value === '') {
            this.showError('book-category', 'Category is required');
            valid = false;
        }

        const pages = this.inputPages.value;
        if (pages === '') {
            this.showError('book-pages', 'Pages is required');
            valid = false;
        } else if (parseInt(pages) < 1 || parseInt(pages) > 99999) {
            this.showError('book-pages', 'Pages must be between 1 and 99999');
            valid = false;
        }

        const published = this.inputPublished.value;
        if (published === '') {
            this.showError('book-published', 'Published year is required');
            valid = false;
        } else if (parseInt(published) < 1450) {
            this.showError('book-published', 'Year must be 1450 or later, because print wasn\'t invented yet');
            valid = false;
        } else if (parseInt(published) > new Date().getFullYear() + 1) {
            this.showError('book-published', 'Year cannot be in the far future');
            valid = false;
        }

        return valid;
    }

    /**
     * Display a validation error on a specific form field.
     *
     * @param {string} fieldId - The DOM ID of the input/select element
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
     * Save the book: POST for new, PUT for existing.
     * On success, redirects to the books list.
     *
     * @returns {Promise<void>}
     */
    async save() {
        if (!this.validate()) {
            return;
        }

        const id = this.inputId.value;
        const title = this.inputTitle.value.trim();
        const publisherId = parseInt(this.selectPublisher.value);
        const categoryId = parseInt(this.selectCategory.value);
        const pages = this.inputPages.value;
        const published = this.inputPublished.value;

        const authorIds = [];
        for (let i = 0; i < this.selectAuthors.selectedOptions.length; i++) {
            authorIds.push(parseInt(this.selectAuthors.selectedOptions[i].value));
        }

        const payload = {
            publisher_id: publisherId,
            category_id: categoryId,
            title: title,
            pages: pages,
            published: published,
            author_ids: authorIds,
        };
        
        //console.log(payload);
        //return;

        try {
            let response;

            if (id === '') {
                response = await fetch(this.API, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(payload),
                });
            } else {
                payload.book_id = parseInt(id);
                payload.status = this.inputStatus.checked ? 1 : 0;
                response = await fetch(this.API, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(payload),
                });
            }

            if (!response.ok) {
                const result = await response.json();
                this.showError('book-title', result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/';
        } catch (error) {
            alert('Unable to save. Please try again later.');
        }
    }

    /**
     * Delete the book after user confirmation.
     * Also removes associated book_author junction records on the server side.
     *
     * @returns {Promise<void>}
     */
    async remove() {
        if (!confirm('Permanently delete this book? This cannot be undone.')) {
            return;
        }

        const id = this.inputId.value;

        try {
            const response = await fetch(this.API, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({book_id: parseInt(id)}),
            });

            if (!response.ok) {
                const result = await response.json();
                alert(result.error);
                return;
            }

            window.location.href = '/bibliotheca/public/';
        } catch (error) {
            alert('Unable to delete. Please try again later.');
        }
    }
}

const bookForm = new BookForm();
