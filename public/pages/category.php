<section>
    <h2 id="form-title">Add Category</h2>
    <form id="category-form" novalidate>
        <input type="hidden" id="category-id" value="">
        <div>
            <label for="category-name">Name</label>
            <input type="text" id="category-name" maxlength="100" required autofocus>
            <span class="error" id="category-name-error"></span>
        </div>
        <div id="status-group" class="checkbox-group" hidden>
            <label>
                <input type="checkbox" id="category-status" checked>
                Active
            </label>
            <span class="error" id="category-status-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Save</button>
            <button type="button" id="btn-delete" class="btn-delete" hidden>Delete</button>
            <a href="/bibliotheca/public/categories">Cancel</a>
        </div>
    </form>
</section>
<script src="/bibliotheca/public/js/category.js"></script>
