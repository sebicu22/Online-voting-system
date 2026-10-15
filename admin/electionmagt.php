<?php

session_start();
// Database connection (only once)
$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Automatically update election statuses

// 1. Set status to 'active' if voting is ongoing and not already 'active' or 'ended'
$conn->query("UPDATE elections SET status = 'active' WHERE start_date <= NOW() AND end_date > NOW() AND status NOT IN ('active', 'ended')");

// 2. Set status to 'completed' if voting has ended and not already 'ended'
$conn->query("UPDATE elections SET status = 'completed' WHERE end_date <= NOW() AND status != 'ended'");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // 1. Get all candidate IDs for this election
    $candidate_ids = [];
    $stmt = $conn->prepare("SELECT id FROM candidates WHERE election_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $candidate_ids[] = $row['id'];
    }
    $stmt->close();

    // 2. Delete results for these candidates
    if (!empty($candidate_ids)) {
        $in = implode(',', array_fill(0, count($candidate_ids), '?'));
        $types = str_repeat('i', count($candidate_ids));
        $stmt = $conn->prepare("DELETE FROM results WHERE candidate_id IN ($in)");
        $stmt->bind_param($types, ...$candidate_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Delete candidates associated with the election
    $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // 4. Delete the election itself
    $stmt = $conn->prepare("DELETE FROM elections WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: electionmagt.php");
    exit;
}

// Example: Close election logic
if (isset($_GET['close_id'])) {
    $close_id = (int)$_GET['close_id'];

    // Get election title and end_date for notification and check
    $titleRes = $conn->query("SELECT title, end_date FROM elections WHERE id = $close_id");
    $election_title = '';
    $end_date = '';
    if ($row = $titleRes->fetch_assoc()) {
        $election_title = $row['title'];
        $end_date = $row['end_date'];
    }

    // Only allow closing if the election is completed (end_date in the past)
    if (strtotime($end_date) <= time()) {
        // Update status to 'ended'
        $stmt = $conn->prepare("UPDATE elections SET status = 'ended' WHERE id = ?");
        $stmt->bind_param('i', $close_id);
        $stmt->execute();
        $stmt->close();

        // Insert notification with admin's user_id
        $admin_id = $_SESSION['user_id'];
        $type = 'announcement';
        $message = "The election '{$election_title}' has ended. Check the results!";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $admin_id, $type, $message);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "<script>alert('Election cannot be closed before its end date.');window.location='electionmagt.php';</script>";
        exit;
    }

    header("Location: electionmagt.php");
    exit;
}

// Fetch all elections
$elections = [];
$result = $conn->query("SELECT * FROM elections ORDER BY start_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $elections[] = $row;
    }
}
foreach ($elections as &$election) {
    // Update status in PHP as well for immediate UI feedback
    if ($election['status'] !== 'ended' && strtotime($election['end_date']) <= time()) {
        $election['status'] = 'completed';
        // Optionally, update the DB as well:
        $stmt = $conn->prepare("UPDATE elections SET status = 'completed' WHERE id = ?");
        $stmt->bind_param('i', $election['id']);
        $stmt->execute();
        $stmt->close();
    }
    // NEW: Update to 'active' if within voting period and not ended
    elseif ($election['status'] !== 'ended' && strtotime($election['start_date']) <= time() && strtotime($election['end_date']) > time()) {
        $election['status'] = 'active';
        $stmt = $conn->prepare("UPDATE elections SET status = 'active' WHERE id = ?");
        $stmt->bind_param('i', $election['id']);
        $stmt->execute();
        $stmt->close();
    }
}
unset($election); // Clean up reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Election Management</title>
    <link rel="stylesheet" href="elemag.css" />
    <link rel="stylesheet" href="create_election.css" />
</head>
<body>
    <div class="content-election">
        <h1>Election Management</h1>
        <button id="createElectionBtn" class="btn add">+ Create New Election</button>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Start Date & Time</th>
                        <th>End Date & Time</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($elections): ?>
                    <?php foreach ($elections as $election): ?>
                        <tr>
                            <td><?=htmlspecialchars($election['id'])?></td>
                            <td><?=htmlspecialchars($election['title'])?></td>
                            <td><?=htmlspecialchars(date('Y-m-d H:i', strtotime($election['start_date'])))?></td>
                            <td><?=htmlspecialchars(date('Y-m-d H:i', strtotime($election['end_date'])))?></td>
                            <td><?=htmlspecialchars(ucfirst($election['status']))?></td>
                            <td class="description-column"><?=htmlspecialchars($election['description'])?></td>
                            <td class="actions-column">
                                <a href="edit_election.php?id=<?= $election['id'] ?>" 
                                   class="btn edit edit-election-btn" 
                                   data-id="<?= $election['id'] ?>">Edit</a>
                                <a href="electionmagt.php?delete_id=<?= $election['id'] ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this election?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No elections found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal Popup for Create Election -->
    <div id="createElectionModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeCreateElection">&times;</span>
        <iframe src="create_election.php" frameborder="0" style="width:100%;height:80vh;"></iframe>
      </div>
    </div>

    <!-- Modal Popup for Edit Election -->
    <div id="editElectionModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeEditElection">&times;</span>
        <div id="editElectionFormContainer"></div>
      </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Election Modal logic
    document.querySelectorAll('.edit-election-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var modal = document.getElementById('editElectionModal');
            var container = document.getElementById('editElectionFormContainer');
            container.innerHTML = '<iframe src="edit_election.php?id=' + encodeURIComponent(id) + '" frameborder="0" style="width:100%;height:80vh;"></iframe>';
            modal.style.display = 'block';
        });
    });

    var closeEdit = document.getElementById('closeEditElection');
    var editModal = document.getElementById('editElectionModal');
    if (closeEdit && editModal) {
        closeEdit.onclick = function() {
            editModal.style.display = 'none';
            document.getElementById('editElectionFormContainer').innerHTML = '';
        };
        window.addEventListener('click', function(event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
                document.getElementById('editElectionFormContainer').innerHTML = '';
            }
        });
    }

    // Create Election Modal logic
    var createBtn = document.getElementById('createElectionBtn');
    var createModal = document.getElementById('createElectionModal');
    var closeCreate = document.getElementById('closeCreateElection');

    if (createBtn && createModal && closeCreate) {
        createBtn.onclick = function(e) {
            e.preventDefault();
            createModal.style.display = 'block';
        };
        closeCreate.onclick = function() {
            createModal.style.display = 'none';
            // Optionally reset iframe:
            // createModal.querySelector('iframe').src = createModal.querySelector('iframe').src;
        };
        window.addEventListener('click', function(event) {
            if (event.target === createModal) {
                createModal.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>
