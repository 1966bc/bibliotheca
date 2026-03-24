<section>
    <h2 id="form-title">Add Category</h2>
    <form id="category-form" novalidate>
        <input type="hidden" id="category-id" value="">
        <div>
            <label for="category-name">Name</label>
            <input type="text" id="category-name" maxlength="100" required>
            <span class="error" id="category-name-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Save</button>
            <a href="/bibliotheca/public/categories">Cancel</a>
        </div>
    </form>
</section>
<script src="/bibliotheca/public/js/category.js"></script>
