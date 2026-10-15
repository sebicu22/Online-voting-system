<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'No notification ID']);
    exit;
}
$user_id = $_SESSION['user_id'];
$notif_id = intval($_POST['id']);

$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}
$stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $notif_id, $user_id);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);