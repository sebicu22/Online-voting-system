<?php
include('../connection.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}
$id = intval($_GET['id'] ?? 0);
$user = null;
if ($id) {
    $result = mysqli_query($conn, "SELECT * FROM register WHERE id = $id");
    $user = mysqli_fetch_assoc($result);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../images/";
        $image = $targetDir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            move_uploaded_file($_FILES['image']['tmp_name'], $image);
        } else {
            $image = $user['image']; // keep old image if invalid
        }
    } else {
        $image = $user['image'];
    }

    $params = [];
    $query = "UPDATE register SET username=?, email=?, image=?";
    $params[] = &$username;
    $params[] = &$email;
    $params[] = &$image;
    $types = "sss";
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password=?";
        $params[] = &$hashed;
        $types .= "s";
    }
    $query .= " WHERE id=?";
    $params[] = &$id;
    $types .= "i";
    $stmt = mysqli_prepare($conn, $query);
    array_unshift($params, $types);
    call_user_func_array([$stmt, 'bind_param'], $params);
    mysqli_stmt_execute($stmt);
    if (isset($_GET['ajax'])) {
        exit('success');
    }
    echo "<script>window.parent.location.reload();</script>";
    exit;
}
$isAjax = isset($_GET['ajax']);
?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="edit_user.css">
</head>
<body>
<?php endif; ?>
<?php if ($user): ?>
<form class="edit-user-modal" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $user['id'] ?>">
    <label>Username: <input type="text" name="username" value="<?=htmlspecialchars($user['username'])?>" required></label><br>
    <label>Email: <input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required></label><br>
    <label>Image:
        <?php if (!empty($user['image'])): ?>
            <br><img src="<?= htmlspecialchars($user['image']) ?>" alt="User Image" style="max-width:80px;max-height:80px;"><br>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">
    </label><br>
    <label>Password: <input type="password" name="password" placeholder="Leave blank to keep current"></label><br>
    <button type="submit">Save Changes</button>
</form>
<?php else: ?>
    <p>User not found.</p>
<?php endif; ?>
<?php if (!empty($successMsg)): ?>
  <div class="success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if (!empty($errorMsg)): ?>
  <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if (!$isAjax): ?>
</body>
</html>
<?php endif; ?>