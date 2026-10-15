<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get the voter's ID from the voters table
$stmt_voter = $conn->prepare('SELECT id FROM voters WHERE register_id = ?');
$stmt_voter->bind_param('i', $user_id);
$stmt_voter->execute();
$result_voter = $stmt_voter->get_result();
$voter = $result_voter->fetch_assoc();
$voter_id = $voter ? $voter['id'] : null;
$stmt_voter->close();

if ($voter_id) {
    $stmt = $conn->prepare('
        SELECT e.title, vt.voted_at
        FROM voter_tracking vt
        JOIN elections e ON vt.election_id = e.id
        WHERE vt.voter_id = ?
        ORDER BY vt.voted_at DESC
    ');
    $stmt->bind_param('i', $voter_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voting History</title>
    <link rel="stylesheet" href="vot.css">
    <style>
        
    </style>
</head>
<body>
    <h2 style="text-align:center;">Your Voting History</h2>
    <?php if (isset($result) && $result->num_rows > 0): ?>
        <table class="voting-history-table">
            <thead>
                <tr>
                    <th>Election Title</th>
                    <th>Date Voted</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['voted_at']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">You have not voted in any elections yet.</p>
    <?php endif; ?>
</body>
</html>
<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>
