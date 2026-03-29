'use strict';

/**
 * Global authentication state — reads the meta tag and exposes
 * a boolean for other scripts. Handles the logout link.
 */
const AUTH = {
    authenticated: document.querySelector('meta[name="authenticated"]')?.content === '1',
};

(function () {
    const logoutLink = document.getElementById('logout-link');

    if (logoutLink) {
        logoutLink.addEventListener('click', async (e) => {
            e.preventDefault();

            await fetch('/bibliotheca/public/api/auth.php', {
                method: 'DELETE',
            });

            window.location.href = '/bibliotheca/public/';
        });
    }
})();
