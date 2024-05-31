let currentUserRole = 'guest';
let isEditMode = false; // State to track edit mode

// Fetch and display the roster
function fetchRoster() {
    fetch('/includes/roster/fetch_roster.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(users => {
            let rosterTableBody = document.querySelector('.roster-container #rosterTable tbody');
            rosterTableBody.innerHTML = ''; // Clear the existing rows

            users.forEach(user => {
                let row = document.createElement('tr');

                let usernameCell = document.createElement('td');
                usernameCell.textContent = user.username;
                row.appendChild(usernameCell);

                let displayNameCell = document.createElement('td');
                displayNameCell.textContent = user.displayname;
                row.appendChild(displayNameCell);

                let roleCell = document.createElement('td');
                roleCell.textContent = user.role;
                row.appendChild(roleCell);

                let devotionCell = document.createElement('td');
                if (currentUserRole === 'admin' && isEditMode) {
                    let select = createDevotionDropdown(user.devotion);
                    select.addEventListener('change', function () {
                        updateDevotion(user.id, select.value);
                    });
                    devotionCell.appendChild(select);
                } else {
                    devotionCell.textContent = user.devotion;
                    devotionCell.style.backgroundColor = getDevotionColor(user.devotion);
                }
                row.appendChild(devotionCell);

                rosterTableBody.appendChild(row);
            });
        })
        .catch(error => console.error('There has been a problem with your fetch operation:', error));
}

function createDevotionDropdown(selectedValue) {
    const select = document.createElement('select');
    const options = ['red', 'blue', 'yellow', 'green'];
    
    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option;
        opt.textContent = option.charAt(0).toUpperCase() + option.slice(1);
        if (option === selectedValue) {
            opt.selected = true;
        }
        select.appendChild(opt);
    });

    // Apply styles for rounded corners, shadowing, and textured shading
    select.style.backgroundColor = getDevotionColor(selectedValue);
    select.style.borderRadius = '5px';
    select.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.2)';
    select.style.padding = '5px';
    select.style.outline = 'none';
    select.style.border = '1px solid #ccc';
    select.style.margin = '5px 0';
    select.style.backgroundImage = 'linear-gradient(white, #f2f2f2)';
    select.style.fontFamily = 'Arial, sans-serif';
    select.style.fontSize = '14px';

    select.addEventListener('change', function () {
        select.style.backgroundColor = getDevotionColor(select.value);
    });

    return select;
}

function getDevotionColor(devotion) {
    switch (devotion) {
        case 'red': return 'red';
        case 'blue': return 'blue';
        case 'yellow': return 'yellow';
        case 'green': return 'green';
        default: return 'white';
    }
}

function updateDevotion(userId, devotion) {
    fetch('/includes/roster/update_devotion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: userId, devotion: devotion })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            fetchRoster(); // Refresh the roster to show updated devotion
        } else {
            console.error('Failed to update devotion:', data.error);
        }
    })
    .catch(error => console.error('There has been a problem with your fetch operation:', error));
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    fetchRoster();
    document.getElementById('editModeButton').textContent = isEditMode ? 'Exit Edit Mode' : 'Enter Edit Mode';
}

// Fetch user role and then fetch the roster
fetch('/includes/users/get_user_role_json.php')
    .then(response => response.json())
    .then(data => {
        currentUserRole = data.role;
        if (currentUserRole === 'admin') {
            const editButton = document.createElement('button');
            editButton.id = 'editModeButton';
            editButton.textContent = 'Enter Edit Mode';
            editButton.addEventListener('click', toggleEditMode);
            const rosterContainer = document.querySelector('.roster-container');
            rosterContainer.insertBefore(editButton, rosterContainer.firstChild);
        }
        fetchRoster();
    })
    .catch(error => console.error('There has been a problem with fetching the user role:', error));
