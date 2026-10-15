<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Update ticket status and create notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $new_status = $_POST['new_status'] ?? '';
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);

    if ($ticket_id > 0 && in_array($new_status, ['open', 'in_progress', 'resolved'])) {
        // Update the ticket status
        $stmt = $conn->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $ticket_id);
        $stmt->execute();
        $stmt->close();

        // Fetch user_id of ticket owner
        $user_id_result = $conn->prepare("SELECT user_id FROM support_tickets WHERE id = ?");
        $user_id_result->bind_param("i", $ticket_id);
        $user_id_result->execute();
        $user_id_result->bind_result($user_id);
        $user_id_result->fetch();
        $user_id_result->close();

        if ($user_id) {
            // Insert notification for ticket owner
            $admin_id = $_SESSION['user_id'] ?? null; // Admin who updated the ticket
            $type = 'ticket_update';
            $message = "Your support ticket #{$ticket_id} status has been updated to '{$new_status}'.";

            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
            $notif_stmt->bind_param('iss', $user_id, $type, $message);
            $notif_stmt->execute();
            $notif_stmt->close();
        }

        $_SESSION['message'] = "Ticket updated and notification sent.";

        // AJAX response: return message + table HTML
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            ob_start();
            ?>
            <div id="ticket-message">
            <p class="success"><?= htmlspecialchars($_SESSION['message']) ?></p>
            </div>
            <div id="tickets-table">
            <table class="tick">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Fetch updated tickets for the table
                $tickets = [];
                $sql = "SELECT st.*, r.username FROM support_tickets st JOIN register r ON st.user_id = r.id ORDER BY st.created_at DESC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $tickets[] = $row;
                }

                foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['id']) ?></td>
                        <td><?= htmlspecialchars($ticket['username']) ?></td>
                        <td><?= htmlspecialchars($ticket['subject']) ?></td>
                        <td><?= nl2br(htmlspecialchars($ticket['message'])) ?></td>
                        <td><?= htmlspecialchars($ticket['status']) ?></td>
                        <td>
                            <form class="update-ticket-form" method="post" style="margin:0;">
                                <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <select name="new_status" required>
                                    <option value="">Select</option>
                                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php
            unset($_SESSION['message']);
            echo ob_get_clean();
            exit();
        }

        header("Location: tech.php");
        exit();
    } else {
        $_SESSION['message'] = "Invalid ticket ID or status.";
    }

    // If AJAX, return the message and exit
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo '<p class="success">' . htmlspecialchars($_SESSION['message']) . '</p>';
        unset($_SESSION['message']);
        exit();
    }

    header("Location: tech.php");
    exit();
}

// Fetch tickets
$tickets = [];
$sql = "SELECT st.*, r.username FROM support_tickets st JOIN register r ON st.user_id = r.id ORDER BY st.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Technical Support</title>
    <link rel="stylesheet" href="tech.css" />
</head>
<body>
    <div class="content-tech">
        <h1>Support Tickets</h1>

        <div id="ticket-message">
        <?php if (isset($_SESSION['message'])): ?>
            <p class="success"><?= htmlspecialchars($_SESSION['message']) ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        </div>

        <div id="tickets-table">
            <?php if (empty($tickets)): ?>
                <p>No tickets yet.</p>
            <?php else: ?>
            <table class="tick">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['id']) ?></td>
                        <td><?= htmlspecialchars($ticket['username']) ?></td>
                        <td><?= htmlspecialchars($ticket['subject']) ?></td>
                        <td><?= nl2br(htmlspecialchars($ticket['message'])) ?></td>
                        <td><?= htmlspecialchars($ticket['status']) ?></td>
                        <td>
                            <form class="update-ticket-form" method="post" style="margin:0;">
                                <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <select name="new_status" required>
                                    <option value="">Select</option>
                                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
    </div>
    
</body>
</html>
