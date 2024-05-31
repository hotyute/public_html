<?php include 'header.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>

<main>
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
</main>

<?php include 'footer.php'; ?>