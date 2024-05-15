// Verify that the script is being loaded
console.log('JavaScript loaded.');

document.addEventListener('DOMContentLoaded', (event) => {
    console.log('DOM fully loaded and parsed.');

    document.getElementById('searchForm').addEventListener('submit', function(event) {
        console.log('Search form submitted.');
        event.preventDefault();
        let query = document.getElementById('searchQuery').value;

        fetch(`/includes/users/search_users.php?query=${query}`)
            .then(response => {
                console.log('Response status:', response.status); // Log response status
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Search results:', data); // Log the search results
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
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });
    });

    document.getElementById('editUserForm').addEventListener('submit', function(event) {
        event.preventDefault();

        let formData = new FormData(this);

        fetch('/includes/users/update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status); // Log response status
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Update result:', data); // Log the update result
            if (data.success) {
                alert('User updated successfully');
            } else {
                alert('Failed to update user');
            }
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
        });
    });
});

function loadUserDetails(userId) {
    fetch(`/includes/users/get_user_details.php?id=${userId}`)
        .then(response => {
            console.log('Response status:', response.status); // Log response status
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('User details:', data); // Log the user details
            document.getElementById('userId').value = data.id;
            document.getElementById('displayName').value = data.displayname;
            document.getElementById('role').value = data.role;
            document.getElementById('userDetails').style.display = 'block';
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
        });
}
