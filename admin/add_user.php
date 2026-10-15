<?php
include('../connection.php'); // Ensure this file establishes $conn (mysqli object)
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$name = $username = $email = $password = $hashed_password = $image = ""; // Initialize $password and $hashed_password
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token.";
    } else {
        // Sanitize and trim inputs
        $name = trim($_POST['name'] ?? ''); // Use null coalescing to avoid undefined index warning
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Get password directly from POST

        // --- Input Validation ---
        if (empty($name)) {
            $errors[] = "Name is required.";
        }
        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        // Check for duplicate username (important for unique constraint)
        if (empty($errors)) { // Only check if username isn't already marked as empty
            $stmt_check_username = $conn->prepare("SELECT id FROM register WHERE username = ?");
            $stmt_check_username->bind_param("s", $username);
            $stmt_check_username->execute();
            $stmt_check_username->store_result();
            if ($stmt_check_username->num_rows > 0) {
                $errors[] = "Username already exists. Please choose a different one.";
            }
            $stmt_check_username->close();
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        // Check for duplicate email (important if email also has unique constraint)
        if (empty($errors)) { // Only check if email isn't already marked as invalid/empty
            $stmt_check_email = $conn->prepare("SELECT id FROM register WHERE email = ?");
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $errors[] = "Email already exists. Please use a different email address.";
            }
            $stmt_check_email->close();
        }


        // Password validation and hashing
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) { // Example: minimum 6 characters
             $errors[] = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        // Handle image upload
        // Set a default image path first
        $image = "images/candidate-default.png"; // Default image relative to root of 'Evote platform'
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "../images/"; // Path where images will be stored
            $uploaded_image_name = basename($_FILES['image']['name']);
            $targetFilePath = $targetDir . $uploaded_image_name;
            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Check file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed for profile image.";
            }

            // Check file size (e.g., 5MB limit)
            if ($_FILES['image']['size'] > 5000000) { // 5MB in bytes
                $errors[] = "Image file is too large. Max 5MB allowed.";
            }

            // Move uploaded file if no errors specific to image upload
            if (empty($errors)) { // Only proceed if no other errors have been found
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                    $image = "../images/" . $uploaded_image_name; // Store relative path for DB
                } else {
                    $errors[] = "Failed to upload image. Check folder permissions.";
                }
            }
        }

        // Insert into database if no errors
        if (empty($errors)) {
            // Include username in the INSERT statement!
            $sql = "INSERT INTO register (full_name, username, email, password, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                 $errors[] = "Database prepare error: " . $conn->error;
            } else {
                // Bind all parameters in correct order and type
                $stmt->bind_param("sssss", $name, $username, $email, $hashed_password, $image);

                if ($stmt->execute()) {
                    $success = "New User Added Successfully!";
                    // Clear form fields after success
                    $name = $username = $email = $password = $hashed_password = $image = ""; // Clear all relevant vars
                    // Regenerate CSRF token for next submission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    // Check for specific duplicate entry errors from MySQL
                    if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                        $errors[] = "Failed to add user. A user with this username or email might already exist.";
                    } else {
                        $errors[] = "Failed to add user. Please try again. Database error: " . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}
// Note: $successMsg and $errorMsg variables are not used in this file
// as you already have $success and $errors arrays. Remove them if they
// are not defined or intended for use here.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <link rel="stylesheet" href="add_user.css">
    <style>
        /* Basic styling for messages */
        .errors {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .errors ul {
            margin: 0;
            padding-left: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .content-use {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't add to total width */
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn.add:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="content-use">
    <h1>Add New User</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="image">Profile Image:</label>
            <input type="file" id="image" name="image">
        </div>

        <button type="submit" class="btn add">Add User</button>
    </form>
</div>
</body>
</html>