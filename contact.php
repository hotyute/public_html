<?php
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    $to = 'admin@divineword.co.uk'; // Your email address
    $subject = 'New Contact Form Submission';
    
    $body = "You have received a new message from your website contact form.\n\n".
            "Here are the details:\n".
            "First Name: $first_name\n".
            "Last Name: $last_name\n".
            "Email: $email\n\n".
            "Message:\n$message";
    
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    if (mail($to, $subject, $body, $headers)) {
        $success_message = "Thank you for contacting us, $first_name. We will get back to you shortly.";
    } else {
        $error_message = "Sorry, something went wrong. Please try again later.";
    }
}
?>

<div class="main-container">
    <main>
        <section>
            <h2>Contact Us</h2>
            <p>We'd love to hear from you! Whether you have a question, feedback, or just want to say hello, feel free to reach out using the form below.</p>
            
            <?php
            if (isset($success_message)) {
                echo "<p style='color:green;'>$success_message</p>";
            } elseif (isset($error_message)) {
                echo "<p style='color:red;'>$error_message</p>";
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
    </main>
</div>

<?php include 'footer.php'; ?>
