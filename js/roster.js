let currentUserRole = 'guest';
let isEditMode = false;

document.addEventListener('DOMContentLoaded', function() {
    fetchUserRole().then(() => fetchRoster());

    document.querySelector('.roster-table').addEventListener('click', function(event) {
        if (event.target.tagName === 'TD') {
            event.target.classList.toggle('clicked');
        }
    });
});

function fetchUserRole() {
    return fetch('/includes/users/get_user_role_json.php')
        .then(r => r.json())
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
        })
        .catch(err => console.error('Role fetch error:', err));
}

function fetchRoster() {
    fetch('/includes/roster/fetch_roster.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(users => {
            let rosterTableBody = document.querySelector('.roster-container #rosterTable tbody');
            rosterTableBody.innerHTML = '';

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
        .catch(error => console.error('Roster fetch error:', error));
}

function createDevotionDropdown(selectedValue) {
    const select = document.createElement('select');
    const options = ['red', 'blue', 'yellow', 'green'];

    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option;
        opt.textContent = option.charAt(0).toUpperCase() + option.slice(1);
        if (option === selectedValue) opt.selected = true;
        select.appendChild(opt);
    });

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
        case 'blue': return '#2565AE';
        case 'yellow': return 'yellow';
        case 'green': return 'green';
        default: return 'white';
    }
}

function updateDevotion(userId, devotion) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('/includes/roster/update_devotion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': token
        },
        body: JSON.stringify({ userId, devotion })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            fetchRoster();
        } else {
            console.error('Failed to update devotion:', data.message || data.error);
        }
    })
    .catch(error => console.error('Update error:', error));
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    fetchRoster();
    const btn = document.getElementById('editModeButton');
    if (btn) btn.textContent = isEditMode ? 'Exit Edit Mode' : 'Enter Edit Mode';
}