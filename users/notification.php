<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$host = 'localhost';
$dbname = 'evoteplatform';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<p style='text-align:center; color: red;'>System error. Please try again later.</p>");
}

$notifications = [];
$sql = "SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Notifications - E-Vote</title>
<link rel="stylesheet" href="noti.css">
</head>
<body>
<div class="container-not" role="main" aria-labelledby="notifications-title">
  <div class="content-not">
    <h2 id="notifications-title">Your Notifications</h2>
    <p class="desc">
      Stay up to date with the latest updates, poll results, and important announcements from the E-Vote platform.
    </p>
    <?php if (empty($notifications)): ?>
      <section class="no-notifications" role="alert" aria-live="polite" style="text-align:center; font-style: italic; color: #555;">
        <p>You have no notifications at this time. Check back later for updates!</p>
      </section>
    <?php else: ?>
      <div class="summary" aria-live="polite" style="font-weight:600; margin-bottom:1rem;">
        Total Notifications: <?= count($notifications) ?>
      </div>
      <section class="notifications-list" aria-label="List of notifications">
        <?php foreach ($notifications as $note): ?>
          <article class="notification-item<?= $note['is_read'] ? ' read' : '' ?>" data-id="<?= (int)$note['id'] ?>">
            <span><?= htmlspecialchars($note['message']) ?></span>
            <?php if (!$note['is_read']): ?>
              <button class="mark-read-btn" data-id="<?= (int)$note['id'] ?>">Mark as Read</button>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
    <?php if (!empty($successMsg)): ?>
      <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Use event delegation for dynamically loaded content
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.mark-read-btn');
        if (btn) {
            e.preventDefault();
            const noteId = btn.getAttribute('data-id');
            const article = btn.closest('.notification-item');
            fetch('mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id: noteId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.remove();
                    if (article) {
                        article.classList.remove('unread');
                        article.classList.add('read');
                    }
                } else {
                    alert('Failed to mark notification as read.');
                }
            })
            .catch(() => {
                alert('An error occurred. Please try again.');
            });
        }
    });
});
</script>
</body>
</html>
