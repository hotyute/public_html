document.getElementById('searchForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const query = document.getElementById('searchQuery').value.trim();
    if (query.length < 2) {
        alert('Please enter at least 2 characters.');
        return;
    }
    fetch(`/includes/users/search_users.php?query=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';
            if (data.users && data.users.length) {
                data.users.forEach(user => {
                    const userDiv = document.createElement('div');
                    userDiv.textContent = `${user.username} (${user.displayname})`;
                    userDiv.style.cursor = 'pointer';
                    userDiv.addEventListener('click', () => {
                        document.getElementById('userId').value = user.id;
                        document.getElementById('displayName').value = user.displayname;
                        document.getElementById('role').value = user.role;
                        document.getElementById('assignTestUserId').value = user.id;
                        document.getElementById('removeTestUserId').value = user.id;

                        fetch(`/includes/tests/get_assigned_tests.php?user_id=${encodeURIComponent(user.id)}`)
                            .then(response => response.json())
                            .then(data => {
                                const assignedTestsDiv = document.getElementById('assignedTests');
                                assignedTestsDiv.innerHTML = '';
                                const assignTestSelect = document.getElementById('assignTestId');
                                const removeTestSelect = document.getElementById('removeTestId');
                                const assignTestName = document.getElementById('assignTestName');
                                const removeTestName = document.getElementById('removeTestName');

                                assignTestSelect.innerHTML = '';
                                removeTestSelect.innerHTML = '';

                                if (data.tests) {
                                    data.tests.forEach(test => {
                                        const t = document.createElement('div');
                                        t.textContent = test.test_name;
                                        assignedTestsDiv.appendChild(t);
                                        const opt = document.createElement('option');
                                        opt.value = test.id;
                                        opt.textContent = test.test_name;
                                        removeTestSelect.appendChild(opt);
                                    });
                                }

                                if (data.available_tests) {
                                    data.available_tests.forEach(test => {
                                        const opt = document.createElement('option');
                                        opt.value = test.id;
                                        opt.textContent = test.test_name;
                                        assignTestSelect.appendChild(opt);
                                    });
                                }

                                if (assignTestSelect.options.length > 0) {
                                    assignTestName.value = assignTestSelect.options[assignTestSelect.selectedIndex].textContent;
                                }
                                if (removeTestSelect.options.length > 0) {
                                    removeTestName.value = removeTestSelect.options[removeTestSelect.selectedIndex].textContent;
                                }

                                assignTestSelect.addEventListener('change', function () {
                                    assignTestName.value = assignTestSelect.options[assignTestSelect.selectedIndex].textContent;
                                });
                                removeTestSelect.addEventListener('change', function () {
                                    removeTestName.value = removeTestSelect.options[removeTestSelect.selectedIndex].textContent;
                                });
                            })
                            .catch(err => console.error('Fetch tests error:', err));

                        document.getElementById('userDetails').style.display = 'block';
                    });
                    resultsDiv.appendChild(userDiv);
                });
            } else {
                resultsDiv.textContent = 'No users found.';
            }
        })
        .catch(error => console.error('Search error:', error));
});

document.getElementById('editUserForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) formData.append('csrf_token', token);

    fetch('/includes/users/update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) alert('User updated successfully');
        else alert(data.message || 'Failed to update user');
    })
    .catch(error => console.error('Update error:', error));
});