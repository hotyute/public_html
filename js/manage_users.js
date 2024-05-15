// JavaScript for handling search and user editing
document.getElementById('searchForm').addEventListener('submit', function(event) {
    event.preventDefault();
    let query = document.getElementById('searchQuery').value;

    console.log(`Searching for: ${query}`);

    fetch(`/includes/users/search_users.php?query=${query}`)
        .then(response => {
            if (!response.ok) {
                alert('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            alert('Search results:', data);
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
        })
        .catch(error => console.error('There has been a problem with your fetch operation:', error));
});

function loadUserDetails(userId) {
    console.log(`Loading details for user ID: ${userId}`);

    fetch(`/includes/users/get_user_details.php?id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('User details:', data);
            document.getElementById('userId').value = data.id;
            document.getElementById('displayName').value = data.displayname;
            document.getElementById('role').value = data.role;
            document.getElementById('userDetails').style.display = 'block';
        })
        .catch(error => console.error('There has been a problem with your fetch operation:', error));
}

document.getElementById('editUserForm').addEventListener('submit', function(event) {
    event.preventDefault();

    let formData = new FormData(this);
    console.log('Updating user with data:', formData);

    fetch('/includes/users/update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            alert('User updated successfully');
        } else {
            alert('Failed to update user');
        }
    })
    .catch(error => console.error('There has been a problem with your fetch operation:', error));
});
