<section>
    <h2>Login</h2>
    <form id="login-form" novalidate>
        <div>
            <label for="username">Username</label>
            <input type="text" id="username" required autofocus>
            <span class="error" id="username-error"></span>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" required>
            <span class="error" id="password-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Login</button>
        </div>
        <span class="error" id="login-error"></span>
    </form>
</section>
<script src="/bibliotheca/public/js/login.js"></script>
