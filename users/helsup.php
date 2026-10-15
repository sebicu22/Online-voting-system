<?php
session_start();
include('../connection.php');

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'voter') {
    header("Location: ../login/login.php");
    exit();
}

// Handle ticket submission
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if ($subject && $message) {
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
        $stmt->bind_param("iss", $user_id, $subject, $message);
        if ($stmt->execute()) {
            $successMsg = "Support ticket submitted successfully.";
        } else {
            $errorMsg = "Failed to submit ticket. Please try again.";
        }
        $stmt->close();
    } else {
        $errorMsg = "All fields are required.";
    }
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help & Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="help.css">
    
</head>
<body>
    <div class="help-content">
        <main role="main" class="help-container">

        <section aria-labelledby="help-title">
            <h1 id="help-title">Help & Support</h1>
            <p>Welcome to the <strong>E-Vote System</strong> Help and Support page. Here you’ll find guidance on using the system, answers to common questions, and ways to contact our support team.</p>
        </section>

        <section aria-labelledby="getting-started-title">
            <h2 id="getting-started-title">Getting Started</h2>
            <ul>
                <li><strong>Register:</strong> Submit form with information to your administrators and they will create an account for you.</li>
                <li><strong>Login:</strong> Access your account securely with your username and password.</li>
                <li><strong>Verify Identity:</strong> Complete any required verification (e.g., OTP or token authentication).</li>
                <li><strong>Vote:</strong> Participate in active elections by selecting your choices and submitting your vote.</li>
            </ul>
        </section>

        <section aria-labelledby="faq-title">
            <h2 id="faq-title">Frequently Asked Questions (FAQs)</h2>
            <ul>
                <li>
                    <strong>How do I reset my password?</strong><br>
                    Use the <a href="forgot_password.php">Forgot Password</a> link on the login page to reset your password via email.
                </li>
                <li>
                    <strong>Can I change my vote after submitting?</strong><br>
                    No, once a vote is submitted, it cannot be changed to ensure election integrity.
                </li>
                <li>
                    <strong>Is my vote anonymous?</strong><br>
                    Yes, the system uses encryption and secure protocols to protect voter anonymity.
                </li>
                <li>
                    <strong>What if I encounter technical issues?</strong><br>
                    Try clearing your browser cache or using a different browser. If problems persist, contact support below.
                </li>
            </ul>
        </section>

        <section aria-labelledby="contact-title">
            <h2 id="contact-title">Contact Technical Support</h2>
            <div id="supportMsg">
            <?php if (!empty($successMsg)): ?>
              <div class="success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
              <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>
            </div>

            <?php
            if ($isAjax) {
                ?>
                <form method="post" id="supportForm" class="helf">
                    <label>Subject:<br>
                        <input type="text" name="subject" required>
                    </label><br><br>
                    <label>Message:<br>
                        <textarea name="message" rows="5" required></textarea>
                    </label><br><br>
                    <button type="submit">Submit Ticket</button>
                </form>
                <div id="supportMsg">
                    <?php if ($success): ?>
                        <p class="success"><?= htmlspecialchars($success) ?></p>
                    <?php elseif ($error): ?>
                        <p class="error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                </div>
                <?php
                exit;
            }
            ?>

            <form method="post" id="supportForm" class="helf">
                <label>Subject:<br>
                    <input type="text" name="subject" required>
                </label><br><br>
                <label>Message:<br>
                    <textarea name="message" rows="5" required></textarea>
                </label><br><br>
                <button type="submit">Submit Ticket</button>
            </form>
            <div id="supportMsg"></div>

            <address class="contact-info">
                <p><strong>Need urgent help?</strong></p>
                <p><strong>Email:</strong> <a href="mailto:support@evote.com">support@evote.com</a></p>
                <p><strong>Phone:</strong> <a href="tel:+256703014441">+256 703 014441</a></p>
                <p><strong>Live Chat:</strong> Monday to Friday, 9 AM - 5 PM (Your Timezone)</p>
            </address>
        </section>

        <section aria-labelledby="resources-title">
            <h2 id="resources-title">Additional Resources</h2>
            <ul>
                <li><a href="user_guide.pdf" target="_blank" rel="noopener noreferrer">User Guide (PDF)</a></li>
                <li><a href="terms_and_conditions.html" rel="noopener noreferrer">Terms and Conditions</a></li>
                <li><a href="privacy_policy.html" rel="noopener noreferrer">Privacy Policy</a></li>
            </ul>
        </section>

    </main>
    <footer role="contentinfo">
        &copy; <?= date('Y') ?> E-Vote System. All rights reserved.
    </footer>

    </div>
    </body>
</html>
