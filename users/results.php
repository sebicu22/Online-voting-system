<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQLi to report errors as exceptions for easier debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get election_id from query string
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;

// Initialize variables
$election = null;
$candidates = [];
$max_votes = 0;
$winners = [];
$total_votes = 0;
$error_message = ''; // For potential database errors

try {
    // Fetch election info (using prepared statement)
    if ($election_id) {
        $stmt_election = $conn->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt_election->bind_param("i", $election_id);
        $stmt_election->execute();
        $res_election = $stmt_election->get_result();
        $election = $res_election->fetch_assoc();
        $stmt_election->close();
    }

    // Fetch candidates and their vote counts (using prepared statement)
    if ($election_id && $election) { // Ensure election exists before fetching candidates
        $sql = "SELECT c.id, c.candidate_name, c.image_path,
                       (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS votes
                FROM candidates c
                WHERE c.election_id = ?
                ORDER BY votes DESC, c.candidate_name ASC";
        $stmt_candidates = $conn->prepare($sql);
        $stmt_candidates->bind_param("i", $election_id);
        $stmt_candidates->execute();
        $result_candidates = $stmt_candidates->get_result();
        while ($row = $result_candidates->fetch_assoc()) {
            $candidates[] = $row;
        }
        $stmt_candidates->close();
    }

    // Find winner(s) and calculate total votes
    foreach ($candidates as $c) {
        if ($c['votes'] > $max_votes) {
            $max_votes = $c['votes'];
        }
        $total_votes += $c['votes'];
    }

    // Filter winners after finding max_votes from all candidates
    $winners = array_filter($candidates, function($c) use ($max_votes) {
        return $c['votes'] == $max_votes && $max_votes > 0;
    });

} catch (mysqli_sql_exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Results page database error: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = "An unexpected error occurred: " . $e->getMessage();
    error_log("Results page general error: " . $e->getMessage());
} finally {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link rel="stylesheet" href="results.css">
  
</head>
<body>
<div class="results-container">
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (!$election): ?>
        <h2 class="results-title">Election Not Found</h2>
        <p style="text-align:center;">No such election exists or an error occurred while fetching election details.</p>
    <?php else: ?>
        <h2 class="results-title"><?= htmlspecialchars($election['title']) ?> - Results</h2>
        <p style="text-align:center; color:#555;"><?= htmlspecialchars($election['description']) ?></p>
        <hr style="border-top: 1px solid #eee; margin: 20px 0;">

        <?php if (empty($candidates)): ?>
            <p class="no-votes">No candidates found for this election.</p>
        <?php else: ?>
            <?php if ($max_votes == 0): ?>
                <p class="no-votes">No votes have been cast yet.</p>
            <?php else: ?>
                <div style="margin-bottom:1.5rem; text-align:center;">
                    <span class="winner">Winner<?= count($winners) > 1 ? 's' : '' ?>:</span>
                    <?php
                    $winner_names = array_map(function($w) {
                        return htmlspecialchars($w['candidate_name']);
                    }, $winners);
                    echo implode(', ', $winner_names);
                    ?>
                    <span style="color:#888;"> (<?= $max_votes ?> vote<?= $max_votes > 1 ? 's' : '' ?>)</span>
                </div>
            <?php endif; ?>

            <?php foreach ($candidates as $c):
                $percent = $total_votes > 0 ? round(($c['votes'] / $total_votes) * 100, 2) : 0;
            ?>
                <div class="candidate-row<?= ($c['votes'] == $max_votes && $max_votes > 0) ? ' winner' : '' ?>">
                    <span class="candidate-info">
                        <!-- <?php
                        $img = !empty($c['image_path'])
                            ? $c['image_path']
                            : '../images/candidate-default.png';
                        ?> -->
                        <?php
                        $img = !empty($c['image_path']) ? '../uploads/candidates/' . basename($c['image_path']) : '../images/candidate-default.png';
                        ?>
                        <img
                            src="<?= htmlspecialchars($img) ?>"
                            alt="Image of <?= htmlspecialchars($c['candidate_name']) ?>"
                            onerror="this.src='../images/candidate-default.png';"
                        />

                        <?= htmlspecialchars($c['candidate_name']) ?>
                    </span>
                    <span>
                        <span class="votes"><?= $c['votes'] ?></span>
                        <span class="percent">(<?= $percent ?>%)</span>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="total-votes-summary">
                Total votes cast: <?= $total_votes ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>