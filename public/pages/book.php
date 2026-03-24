<section>
    <h2 id="form-title">Add Book</h2>
    <form id="book-form" novalidate>
        <input type="hidden" id="book-id" value="">
        <div>
            <label for="book-title">Title</label>
            <input type="text" id="book-title" required>
            <span class="error" id="book-title-error"></span>
        </div>
        <div>
            <label for="book-publisher">Publisher</label>
            <select id="book-publisher" required>
                <option value="">-- select --</option>
            </select>
            <span class="error" id="book-publisher-error"></span>
        </div>
        <div>
            <label for="book-category">Category</label>
            <select id="book-category" required>
                <option value="">-- select --</option>
            </select>
            <span class="error" id="book-category-error"></span>
        </div>
        <div>
            <label for="book-pages">Pages</label>
            <input type="number" id="book-pages" min="1" max="99999" required>
            <span class="error" id="book-pages-error"></span>
        </div>
        <div>
            <label for="book-published">Published (year)</label>
            <input type="number" id="book-published" min="1450" required>
            <span class="error" id="book-published-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Save</button>
            <a href="/bibliotheca/public/books">Cancel</a>
        </div>
    </form>
</section>
<script src="/bibliotheca/public/js/book.js"></script>
