// Fetch and display the roster
function fetchRoster() {
    fetch('/includes/users/fetch_roster.php')
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
                devotionCell.textContent = user.devotion;
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

// Call fetchRoster on page load
window.onload = fetchRoster;
