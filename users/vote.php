<?php
session_start();
include('../connection.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if user is logged in and is a voter
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'voter') {
    header('Location: ../login/login.php');
    exit();
}

$user_id = $_SESSION['user_id']; 
$success = '';
$error = '';
$voter_id = null; 

try {
    
    $stmt_voter = $conn->prepare("SELECT id FROM voters WHERE register_id = ?");
    $stmt_voter->bind_param("i", $user_id);
    $stmt_voter->execute();
    $result_voter = $stmt_voter->get_result();
    if ($row_voter = $result_voter->fetch_assoc()) {
        $voter_id = $row_voter['id'];
    } else {
        
        $stmt_insert_voter = $conn->prepare("INSERT INTO voters (register_id) VALUES (?)");
        $stmt_insert_voter->bind_param("i", $user_id);
        if ($stmt_insert_voter->execute()) {
            $voter_id = mysqli_insert_id($conn);
        } else {
            $error = "Could not create voter record: " . $stmt_insert_voter->error;
        }
        $stmt_insert_voter->close();
    }
    $stmt_voter->close();


    // Handle vote submission
    if ($voter_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id'], $_POST['candidate_id'])) {
        $election_id = intval($_POST['election_id']);
        $candidate_id = intval($_POST['candidate_id']);

        // Fetch election to check end date and title
        $stmt_election = $conn->prepare("SELECT title, end_date FROM elections WHERE id = ?");
        $stmt_election->bind_param("i", $election_id);
        $stmt_election->execute();
        $result_election = $stmt_election->get_result();
        $election = $result_election->fetch_assoc();
        $stmt_election->close();

        if (!$election) {
            $error = "Election not found.";
        } elseif (strtotime($election['end_date']) < time()) {
            $error = "This election has already ended.";
        } else {
            // Check if already voted
            $stmt_check_vote = $conn->prepare("SELECT id FROM voter_tracking WHERE election_id = ? AND voter_id = ?");
            $stmt_check_vote->bind_param("ii", $election_id, $voter_id);
            $stmt_check_vote->execute();
            $stmt_check_vote->store_result();
            if ($stmt_check_vote->num_rows > 0) {
                $error = "You have already voted in this election.";
            } else {
                // Begin Transaction to ensure atomicity
                $conn->begin_transaction();

                try {
                    // Insert vote anonymously into 'votes' table
                    $stmt_vote = $conn->prepare("INSERT INTO votes (election_id, candidate_id) VALUES (?, ?)");
                    $stmt_vote->bind_param("ii", $election_id, $candidate_id);
                    if (!$stmt_vote->execute()) {
                        throw new Exception("Failed to record vote in 'votes' table: " . $stmt_vote->error);
                    }
                    $stmt_vote->close();

                    // Mark that the voter has voted in 'voter_tracking'
                    $stmt_track = $conn->prepare("INSERT INTO voter_tracking (election_id, voter_id) VALUES (?, ?)");
                    $stmt_track->bind_param("ii", $election_id, $voter_id);
                    if (!$stmt_track->execute()) {
                        throw new Exception("Failed to record vote in 'voter_tracking': " . $stmt_track->error);
                    }
                    $stmt_track->close();

                    // Update results table (ON DUPLICATE KEY UPDATE)
                    $stmt_update_results = $conn->prepare("INSERT INTO results (election_id, candidate_id, vote_count) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE vote_count = vote_count + 1");
                    $stmt_update_results->bind_param("ii", $election_id, $candidate_id);
                    if (!$stmt_update_results->execute()) {
                        throw new Exception("Failed to update 'results' table: " . $stmt_update_results->error);
                    }
                    $stmt_update_results->close();

                    // Send notification
                    $election_title = htmlspecialchars($election['title']);
                    $type = 'vote';
                    $message = "You have successfully voted in the election: " . $election_title;

                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
                    $stmt_notify->bind_param('iss', $user_id, $type, $message);
                    if (!$stmt_notify->execute()) {
                        error_log("Failed to send notification for user " . $user_id . ": " . $stmt_notify->error);
                    }
                    $stmt_notify->close();

                    $conn->commit(); // Commit transaction if all good
                    $success = "Your vote has been recorded. Thank you!";

                    // Redirect to self to prevent form re-submission on refresh
                    header('Location: vote.php?success=' . urlencode($success));
                    exit();

                } catch (Exception $e) {
                    $conn->rollback(); // Rollback on any error
                    $error = "Failed to record your vote: " . $e->getMessage();
                    error_log("Vote transaction error: " . $e->getMessage()); // Log detailed error
                }
            }
            $stmt_check_vote->close();
        }
    }

    // After potential POST, check for success/error messages in GET (after redirect)
    if (isset($_GET['success'])) {
        $success = htmlspecialchars($_GET['success']);
    }
    if (isset($_GET['error'])) {
        $error = htmlspecialchars($_GET['error']);
    }

    // --- Fetch active elections for display (This was the line causing the error) ---
    $elections = [];
    $stmt_elections = $conn->prepare("SELECT id, title, description, end_date FROM elections WHERE status = 'active'");
    $stmt_elections->execute();
    $result_elections = $stmt_elections->get_result();
    while ($row = $result_elections->fetch_assoc()) {
        $elections[] = $row;
    }
    $stmt_elections->close();

} catch (mysqli_sql_exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Vote page database error: " . $e->getMessage()); // Log the error for admin
} catch (Exception $e) {
    $error = "An unexpected error occurred: " . $e->getMessage();
    error_log("Vote page general error: " . $e->getMessage()); // Log the error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Page</title>
    <link rel="stylesheet" href="vote.css">
</head>
<body>
    <h1>Choose Candidate</h1>

    <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($elections)): ?>
        <p>No active elections available at the moment.</p>
    <?php else: ?>
        <?php foreach ($elections as $election):
            // Check if the user has voted in this election
            $has_voted_in_this_election = false;
            if ($voter_id) {
                $stmt_check_vote = $conn->prepare("SELECT id FROM voter_tracking WHERE election_id = ? AND voter_id = ?");
                $stmt_check_vote->bind_param("ii", $election['id'], $voter_id);
                $stmt_check_vote->execute();
                $stmt_check_vote->store_result();
                if ($stmt_check_vote->num_rows > 0) {
                    $has_voted_in_this_election = true;
                }
                $stmt_check_vote->close();
            }
            $has_ended = strtotime($election['end_date']) < time();
        ?>
            <div class="election-block">
                <h2><?= htmlspecialchars($election['title']) ?></h2>
                <p><?= nl2br(htmlspecialchars($election['description'])) ?></p>

                <?php if ($has_ended): ?>
                    <p class="election-ended">This election has ended. Voting is closed.</p>
                <?php else: ?>
                    <?php if (empty($success)): ?>
                        <?php if ($has_voted_in_this_election): ?>
                            <p class="election-ended" style="color: #007bff;">You have already voted in this election. Thank you!</p>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                <ul class="candidates-list">
                                    <?php
                                    $candidates = [];
                                    $stmt_candidates = $conn->prepare("SELECT id, candidate_name, image_path FROM candidates WHERE election_id = ?");
                                    $stmt_candidates->bind_param("i", $election['id']);
                                    $stmt_candidates->execute();
                                    $result_candidates = $stmt_candidates->get_result();
                                    while ($cand = $result_candidates->fetch_assoc()) {
                                        $candidates[] = $cand;
                                    }
                                    $stmt_candidates->close(); 
                                    ?>
                                    <?php if (empty($candidates)): ?>
                                        <li>No candidates available for this election.</li>
                                    <?php else: ?>
                                        <?php foreach ($candidates as $candidate): ?>
                                            <li class="candidate-item">
                                                <label>
                                                    <!-- <?php
                                                    // $img = !empty($candidate['image_path'])
                                                    //     ? $candidate['image_path']
                                                    //     : '../images/candidate-default.png';
                                                     ?> -->
                                                    <?php
                                                    $img = !empty($candidate['image_path']) ? '../uploads/candidates/' . basename($candidate['image_path']) : '../images/candidate-default.png';
                                                    ?>
                                                    <img
                                                        src="<?= htmlspecialchars($img) ?>"
                                                        alt="Image of <?= htmlspecialchars($candidate['candidate_name']) ?>"
                                                        onerror="this.src='../images/candidate-default.png';"
                                                    />
                                                    <input type="radio" name="candidate_id" value="<?= $candidate['id'] ?>" required>
                                                    <span><?= htmlspecialchars($candidate['candidate_name']) ?></span>
                                                </label>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                                <?php if (!empty($candidates)): ?>
                                    <button type="submit" class = "submit-btn">Vote Now</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; /* End of has_voted_in_this_election check */ ?>
                    <?php endif; /* End of success check */ ?>
                <?php endif; /* End of has_ended check */ ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
<?php
// Only close the connection at the very end, after all PHP code is done
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>