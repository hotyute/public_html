<?php
session_start();
require 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle form submission
        $answers = $_POST['answers'];
        $test_id = $_POST['test_id'];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_tests WHERE user_id = ? AND test_id = ?");
        $stmt->execute([$_SESSION['user_id'], $test_id]);
        if ($stmt->fetchColumn() == 0) {
            die("You are not assigned to this test.");
        }

        $correct_count = 0;
        foreach ($answers as $question_id => $user_answer) {
            $stmt = $pdo->prepare("SELECT correct_option FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $correct_answer = $stmt->fetchColumn();

            if ($user_answer === $correct_answer) {
                $correct_count++;
            }
        }

        $score = $correct_count; // Modify scoring logic if needed

        $stmt = $pdo->prepare("INSERT INTO scores (user_id, test_id, score) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $test_id, $score]);

        echo "Your score: $score";
    } else {
        // Display the test
        $test_id = $_GET['test_id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_tests WHERE user_id = ? AND test_id = ?");
        $stmt->execute([$_SESSION['user_id'], $test_id]);
        if ($stmt->fetchColumn() == 0) {
            die("You are not assigned to this test.");
        }

        // Remove the test assignment
        $stmt = $pdo->prepare("DELETE FROM user_tests WHERE user_id = ? AND test_id = ?");
        $stmt->execute([$_SESSION['user_id'], $test_id]);

        $stmt = $pdo->prepare("SELECT q.id, q.question, q.option_a, q.option_b, q.option_c, q.option_d FROM questions q 
                               JOIN test_questions tq ON q.id = tq.question_id WHERE tq.test_id = ?");
        $stmt->execute([$test_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            die("No questions found for this test.");
        }

        // Set the timer
        $_SESSION['start_time'] = time();
        $test_duration = 60 * 5; // 5 minutes, adjust as needed
        $_SESSION['end_time'] = $_SESSION['start_time'] + $test_duration;

        echo '<div id="timer"></div>';
        echo '<form method="POST">';
        echo '<input type="hidden" name="test_id" value="' . htmlspecialchars($test_id) . '">';
        foreach ($questions as $index => $question) {
            echo '<div>';
            echo '<p>' . ($index + 1) . '. ' . htmlspecialchars($question['question']) . '</p>';
            echo '<label><input type="radio" name="answers[' . $question['id'] . ']" value="a">' . htmlspecialchars($question['option_a']) . '</label><br>';
            echo '<label><input type="radio" name="answers[' . $question['id'] . ']" value="b">' . htmlspecialchars($question['option_b']) . '</label><br>';
            echo '<label><input type="radio" name="answers[' . $question['id'] . ']" value="c">' . htmlspecialchars($question['option_c']) . '</label><br>';
            echo '<label><input type="radio" name="answers[' . $question['id'] . ']" value="d">' . htmlspecialchars($question['option_d']) . '</label>';
            echo '</div>';
        }
        echo '<input type="submit" value="Submit">';
        echo '</form>';
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<script>
    function startTimer(duration, display) {
        var timer = duration,
            minutes, seconds;
        setInterval(function() {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                timer = duration;
                alert("Time's up! The test will be submitted automatically.");
                document.querySelector('form').submit();
            }
        }, 1000);
    }

    window.onload = function() {
        var endTime = <?= $_SESSION['end_time'] ?>;
        var currentTime = Math.floor(Date.now() / 1000);
        var timeLeft = endTime - currentTime;

        if (timeLeft <= 0) {
            alert("Time's up! The test will be submitted automatically.");
            document.querySelector('form').submit();
        } else {
            var display = document.querySelector('#timer');
            startTimer(timeLeft, display);
        }
    };
</script>