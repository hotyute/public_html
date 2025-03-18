<?php include 'header.php'; ?>

<div class="main-container">
    <main>
        <div class="roster-container">
            <form id="searchForm" class="search-form">
                <input type="text" id="searchQuery" placeholder="Search users...">
                <button type="submit">Search</button>
            </form>
            <div id="searchResults"></div>
            <table id="rosterTable" class="roster-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Display Name</th>
                        <th>Role</th>
                        <th>Devotion</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be populated by JavaScript -->
                </tbody>
            </table>
            <p class="notice">These members have been displayed for testing purposes. Other members have been hidden due to privacy concerns.</p>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>