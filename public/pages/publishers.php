<section>
    <div class="section-header">
        <h2>Publishers</h2>
<?php if ($isLoggedIn): ?>
        <a href="/bibliotheca/public/publisher" class="btn">Add new publisher</a>
<?php endif; ?>
    </div>
    <table id="publisher-table">
        <thead>
            <tr>
                <th>Name</th>
                <th></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</section>
<script src="/bibliotheca/public/js/publishers.js"></script>
