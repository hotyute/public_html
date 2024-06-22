<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../base_config.php';
require 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_test'])) {
            $test_name = $_POST['test_name'];
            $stmt = $pdo->prepare("INSERT INTO tests (test_name) VALUES (?)");
            $stmt->execute([$test_name]);
            echo "Test added successfully!";
        } elseif (isset($_POST['delete_test'])) {
            $test_id = $_POST['test_id'];
            $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
            $stmt->execute([$test_id]);
            echo "Test deleted successfully!";
        } elseif (isset($_POST['add_question'])) {
            $test_id = $_POST['test_id'];
            $question = $_POST['question'];
            $option_a = $_POST['option_a'];
            $option_b = $_POST['option_b'];
            $option_c = $_POST['option_c'];
            $option_d = $_POST['option_d'];
            $correct_option = $_POST['correct_option'];

            $stmt = $pdo->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$question, $option_a, $option_b, $option_c, $option_d, $correct_option]);

            $question_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO test_questions (test_id, question_id) VALUES (?, ?)");
            $stmt->execute([$test_id, $question_id]);

            echo "Question added successfully!";
        } elseif (isset($_POST['delete_question'])) {
            $question_id = $_POST['question_id'];
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            echo "Question deleted successfully!";
        }
    }

    // Fetch existing tests and questions
    $tests = $pdo->query("SELECT * FROM tests")->fetchAll(PDO::FETCH_ASSOC);
    $questions = $pdo->query("SELECT * FROM questions")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../header.php'; ?>
<h1>Manage Tests and Questions</h1>

<!-- Form to add a new test -->
<h2>Add New Test</h2>
<form method="POST">
    <input type="text" name="test_name" placeholder="Test Name" required>
    <button type="submit" name="add_test">Add Test</button>
</form>

<!-- Form to delete a test -->
<h2>Delete Test</h2>
<form method="POST">
    <select name="test_id" required>
        <?php foreach ($tests as $test) : ?>
            <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" name="delete_test">Delete Test</button>
</form>

<!-- Form to add a new question -->
<h2>Add New Question</h2>
<form method="POST">
    <select name="test_id" required>
        <?php foreach ($tests as $test) : ?>
            <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
        <?php endforeach; ?>
    </select><br>
    <textarea name="question" placeholder="Question" required></textarea><br>
    <input type="text" name="option_a" placeholder="Option A" required><br>
    <input type="text" name="option_b" placeholder="Option B" required><br>
    <input type="text" name="option_c" placeholder="Option C" required><br>
    <input type="text" name="option_d" placeholder="Option D" required><br>
    <select name="correct_option" required>
        <option value="a">A</option>
        <option value="b">B</option>
        <option value="c">C</option>
        <option value="d">D</option>
    </select><br>
    <button type="submit" name="add_question">Add Question</button>
</form>

<!-- Form to delete a question -->
<h2>Delete Question</h2>
<form method="POST">
    <select name="question_id" required>
        <?php foreach ($questions as $question) : ?>
            <option value="<?= htmlspecialchars($question['id']) ?>"><?= htmlspecialchars($question['question']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" name="delete_question">Delete Question</button>
</form>
<?php include '../footer.php'; ?>