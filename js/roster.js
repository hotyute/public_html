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
                if (user.role === 'admin') {
                    let select = document.createElement('select');
                    select.innerHTML = `
                        <option value="red" ${user.devotion === 'red' ? 'selected' : ''}>Red</option>
                        <option value="blue" ${user.devotion === 'blue' ? 'selected' : ''}>Blue</option>
                        <option value="yellow" ${user.devotion === 'yellow' ? 'selected' : ''}>Yellow</option>
                        <option value="green" ${user.devotion === 'green' ? 'selected' : ''}>Green</option>
                    `;
                    select.addEventListener('change', function () {
                        updateDevotion(user.id, select.value);
                    });
                    devotionCell.appendChild(select);
                } else {
                    devotionCell.textContent = user.devotion;
                }
                devotionCell.style.backgroundColor = getDevotionColor(user.devotion);
                row.appendChild(devotionCell);

                rosterTable.appendChild(row);
            });
        })
        .catch(error => console.error('There has been a problem with your fetch operation:', error));
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

// Call fetchRoster on page load
window.onload = fetchRoster;
