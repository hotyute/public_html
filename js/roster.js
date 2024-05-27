let currentUserRole = 'member'; // Replace this with actual role fetching logic

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
            let rosterTable = document.getElementById('rosterTable');
            rosterTable.innerHTML = `
                <tr>
                    <th>Username</th>
                    <th>Display Name</th>
                    <th>Role</th>
                    <th>Devotion</th>
                </tr>
            `;

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
                if (true) {
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

                rosterTable.appendChild(row);
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

    select.style.backgroundColor = getDevotionColor(selectedValue);
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

// Fetch user role and then fetch the roster
fetch('/includes/roster/get_user_role.php')
    .then(response => response.json())
    .then(data => {
        currentUserRole = data.role;
        fetchRoster();
    })
    .catch(error => console.error('There has been a problem with fetching the user role:', error));

// Call fetchRoster on page load
window.onload = fetchRoster;
