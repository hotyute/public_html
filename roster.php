<?php include 'header.php'; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>

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

<p style="border:2px solid DodgerBlue; color:#FF0000; font-size: 180%; text-align: center;">These members have been displayed for testing. Other members have been hidden for privacy purposes.</p>

<?php include 'footer.php'; ?>
