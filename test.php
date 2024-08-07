<?php
session_start();
require 'includes/config.php';
require_once 'base_config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/tests.css">
</head>

<body>
    <header>Test</header>

    <?php

    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized access.");
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['test_started'], $_SESSION['test_completed']) && $_SESSION['test_started'] && $_SESSION['test_completed']) {
                // Handle form submission
                $answers = $_POST['answers'];
                $test_id = $_POST['test_id'];
                $username = $_SESSION['username'];

                // $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_tests WHERE user_id = ? AND test_id = ?");
                // $stmt->execute([$_SESSION['user_id'], $test_id]);
                // if ($stmt->fetchColumn() == 0) {
                //     die("You are not assigned to this test.");
                // }

                $correct_count = 0;

                if (!empty($answers)) {
                    foreach ($answers as $question_id => $user_answer) {
                        $stmt = $pdo->prepare("SELECT correct_option FROM questions WHERE id = ?");
                        $stmt->execute([$question_id]);
                        $correct_answer = $stmt->fetchColumn();

                        if ($user_answer === $correct_answer) {
                            $correct_count++;
                        }
                    }
                }

                $score = $correct_count; // Modify scoring logic if needed

                $stmt = $pdo->prepare("INSERT INTO scores (user_id, test_id, score) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $test_id, $score]);

                echo "Your score: $score";

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE id = ?");
                $stmt->execute([$test_id]);
                $test_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($test_info)) {

                    $test_name = $test_info['test_name'];

                    $to = 'admin@divineword.co.uk';
                    $subject = "{$username} has completed the test '{$test_name}'";
                    $message = "{$username} has completed the test '{$test_name}' with a score of {$score}";
                    $headers = 'From: admin@divineword.co.uk' . "\r\n" .
                        'Reply-To: admin@divineword.co.uk' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();

                    if (mail($to, $subject, $message, $headers)) {
                        echo 'Email sent successfully.';
                    } else {
                        echo 'Email sending failed.';
                    }
                }

                // Unset session variables related to the test
                unset($_SESSION['test_started'], $_SESSION['test_completed']);
            } else {
                unset($_SESSION['test_started'], $_SESSION['test_completed']);
                die("Test session is invalid or has not been properly started.");
            }
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

            // Set the session flag indicating the test has started
            $_SESSION['test_started'] = true;
            $_SESSION['test_completed'] = false;

            // Fetch the number of questions for the test
            $stmt = $pdo->prepare("SELECT num_questions FROM tests WHERE id = ?");
            $stmt->execute([$test_id]);
            $num_questions = $stmt->fetchColumn();

            if (!$num_questions) {
                die("Invalid test configuration.");
            }

            // Fetch questions assigned to the test using JSON search and random selection
            $stmt = $pdo->prepare("SELECT id, question, options FROM questions WHERE JSON_CONTAINS(test_ids, :test_id, '$') ORDER BY RAND() LIMIT :num_questions");
            $stmt->bindValue(':test_id', json_encode((int)$test_id), PDO::PARAM_STR);
            $stmt->bindValue(':num_questions', $num_questions, PDO::PARAM_INT);
            $stmt->execute();
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
                $options = json_decode($question['options'], true);
                echo '<div class="question">';
                echo '<p>' . ($index + 1) . '. ' . htmlspecialchars($question['question']) . '</p>';
                foreach ($options as $key => $option) {
                    echo '<label class="answer"><input type="radio" name="answers[' . $question['id'] . ']" value="' . $key . '">' . htmlspecialchars($option) . '</label><br>';
                }
                echo '</div>';
            }
            echo '<input type="submit" value="Submit">';
            echo '</form>';

            // Set the session flag indicating the test can be completed
            $_SESSION['test_completed'] = true;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
    ?>

    <footer></footer>

    <script>
        window.onbeforeunload = function() {
            return "Are you sure you want to leave? Your progress will be lost.";
        };

        window.addEventListener('beforeunload', function(e) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/includes/testsunset_test_session.php', true);
            xhr.send();
        });

        document.addEventListener('DOMContentLoaded', (event) => {
            document.addEventListener('copy', (e) => {
                e.preventDefault();
                alert('Copying is not allowed.');
            });

            document.addEventListener('cut', (e) => {
                e.preventDefault();
                alert('Cutting is not allowed.');
            });

            document.addEventListener('paste', (e) => {
                e.preventDefault();
                alert('Pasting is not allowed.');
            });
        });

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

</body>

</html>