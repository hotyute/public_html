<!-- admin/manage_users.php -->
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('../header.php'); // Admin panel header
include('includes/config.php'); // Database connection and other configuration

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}
?>

<div class="admin-container">
    <h2>User Management</h2>
    <form id="searchForm">
        <input type="text" id="searchQuery" placeholder="Search by username or display name">
        <button type="submit">Search</button>
    </form>
    
    <div id="searchResults"></div>
    <div id="userDetails" style="display: none;">
        <form id="editUserForm">
            <input type="hidden" id="userId" name="userId">
            <label for="displayName">Display Name:</label>
            <input type="text" id="displayName" name="displayName">
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="admin">Admin</option>
                <option value="editor">Editor</option>
                <option value="member">Member</option>
            </select>
            <button type="submit">Update User</button>
        </form>
    </div>
</div>

<?php
include('../footer.php'); // Admin panel footer
?>
