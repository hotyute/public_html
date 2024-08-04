document.getElementById('searchForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const query = document.getElementById('searchQuery').value;
    fetch(`/includes/users/search_users.php?query=${query}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';
            if (data.users) {
                data.users.forEach(user => {
                    const userDiv = document.createElement('div');
                    userDiv.textContent = `${user.username} (${user.displayname})`;
                    userDiv.addEventListener('click', () => {
                        document.getElementById('userId').value = user.id;
                        document.getElementById('displayName').value = user.displayname;
                        document.getElementById('role').value = user.role;
                        document.getElementById('assignTestUserId').value = user.id;
                        document.getElementById('removeTestUserId').value = user.id;

                        // Fetch assigned tests for this user
                        fetch(`/includes/tests/get_assigned_tests.php?user_id=${user.id}`)
                            .then(response => response.json())
                            .then(data => {
                                const assignedTestsDiv = document.getElementById('assignedTests');
                                assignedTestsDiv.innerHTML = '';
                                if (data.tests) {
                                    data.tests.forEach(test => {
                                        const testDiv = document.createElement('div');
                                        testDiv.textContent = test.test_name;
                                        assignedTestsDiv.appendChild(testDiv);
                                    });

                                    const assignTestSelect = document.getElementById('assignTestId');
                                    const removeTestSelect = document.getElementById('removeTestId');
                                    assignTestSelect.innerHTML = '';
                                    removeTestSelect.innerHTML = '';

                                    // Populate the assign test select box with tests not assigned to the user
                                    if (data.available_tests) {
                                        data.available_tests.forEach(test => {
                                            const option = document.createElement('option');
                                            option.value = test.id;
                                            option.textContent = test.test_name;
                                            assignTestSelect.appendChild(option);
                                            document.getElementById('assignTestName').innerHTML = test.test_name;
                                        });
                                    }

                                    // Populate the remove test select box with tests assigned to the user
                                    data.tests.forEach(test => {
                                        const option = document.createElement('option');
                                        option.value = test.id;
                                        option.textContent = test.test_name;
                                        removeTestSelect.appendChild(option);
                                        document.getElementById('removeTestName').innerHTML = test.test_name;
                                    });

                                    // Add event listeners to update the hidden test name fields
                                    assignTestSelect.addEventListener('change', function () {
                                        const selectedOption = assignTestSelect.options[assignTestSelect.selectedIndex];
                                        document.getElementById('assignTestName').value = selectedOption.textContent;
                                    });

                                    removeTestSelect.addEventListener('change', function () {
                                        const selectedOption = removeTestSelect.options[removeTestSelect.selectedIndex];
                                        document.getElementById('removeTestName').value = selectedOption.textContent;
                                    });
                                } else {
                                    console.error('No tests data found:', data);
                                }
                            });
                        document.getElementById('userDetails').style.display = 'block';
                    });
                    resultsDiv.appendChild(userDiv);
                });
            } else {
                console.error('No users data found:', data);
            }
        })
        .catch(error => console.error('There has been a problem with your fetch operation:', error));
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

document.getElementById('editUserForm').addEventListener('submit', function (event) {
    event.preventDefault();

    // FormData uses the 'name' attribute to collect data
    const formData = new FormData(this);

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
        })
        .catch(error => console.error('Error:', error));
});
