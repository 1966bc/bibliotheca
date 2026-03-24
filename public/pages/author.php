<section>
    <h2 id="form-title">Add Author</h2>
    <form id="author-form" novalidate>
        <input type="hidden" id="author-id" value="">
        <div>
            <label for="author-first-name">First name</label>
            <input type="text" id="author-first-name" maxlength="100" required>
            <span class="error" id="author-first-name-error"></span>
        </div>
        <div>
            <label for="author-last-name">Last name</label>
            <input type="text" id="author-last-name" maxlength="100" required>
            <span class="error" id="author-last-name-error"></span>
        </div>
        <div>
            <label for="author-birthdate">Birthdate</label>
            <input type="date" id="author-birthdate">
            <span class="error" id="author-birthdate-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Save</button>
            <a href="/bibliotheca/public/authors">Cancel</a>
        </div>
    </form>
</section>
<script src="/bibliotheca/public/js/author.js"></script>
