<?php
include('../connection.php');
session_start();

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simplify redirection logic for testing
function redirect($url) {
    echo "<p>Redirecting to: $url</p>"; // Debugging output
    header("Location: $url");
    exit(); // Ensure no further code is executed
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$login_error = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $login_error = "Invalid CSRF token.";
    } elseif (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password.";
    } else {
        // Check admins table
        $stmt_admin = $conn->prepare("SELECT id, username, password FROM admins WHERE email = ?");
        $stmt_admin->bind_param("s", $email);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();

        if ($result_admin && $result_admin->num_rows === 1) {
            $admin = $result_admin->fetch_assoc();
            

            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = 'admin';
                redirect('../admin/dashboard.php');
            } else {
                $login_error = "Invalid email or password.";
            }
        } else {
            // Check register table
            $stmt_voter = $conn->prepare("SELECT id, username, password FROM register WHERE email = ?");
            $stmt_voter->bind_param("s", $email);
            $stmt_voter->execute();
            $result_voter = $stmt_voter->get_result();

            if ($result_voter && $result_voter->num_rows === 1) {
                $voter = $result_voter->fetch_assoc();
                if (password_verify($password, $voter['password'])) {
                    $_SESSION['user_id'] = $voter['id'];
                    $_SESSION['username'] = $voter['username'];
                    $_SESSION['role'] = 'voter';
                    header("Location: ../users/udashboard.php"); 
                    exit();
                }
                
            }
            $login_error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="log">
        <img src="../images/image8.jpg" alt="Login Image" />

        <form action="" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <h1>Login</h1>
            <hr />
            <div class="in">
                <input type="email" placeholder="Email" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
            <div class="in">
                <input type="password" placeholder="Password" name="password" id="password" required />
            </div>

            <div class="fot">
                <a href="">Forgot password</a>
                <button class="btn" type="submit" name="login">Login</button>
            </div>

            <?php if ($login_error): ?>
                <p style="color: red; margin-top: 15px;"><?= htmlspecialchars($login_error) ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
