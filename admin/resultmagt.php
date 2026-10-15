<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8');

function calculate_percentage($votes, $total) {
    return $total > 0 ? round(($votes / $total) * 100, 1) : 0;
}

$elections_result = $conn->query("
    SELECT * FROM elections 
    ORDER BY 
        CASE status
            WHEN 'active' THEN 1
            WHEN 'completed' THEN 2
            WHEN 'upcoming' THEN 3
            ELSE 4
        END, id DESC
");

$elections = $elections_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Election Results</title>
    <link rel="stylesheet" href="result.css">
</head>
<body>

<h1 class="res">All Election Results</h1>

<!-- Remove the form to prevent reloads -->
<label for="election_id" style="text-align:center;display:block;margin-bottom:24px;">
    <strong>Choose Election:</strong>
    <select name="election_id" id="election_id" style="margin-left: 8px; padding: 6px 12px;">
        <option value="">-- All Elections --</option>
        <?php foreach ($elections as $election): ?>
            <option value="<?= $election['id'] ?>" <?= (isset($_GET['election_id']) && $_GET['election_id'] == $election['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($election['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</label>

<div id="results-container">
<?php if (empty($elections)): ?>
    <p style="text-align:center; color:#c00;">No elections found.</p>
<?php else: ?>
    <?php
    $selected_id = isset($_GET['election_id']) && $_GET['election_id'] !== '' ? (int)$_GET['election_id'] : null;
    foreach ($elections as $election):
        if ($selected_id && $election['id'] != $selected_id) continue;
    ?>
        <div class="content-result">
            <h2><?= htmlspecialchars($election['title']) ?></h2>
            <p class="poll-desc">Election ID: <strong>#<?= $election['id'] ?></strong></p>
            <p><?= nl2br(htmlspecialchars($election['description'] ?? '')) ?></p>

            <?php
            $stmt = $conn->prepare("SELECT id, candidate_name AS title FROM candidates WHERE election_id = ?");
            $stmt->bind_param('i', $election['id']);
            $stmt->execute();
            $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $votes_per_candidate = [];
            $total_votes = 0;
            if (!empty($candidates)) {
                $stmt = $conn->prepare("SELECT candidate_id, COUNT(*) as vote_count FROM votes WHERE election_id = ? GROUP BY candidate_id");
                $stmt->bind_param('i', $election['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $votes_per_candidate[$row['candidate_id']] = (int)$row['vote_count'];
                    $total_votes += (int)$row['vote_count'];
                }
                $stmt->close();
            }
            ?>

            <div class="result-summary">
                <strong>Total Votes:</strong> <?= $total_votes ?> <br>
                <span class="result-date">Results as of <?= date('M d, Y H:i') ?></span>
            </div>

            <?php if ($total_votes === 0): ?>
                <div class="no-votes">
                    <p>No votes have been cast yet for this election.</p>
                </div>
            <?php else: ?>
                <div class="result-answers">
                    <?php foreach ($candidates as $candidate):
                        $candidate_votes = $votes_per_candidate[$candidate['id']] ?? 0;
                        $percent = calculate_percentage($candidate_votes, $total_votes);
                    ?>
                        <div class="answer-title">
                            <?= htmlspecialchars($candidate['title']) ?>
                            <span class="votes">(<?= $candidate_votes ?> votes)</span>
                        </div>
                        <div class="result-bar-container" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="result-bar" style="width: <?= $percent ?>%">
                                <?= $percent ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<button onclick="window.print()" class="print-btn">Print Results</button>

<script>
document.getElementById('election_id').addEventListener('change', function() {
    const electionId = this.value;
    const params = electionId ? '?election_id=' + encodeURIComponent(electionId) : '';
    fetch('resultmagt.php' + params)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newResults = doc.getElementById('results-container');
            if (newResults) {
                document.getElementById('results-container').innerHTML = newResults.innerHTML;
            }
        });
});
</script>
</body>
</html>
