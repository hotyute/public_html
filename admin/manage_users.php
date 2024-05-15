<!-- admin/manage_users.php -->
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('../header.php'); // Admin panel header
include(BASE_URL . 'includes/config.php'); // Database connection and other configuration

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>

<div class="container">
    <h2>User Management</h2>
    <form id="searchForm">
        <input type="text" id="searchQuery" placeholder="Search by username or display name">
        <button type="submit">Search</button>
    </form>
    
    <div id="searchResults"></div>
    <div id="userDetails" style="display: none;">
        <form id="editUserForm">
            <input type="hidden" id="userId">
            <label for="displayName">Display Name:</label>
            <input type="text" id="displayName">
            <label for="role">Role:</label>
            <select id="role">
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <button type="submit">Update User</button>
        </form>
    </div>
</div>

<script>
// JavaScript for handling search and user editing
document.getElementById('searchForm').addEventListener('submit', function(event) {
    event.preventDefault();
    let query = document.getElementById('searchQuery').value;

    fetch(`/includes/users/search_users.php?query=${query}`)
        .then(response => response.json())
        .then(data => {
            let resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';

            data.forEach(user => {
                let userDiv = document.createElement('div');
                userDiv.textContent = `${user.username} (${user.displayname})`;
                userDiv.addEventListener('click', function() {
                    loadUserDetails(user.id);
                });
                resultsDiv.appendChild(userDiv);
            });
        });
});

function loadUserDetails(userId) {
    fetch(`/includes/users/get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('userId').value = data.id;
            document.getElementById('displayName').value = data.displayname;
            document.getElementById('role').value = data.role;
            document.getElementById('userDetails').style.display = 'block';
        });
}

document.getElementById('editUserForm').addEventListener('submit', function(event) {
    event.preventDefault();

    let formData = new FormData(this);

    fetch('/includes/users/update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User updated successfully');
        } else {
            alert('Failed to update user');
        }
    });
});
</script>

<?php
include('footer.php'); // Admin panel footer
?>
