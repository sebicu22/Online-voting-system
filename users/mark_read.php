<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'No notification ID']);
    exit;
}

$note_id = intval($data['id']);


// Use MySQLi instead of PDO
$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}

// Only allow marking notifications that belong to the user or are global
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
$stmt->bind_param('ii', $note_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not found or not allowed']);
}
$stmt->close();
$conn->close();
?>
