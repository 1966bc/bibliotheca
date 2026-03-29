<section>
    <div class="section-header">
        <h2>Books</h2>
<?php if ($isLoggedIn): ?>
        <a href="/bibliotheca/public/book" class="btn">Add new book</a>
<?php endif; ?>
    </div>
    <input type="text" id="book-search" placeholder="Search by title or author…" autocomplete="off" autofocus>
    <table id="book-table">
        <thead>
            <tr>
                <th data-sort="title">Title</th>
                <th data-sort="authors">Authors</th>
                <th data-sort="publisher">Publisher</th>
                <th data-sort="category">Category</th>
                <th data-sort="pages">Pages</th>
                <th data-sort="published">Published</th>
                <th></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div id="pagination"></div>
</section>
<script src="/bibliotheca/public/js/books.js"></script>
