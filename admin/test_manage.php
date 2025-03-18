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
            $test_id = '[' + $_POST['test_id'] + ']';
            $question = $_POST['question'];
            $options = $_POST['options'];
            $correct_option = $_POST['correct_option'];
            $num_options = count($options);
            $option_struct = str_repeat('s', $num_options); // Dynamic option structure
            $options_json = json_encode($options);

            $stmt = $pdo->prepare("INSERT INTO questions (question, num_options, option_struct, options, correct_option, test_ids) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$question, $num_options, $option_struct, $options_json, $correct_option, $test_id]);

            echo "Question added successfully!";
        } elseif (isset($_POST['delete_question'])) {
            $question_id = $_POST['question_id'];
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            echo "Question deleted successfully!";
        } elseif (isset($_POST['edit_question'])) {
            $question_id = $_POST['question_id'];
            $question = $_POST['question'];
            $options = $_POST['options'];
            $correct_option = $_POST['correct_option'];
            $num_options = count($options);
            $option_struct = str_repeat('s', $num_options);
            $options_json = json_encode($options);

            $stmt = $pdo->prepare("UPDATE questions SET question = ?, num_options = ?, option_struct = ?, options = ?, correct_option = ? WHERE id = ?");
            $stmt->execute([$question, $num_options, $option_struct, $options_json, $correct_option, $question_id]);

            echo "Question edited successfully!";
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
        height: 150px;
        width: 400px;
        padding: 10px;
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

    <!-- Form to edit a question -->
    <h2>Edit Question</h2>
    <form method="POST" id="editQuestionForm">
        <label for="edit_question_id">Select Question:</label>
        <select name="question_id" id="edit_question_id" required>
            <option value="">-- Select a Question --</option>
            <?php foreach ($questions as $question) : ?>
                <option value="<?= htmlspecialchars($question['id']) ?>"><?= htmlspecialchars($question['question']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <textarea name="question" id="edit_question_text" placeholder="Question" required></textarea><br>

        <div id="edit_options">
            <!-- Options will be dynamically loaded here based on the selected question -->
        </div>
        <button type="button" id="edit_addOption">Add Option</button><br><br>

        <label for="edit_correct_option">Correct Option:</label>
        <select name="correct_option" id="edit_correct_option" required>
            <!-- Correct options will be dynamically populated based on the selected question -->
        </select><br><br>

        <button type="submit" name="edit_question">Edit Question</button>
    </form>


</div>

<?php include '../footer.php'; ?>

<script>
    // Adding new options dynamically in the "Add Question" form
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

    // Removing options in the "Add Question" form
    document.querySelectorAll('.removeOption').forEach(function(button) {
        button.addEventListener('click', function() {
            var optionDiv = button.parentElement;
            optionDiv.parentElement.removeChild(optionDiv);
            updateOptions();
        });
    });

    // Update options in the "Add Question" form to keep them consistent
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

    // Edit question functionality: Fetching and populating the selected question's details
    document.getElementById('edit_question_id').addEventListener('change', function() {
        var questionId = this.value;
        if (questionId) {
            console.log("questionId: " + questionId);
            fetchQuestionDetails(questionId);
        }
    });

    // Fetch question details via AJAX and populate the edit form
    function fetchQuestionDetails(questionId) {
        fetch('/includes/tests/fetch_question.php?question_id=' + questionId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    populateEditForm(data);
                }
            })
            .catch(error => console.error('Error fetching question details:', error));
    }

    // Populate the edit form with the fetched question details
    function populateEditForm(question) {
        document.getElementById('edit_question_text').value = question.question;

        var optionsDiv = document.getElementById('edit_options');
        optionsDiv.innerHTML = ''; // Clear current options

        var options = {};
        try {
            options = JSON.parse(question.options);
            if (typeof options !== 'object' || options === null) {
                throw new Error('Options is not a valid object');
            }
        } catch (e) {
            console.error('Error parsing options:', e);
            alert('Failed to load options. Please check the question data.');
            return;
        }

        Object.keys(options).forEach(function(optionLetter) {
            var optionValue = options[optionLetter];

            var optionDiv = document.createElement('div');
            optionDiv.className = 'option';
            optionDiv.setAttribute('data-option', optionLetter);

            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'options[' + optionLetter + ']';
            input.value = optionValue;
            input.placeholder = 'Option ' + optionLetter.toUpperCase();
            input.required = true;

            var removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'removeOption';
            removeButton.textContent = 'Remove Option';
            removeButton.addEventListener('click', function() {
                optionsDiv.removeChild(optionDiv);
                updateEditOptions();
            });

            optionDiv.appendChild(input);
            optionDiv.appendChild(removeButton);
            optionsDiv.appendChild(optionDiv);
        });

        updateEditOptions();
        document.getElementById('edit_correct_option').value = question.correct_option;
    }

    // Update the edit form's correct option dropdown
    function updateEditOptions() {
        var optionsDiv = document.getElementById('edit_options');
        var correctOptionSelect = document.getElementById('edit_correct_option');
        correctOptionSelect.innerHTML = ''; // Clear the current dropdown options

        optionsDiv.querySelectorAll('.option').forEach(function(optionDiv) {
            var optionLetter = optionDiv.getAttribute('data-option'); // Get the option letter (e.g., 'a', 'b', etc.)

            var newOption = document.createElement('option');
            newOption.value = optionLetter; // Set the option value
            newOption.textContent = optionLetter.toUpperCase(); // Set the option display text (e.g., 'A', 'B', etc.)
            correctOptionSelect.appendChild(newOption); // Add the option to the select dropdown
        });
    }
</script>