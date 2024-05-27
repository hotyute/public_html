<?php include 'header.php'; ?>

<form id="searchForm">
    <input type="text" id="searchQuery" placeholder="Search users...">
    <button type="submit">Search</button>
</form>
<div id="searchResults"></div>
<table id="rosterTable" border="1">
    <tr>
        <th>Username</th>
        <th>Display Name</th>
        <th>Role</th>
        <th>Devotion</th>
    </tr>
</table>

<?php include 'footer.php'; ?>