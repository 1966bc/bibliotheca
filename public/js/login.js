'use strict';

/**
 * Login form — handles authentication via the auth API.
 *
 * On success, redirects to the home page.
 * On failure, shows an error message.
 */
class LoginForm {

    constructor() {
        this.API = '/bibliotheca/public/api/auth.php';
        this.form = document.getElementById('login-form');
        this.username = document.getElementById('username');
        this.password = document.getElementById('password');
        this.error = document.getElementById('login-error');
        this.form.addEventListener('submit', (e) => this.submit(e));
    }

    /**
     * Validate and submit the login form.
     *
     * @param {Event} e - The submit event
     */
    async submit(e) {
        e.preventDefault();
        this.clearErrors();

        const username = this.username.value.trim();
        const password = this.password.value;

        let valid = true;

        if (username === '') {
            this.showError('username', 'Username is required');
            valid = false;
        }

        if (password === '') {
            this.showError('password', 'Password is required');
            valid = false;
        }

        if (!valid) {
            return;
        }

        try {
            const response = await fetch(this.API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password }),
            });

            if (response.ok) {
                window.location.href = '/bibliotheca/public/';
            } else {
                const data = await response.json();
                this.error.textContent = data.error || 'Login failed';
            }
        } catch (error) {
            this.error.textContent = 'Unable to connect. Please try again.';
        }
    }

    /**
     * Show a validation error under a field.
     *
     * @param {string} field - The field name
     * @param {string} message - The error message
     */
    showError(field, message) {
        const input = document.getElementById(field);
        const error = document.getElementById(field + '-error');
        input.classList.add('invalid');
        error.textContent = message;
    }

    /**
     * Clear all validation errors.
     */
    clearErrors() {
        this.error.textContent = '';
        for (const input of this.form.querySelectorAll('input')) {
            input.classList.remove('invalid');
        }
        for (const span of this.form.querySelectorAll('.error')) {
            span.textContent = '';
        }
    }
}

const loginForm = new LoginForm();
