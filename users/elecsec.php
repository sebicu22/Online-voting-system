<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Database connection with error handling
try {
    $conn = new mysqli('localhost', 'root', '', 'evoteplatform');
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);

    // Get all elections with voting status
    $sql = "SELECT e.*, 
            CASE 
                WHEN NOW() < e.start_date THEN 'Upcoming'
                WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'Active'
                ELSE 'Completed'
            END AS status,
            (SELECT COUNT(*) FROM voter_tracking WHERE election_id = e.id AND voter_id = ?) AS has_voted,
            (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) AS candidates_count,
            (SELECT COUNT(*) FROM votes WHERE election_id = e.id) AS total_votes
            FROM elections e
            ORDER BY e.start_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) throw new Exception("Query failed: " . $stmt->error);
    $result = $stmt->get_result();
    $elections = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    die("System error. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="elecsec.css">
</head>
<body>
    <div class="content-elec">
        <div class="elecsec-container">
            <header class="portal-header">
                <h2>Welcome to E-Vote, <?= htmlspecialchars($_SESSION['username'] ?? 'Voter') ?>!</h2>
            </header>

            <div class="election-filters">
                <button class="filter-btn active" data-status="all">All Elections</button>
                <button class="filter-btn" data-status="active"><i class="fas fa-vote-yea"></i> Active</button>
                <button class="filter-btn" data-status="upcoming"><i class="fas fa-clock"></i> Upcoming</button>
                <button class="filter-btn" data-status="completed"><i class="fas fa-check-circle"></i> Completed</button>
            </div>

            <?php if (empty($elections)): ?>
                <div class="no-elections">
                    <img src="../images/election.svg" alt="No elections">
                    <p>No elections available at this time. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="election-grid">
                    <?php foreach ($elections as $election): ?>
                        <div class="election-card <?= strtolower($election['status']) ?>">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($election['title']) ?></h3>
                                <span class="status-badge <?= strtolower($election['status']) ?>">
                                    <i class="fas <?= match($election['status']) {
                                        'Active' => 'fa-hourglass-half',
                                        'Upcoming' => 'fa-clock',
                                        'Completed' => 'fa-check-circle'
                                    } ?>"></i> <?= $election['status'] ?>
                                </span>
                            </div>
                            
                            <div class="card-body">
                                <p class="election-desc"><?= htmlspecialchars($election['description']) ?></p>
                                
                                <div class="election-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-users"></i>
                                        <span><?= $election['candidates_count'] ?> Candidates</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-vote-yea"></i>
                                        <span><?= $election['total_votes'] ?> Votes Cast</span>
                                    </div>
                                </div>

                                <div class="time-info">
                                    <div class="time-item">
                                        <i class="fas fa-hourglass-start"></i>
                                        <span><?= date('M d, Y H:i', strtotime($election['start_date'])) ?></span>
                                    </div>
                                    <div class="time-item">
                                        <i class="fas fa-hourglass-end"></i>
                                        <span><?= date('M d, Y H:i', strtotime($election['end_date'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <?php if ($election['status'] === 'Active'): ?>
                                    <?php if ($election['has_voted']): ?>
                                        <button class="voted-btn" disabled>
                                            <i class="fas fa-check-double"></i> Voted
                                        </button>
                                    <?php else: ?>
                                        <a href="#" 
                                           class="vote-now-btn"
                                           data-election-id="<?= $election['id'] ?>">
                                            <i class="fas fa-check-to-slot"></i> Vote Now
                                        </a>
                                    <?php endif; ?>
                                <?php elseif ($election['status'] === 'Upcoming'): ?>
                                    <button class="upcoming-btn" disabled>
                                        <i class="fas fa-clock"></i> Voting Opens Soon
                                    </button>
                                <?php else: ?>
                                    <div class="results-link">
                                        <a href="#" class="view-results-btn" data-election-id="<?= $election['id'] ?>">
                                            <i class="fas fa-chart-bar"></i> View Results
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="voteModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeVoteModal">&times;</span>
        <div id="voteFormContainer"></div>
      </div>
    </div>

    <div id="resultsModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeResultsModal">&times;</span>
        <div id="resultsFormContainer"></div>
      </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const elecsecContainer = document.querySelector('.elecsec-container');
    if (!elecsecContainer) return;

    elecsecContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('filter-btn')) {
            e.preventDefault();
            const btn = e.target;
            const status = btn.dataset.status.toLowerCase();

            // Remove active from all, add to clicked
            elecsecContainer.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Show/hide election cards
            elecsecContainer.querySelectorAll('.election-card').forEach(card => {
                const cardStatus = card.className.match(/\b(active|upcoming|completed)\b/i);
                if (
                    status === 'all' ||
                    (cardStatus && cardStatus[0].toLowerCase() === status)
                ) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.vote-now-btn');
        if (btn) {
            e.preventDefault();
            var electionId = btn.getAttribute('data-election-id');
            var modal = document.getElementById('voteModal');
            var container = document.getElementById('voteFormContainer');
            container.innerHTML = '<iframe src="vote.php?election_id=' + encodeURIComponent(electionId) + '" frameborder="0" style="width:100%;height:80vh;"></iframe>';
            modal.style.display = 'flex';
        }
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-results-btn');
        if (btn) {
            e.preventDefault();
            var electionId = btn.getAttribute('data-election-id');
            var modal = document.getElementById('resultsModal');
            var container = document.getElementById('resultsFormContainer');
            container.innerHTML = '<iframe src="results.php?election_id=' + encodeURIComponent(electionId) + '" frameborder="0" style="width:100%;height:80vh;"></iframe>';
            modal.style.display = 'flex';
        }
    });

    // Close Vote Modal
    var closeVote = document.getElementById('closeVoteModal');
    var voteModal = document.getElementById('voteModal');
    if (closeVote && voteModal) {
        closeVote.onclick = function() {
            voteModal.style.display = 'none';
            document.getElementById('voteFormContainer').innerHTML = '';
        };
        window.addEventListener('click', function(event) {
            if (event.target === voteModal) {
                voteModal.style.display = 'none';
                document.getElementById('voteFormContainer').innerHTML = '';
            }
        });
    }

    // Close Results Modal
    var closeResults = document.getElementById('closeResultsModal');
    var resultsModal = document.getElementById('resultsModal');
    if (closeResults && resultsModal) {
        closeResults.onclick = function() {
            resultsModal.style.display = 'none';
            document.getElementById('resultsFormContainer').innerHTML = '';
        };
        window.addEventListener('click', function(event) {
            if (event.target === resultsModal) {
                resultsModal.style.display = 'none';
                document.getElementById('resultsFormContainer').innerHTML = '';
            }
        });
    }
});
</script>
</body>
</html>
