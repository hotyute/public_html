<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access.");
}

if (isset($_GET['question_id'])) {

    $question_id = $_GET['question_id'];
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($question) {
        echo json_encode($question);
    } else {
        echo json_encode(['error' => 'Question not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
