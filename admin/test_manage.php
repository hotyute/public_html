<?php
session_start();
require_once '../base_config.php';
require 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access.");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_test'])) {
            $test_name = $_POST['test_name'];
            $num_questions = $_POST['num_questions'];
            $stmt = $pdo->prepare("INSERT INTO tests (test_name, num_questions) VALUES (?, ?)");
            $stmt->execute([$test_name, $num_questions]);
            echo "Test added successfully!";
        } elseif (isset($_POST['delete_test'])) {
            $test_id = $_POST['test_id'];
            $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
            $stmt->execute([$test_id]);
            echo "Test deleted successfully!";
        } elseif (isset($_POST['add_question'])) {
            $test_id = $_POST['test_id'];
            $question = $_POST['question'];
            $options = $_POST['options'];
            $correct_option = $_POST['correct_option'];
            $num_options = count($options);
            $option_struct = str_repeat('s', $num_options); // Dynamic option structure
            $options_json = json_encode($options);

            $stmt = $pdo->prepare("INSERT INTO questions (question, num_options, option_struct, options, correct_option, test_ids) VALUES (?, ?, ?, ?, ?, JSON_ARRAY(?))");
            $stmt->execute([$question, $num_options, $option_struct, $options_json, $correct_option, $test_id]);

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
<style>
    .admin-container textarea {
        width: 100%;
        padding: 10px;
        margin-right: 400px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
</style>
<div class="admin-container">
    <h1>Manage Tests and Questions</h1>

    <!-- Form to add a new test -->
    <h2>Add New Test</h2>
    <form method="POST">
        <input type="text" name="test_name" placeholder="Test Name" required>
        <input type="number" name="num_questions" placeholder="Number of Questions" required>
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
    <form method="POST" id="questionForm">
        <select name="test_id" required>
            <?php foreach ($tests as $test) : ?>
                <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <textarea name="question" placeholder="Question" required></textarea><br>
        <div id="options">
            <div class="option" data-option="a">
                <input type="text" name="options[a]" placeholder="Option A" required>
                <button type="button" class="removeOption">Remove Option</button>
            </div>
            <div class="option" data-option="b">
                <input type="text" name="options[b]" placeholder="Option B" required>
                <button type="button" class="removeOption">Remove Option</button>
            </div>
            <div class="option" data-option="c">
                <input type="text" name="options[c]" placeholder="Option C" required>
                <button type="button" class="removeOption">Remove Option</button>
            </div>
            <div class="option" data-option="d">
                <input type="text" name="options[d]" placeholder="Option D" required>
                <button type="button" class="removeOption">Remove Option</button>
            </div>
        </div>
        <button type="button" id="addOption">Add Option</button><br>
        <label for="correct_option">Correct Option:</label>
        <select name="correct_option" id="correct_option" required>
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

</div>

<?php include '../footer.php'; ?>

<script>
    document.getElementById('addOption').addEventListener('click', function() {
        var optionsDiv = document.getElementById('options');
        var optionCount = optionsDiv.getElementsByClassName('option').length;
        var optionLetter = String.fromCharCode(97 + optionCount); // a, b, c, d, ...

        var newOptionDiv = document.createElement('div');
        newOptionDiv.className = 'option';
        newOptionDiv.setAttribute('data-option', optionLetter);

        var input = document.createElement('input');
        input.type = 'text';
        input.name = 'options[' + optionLetter + ']';
        input.placeholder = 'Option ' + optionLetter.toUpperCase();
        input.required = true;

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'removeOption';
        removeButton.textContent = 'Remove Option';
        removeButton.addEventListener('click', function() {
            optionsDiv.removeChild(newOptionDiv);
            updateOptions();
        });

        newOptionDiv.appendChild(input);
        newOptionDiv.appendChild(removeButton);
        optionsDiv.appendChild(newOptionDiv);

        updateOptions();
    });

    document.querySelectorAll('.removeOption').forEach(function(button) {
        button.addEventListener('click', function() {
            var optionDiv = button.parentElement;
            optionDiv.parentElement.removeChild(optionDiv);
            updateOptions();
        });
    });

    function updateOptions() {
        var optionsDiv = document.getElementById('options');
        var correctOptionSelect = document.getElementById('correct_option');
        correctOptionSelect.innerHTML = '';

        var optionLetters = 'abcdefghijklmnopqrstuvwxyz'.split('');
        optionsDiv.querySelectorAll('.option').forEach(function(optionDiv, index) {
            var optionLetter = optionLetters[index];
            optionDiv.setAttribute('data-option', optionLetter);
            var input = optionDiv.querySelector('input');
            input.name = 'options[' + optionLetter + ']';
            input.placeholder = 'Option ' + optionLetter.toUpperCase();

            var newOption = document.createElement('option');
            newOption.value = optionLetter;
            newOption.textContent = optionLetter.toUpperCase();
            correctOptionSelect.appendChild(newOption);
        });
    }
</script>