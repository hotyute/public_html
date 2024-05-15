// JavaScript for handling search and user editing
document.getElementById('searchForm').addEventListener('submit', function(event) {
    event.preventDefault();
    let query = document.getElementById('searchQuery').value;

    alert(query);

    fetch(`/includes/users/search_users.php?query=${query}`)
        .then(response => response.json())
        .then(data => {
            let resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';

            alert(resultsDiv.innerHTML);

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