<section>
    <h2 id="form-title">Add Publisher</h2>
    <form id="publisher-form" novalidate>
        <input type="hidden" id="publisher-id" value="">
        <div>
            <label for="publisher-name">Name</label>
            <input type="text" id="publisher-name" maxlength="100" required>
            <span class="error" id="publisher-name-error"></span>
        </div>
        <div class="form-actions">
            <button type="submit">Save</button>
            <a href="/bibliotheca/public/publishers">Cancel</a>
        </div>
    </form>
</section>
<script src="/bibliotheca/public/js/publisher.js"></script>
