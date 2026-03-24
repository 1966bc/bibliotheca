'use strict';

class BookForm {

    constructor() {
        this.API = '/bibliotheca/public/api/books.php';
        this.form = document.querySelector('#book-form');
        this.inputId = document.querySelector('#book-id');
        this.inputTitle = document.querySelector('#book-title');
        this.selectPublisher = document.querySelector('#book-publisher');
        this.selectCategory = document.querySelector('#book-category');
        this.inputPages = document.querySelector('#book-pages');
        this.inputPublished = document.querySelector('#book-published');
        this.title = document.querySelector('#form-title');

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.save();
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

    async loadSelects() {
        const publisherRes = await fetch('/bibliotheca/public/api/publishers.php');
        const publishers = await publisherRes.json();

        for (const publisher of publishers) {
            const option = document.createElement('option');
            option.value = publisher.publisher_id;
            option.textContent = publisher.name;
            this.selectPublisher.appendChild(option);
        }

        const categoryRes = await fetch('/bibliotheca/public/api/categories.php');
        const categories = await categoryRes.json();

        for (const category of categories) {
            const option = document.createElement('option');
            option.value = category.category_id;
            option.textContent = category.name;
            this.selectCategory.appendChild(option);
        }

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
        const book = await response.json();

        this.inputId.value = book.book_id;
        this.inputTitle.value = book.title;
        this.selectPublisher.value = book.publisher_id;
        this.selectCategory.value = book.category_id;
        this.inputPages.value = book.pages || '';
        this.inputPublished.value = book.published || '';
        this.title.textContent = 'Edit Book';
        this.inputTitle.focus();
    }

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
        const title = this.inputTitle.value.trim();
        const publisherId = parseInt(this.selectPublisher.value);
        const categoryId = parseInt(this.selectCategory.value);
        const pages = this.inputPages.value;
        const published = this.inputPublished.value;

        const payload = {
            publisher_id: publisherId,
            category_id: categoryId,
            title: title,
            pages: pages,
            published: published,
        };

        if (id === '') {
            await fetch(this.API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
        } else {
            payload.book_id = parseInt(id);
            await fetch(this.API, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
        }

        window.location.href = '/bibliotheca/public/books';
    }
}

const bookForm = new BookForm();
