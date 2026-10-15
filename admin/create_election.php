<?php
// Database connection (MySQLi)
$host = 'localhost';
$dbname = 'evoteplatform';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token.";
    } else {
        // Collect and sanitize inputs
        $title = trim($_POST['title'] ?? '');
        $start_date_raw = trim($_POST['start_date'] ?? '');
        $end_date_raw = trim($_POST['end_date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Convert datetime-local to MySQL DATETIME format
        $start_date = $start_date_raw ? str_replace('T', ' ', $start_date_raw) . ':00' : '';
        $end_date = $end_date_raw ? str_replace('T', ' ', $end_date_raw) . ':00' : '';

        // Validate inputs
        if ($title === '') {
            $errors[] = "Title is required.";
        }
        if ($start_date === '') {
            $errors[] = "Start date and time is required.";
        }
        if ($end_date === '') {
            $errors[] = "End date and time is required.";
        }
        if ($start_date && $end_date && $start_date > $end_date) {
            $errors[] = "Start date cannot be after end date.";
        }

        if (empty($errors)) {
            // Calculate status based on current datetime
            $currentDateTime = date('Y-m-d H:i:s');
            if ($currentDateTime < $start_date) {
                $status = 'upcoming';
            } elseif ($currentDateTime >= $start_date && $currentDateTime <= $end_date) {
                $status = 'active';
            } else {
                $status = 'completed';
            }

            // Get admin id from session
            $created_by = $_SESSION['admin_id'] ?? null;
            if (!$created_by) {
                $errors[] = "Admin ID not found in session. Please log in again.";
            } else {
                // Insert election into database
                $stmt = $conn->prepare("INSERT INTO elections (title, start_date, end_date, status, description, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssssi", $title, $start_date, $end_date, $status, $description, $created_by);
                    if ($stmt->execute()) {
                        $success = "Election created successfully!";

                        // Insert notification
                        $admin_id = $_SESSION['user_id'] ?? $created_by;
                        $type = 'announcement';
                        $message = "A new election titled '{$title}' has been created!";

                        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
                        if ($notif_stmt) {
                            $notif_stmt->bind_param('iss', $admin_id, $type, $message);
                            $notif_stmt->execute();
                            $notif_stmt->close();
                        }

                        // Clear POST data after success
                        $_POST = [];
                    } else {
                        $errors[] = "Failed to create election. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create New Election</title>
    <link rel="stylesheet" href="create_election.css" />
</head>
<body>
    <div class="container">
        <h1>Create New Election</h1>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?=htmlspecialchars($err)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <label for="title">Election Title</label>
            <input type="text" name="title" id="title" value="<?=htmlspecialchars($_POST['title'] ?? '')?>" required />

            <label for="start_date">Start Date</label>
            <input type="datetime-local" name="start_date" id="start_date" value="<?=htmlspecialchars($_POST['start_date'] ?? '')?>" required />

            <label for="end_date">End Date</label>
            <input type="datetime-local" name="end_date" id="end_date" value="<?=htmlspecialchars($_POST['end_date'] ?? '')?>" required />

            <label for="description">Description</label>
            <textarea name="description" id="description"><?=htmlspecialchars($_POST['description'] ?? '')?></textarea>

            <button type="submit" class="btn">Create Election</button>
        </form>
    </div>
</body>
</html>
