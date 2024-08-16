<?php include 'header.php'; ?>

<style>
    h2 {
        margin-bottom: 20px;
        font-size: 24px;
    }

    p {
        margin-bottom: 20px;
        font-size: 16px;
        color: #555;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    label {
        text-align: left;
        font-size: 14px;
        color: #333;
    }

    input[type="text"],
    input[type="email"],
    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
    }

    textarea {
        resize: vertical; /* Allow vertical resize */
    }

    button[type="submit"] {
        padding: 10px 20px;
        background-color: #007BFF;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button[type="submit"]:hover {
        background-color: #0056b3;
    }

    /* Success and error messages */
    .message {
        margin-bottom: 20px;
        font-size: 16px;
    }

    .message.success {
        color: green;
    }

    .message.error {
        color: red;
    }
</style>

<div class="main-container">
    <main>
        <section>
            <h2>Contact Us</h2>
            <p>We'd love to hear from you! Whether you have a question, feedback, or just want to say hello, feel free to reach out using the form below.</p>
            
            <?php
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
                    echo "<p class='message success'>Thank you for contacting us, $first_name. We will get back to you shortly.</p>";
                } else {
                    echo "<p class='message error'>Sorry, something went wrong. Please try again later.</p>";
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
    </main>
</div>

<?php include 'footer.php'; ?>
