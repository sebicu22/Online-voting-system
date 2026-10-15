<?php
include('../connection.php');
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

$id = intval($_GET['id'] ?? 0);
$election = null;
$successMsg = '';
$errorMsg = '';

if ($id) {
    $result = mysqli_query($conn, "SELECT * FROM elections WHERE id = $id");
    $election = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    // Convert datetime-local input to MySQL DATETIME format
    $start = str_replace('T', ' ', $_POST['start_date']);
    $end = str_replace('T', ' ', $_POST['end_date']);
    $desc = $_POST['description'];

    // Automatically determine status based on current datetime
    $currentDateTime = date('Y-m-d H:i:s');
    if ($currentDateTime < $start) {
        $status = 'upcoming';
    } elseif ($currentDateTime >= $start && $currentDateTime <= $end) {
        $status = 'active';
    } else {
        $status = 'completed';
    }

    $stmt = mysqli_prepare($conn, "UPDATE elections SET title=?, start_date=?, end_date=?, status=?, description=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssssi", $title, $start, $end, $status, $desc, $id);
    if (mysqli_stmt_execute($stmt)) {
        $successMsg = 'Election updated successfully.';
    } else {
        $errorMsg = 'Error updating election: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);

    if (isset($_GET['ajax'])) {
        exit('success');
    }
    echo "<script>window.parent.location.reload();</script>";
    exit;
}

$isAjax = isset($_GET['ajax']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Election</title>
    <link rel="stylesheet" href="edit_election.css" />
</head>
<body>
<?php if (!empty($successMsg)): ?>
  <div class="success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if (!empty($errorMsg)): ?>
  <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($election): ?>
<form method="post" autocomplete="off">
    <input type="hidden" name="id" value="<?= htmlspecialchars($election['id']) ?>" />

    <label for="title">Title:</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($election['title']) ?>" required />

    <label for="start_date">Start Date & Time:</label>
    <input type="datetime-local" id="start_date" name="start_date" 
        value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($election['start_date']))) ?>" required />

    <label for="end_date">End Date & Time:</label>
    <input type="datetime-local" id="end_date" name="end_date" 
        value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($election['end_date']))) ?>" required />

    <label for="description">Description:</label>
    <textarea id="description" name="description"><?= htmlspecialchars($election['description']) ?></textarea>

    <button type="submit">Save Changes</button>
</form>
<?php else: ?>
    <p>Election not found.</p>
<?php endif; ?>
</body>
</html>
