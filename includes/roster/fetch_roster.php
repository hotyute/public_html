<?php
require '../database.php';
require '../sanitize.php'; // Include the sanitization function

// Function to create default roster data for users without it
function createDefaultRosterData($pdo) {
    // Find users without roster data
    $stmt = $pdo->prepare("
        SELECT users.id 
        FROM users 
        LEFT JOIN roster_data ON users.id = roster_data.user_id 
        WHERE roster_data.user_id IS NULL
    ");
    $stmt->execute();
    $usersWithoutRoster = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert default roster data for each user without it
    $insertStmt = $pdo->prepare("INSERT INTO roster_data (user_id, devotion) VALUES (?, 'red')");
    foreach ($usersWithoutRoster as $user) {
        $insertStmt->execute([$user['id']]);
    }
}

// Create default roster data if it doesn't exist
createDefaultRosterData($pdo);

// Prepare the SQL statement to join users and roster_data tables
$stmt = $pdo->prepare("
    SELECT users.id, users.username, users.displayname, users.role, roster_data.devotion 
    FROM users
    LEFT JOIN roster_data ON users.id = roster_data.user_id
");

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$user) {
    $user['username'] = sanitize_html($user['username']);
}

header('Content-Type: application/json');
echo json_encode($users);
?>
