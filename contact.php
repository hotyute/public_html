<?php include 'header.php'; ?>

<div class="contact-container">
    <main>
        <div class="contact-us">
            <section>
                <h2>Contact Us</h2>
                <p>We'd love to hear from you! Whether you have a question, feedback, or just want to say hello, feel free to reach out using the form below.</p>

                <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name  = trim($_POST['last_name'] ?? '');
                    $email      = trim($_POST['email'] ?? '');
                    $message    = trim($_POST['message'] ?? '');

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo "<p class='message error'>Invalid email address.</p>";
                    } else {
                        // Basic header injection guard
                        if (preg_match("/[\r\n]/", $email)) {
                            echo "<p class='message error'>Invalid email header.</p>";
                        } else {
                            $to = 'admin@divineword.co.uk';
                            $subject = 'New Contact Form Submission';
                            $body = "You have received a new message from your website contact form.\n\n" .
                                "Here are the details:\n" .
                                "First Name: $first_name\n" .
                                "Last Name: $last_name\n" .
                                "Email: $email\n\n" .
                                "Message:\n$message";

                            $headers = "From: no-reply@divineword.co.uk\r\n";
                            $headers .= "Reply-To: $email\r\n";

                            if (mail($to, $subject, $body, $headers)) {
                                echo "<p class='message success'>Thank you for contacting us, " . htmlspecialchars($first_name) . ". We will get back to you shortly.</p>";
                            } else {
                                echo "<p class='message error'>Sorry, something went wrong. Please try again later.</p>";
                            }
                        }
                    }
                }
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div>
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div>
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div>
                        <label for="message">Your Message:</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>

                    <div>
                        <button type="submit">Send Message</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>