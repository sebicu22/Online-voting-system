<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit();
}
include('../connection.php');

$errors = [];
$success = '';

// Handle single candidate assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id']) && isset($_POST['candidate_name'])) {
    $election_id = intval($_POST['election_id']);
    $candidate_name = trim($_POST['candidate_name']);
    $image = $_FILES['candidate_image'] ?? null;

    if (empty($candidate_name)) {
        $errors[] = 'Candidate name is required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM elections WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $election_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 0) {
            $errors[] = 'Selected election does not exist.';
        }
        mysqli_stmt_close($stmt);

        // Fetch the election start date
        $stmt = mysqli_prepare($conn, "SELECT start_date FROM elections WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $election_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $start_date);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        if ($start_date && strtotime($start_date) <= time()) {
            $errors[] = 'Cannot add candidates. The election has already started.';
        }
        if (empty($errors)) {
            $image_path = null;
            if (
                $image && is_uploaded_file($image['tmp_name']) &&
                $image['error'] === UPLOAD_ERR_OK
            ) {
                $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
                $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $candidate_name);
                $upload_dir = '../uploads/candidates/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = uniqid('cand_', true) . '_' . $safe_name . '.' . $ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($image['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                }
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO candidates (election_id, candidate_name, image_path) VALUES (?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iss", $election_id, $candidate_name, $image_path);
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Candidate added successfully.';
                } else {
                    $errors[] = 'Error inserting candidate: ' . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = 'Failed to prepare insert statement.';
            }
        }
    }
}

// Handle candidate deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_candidate_id'])) {
    $cand_id = intval($_POST['delete_candidate_id']);
    $stmt = mysqli_prepare($conn, "SELECT image_path FROM candidates WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cand_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $img_path);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM candidates WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cand_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($img_path && file_exists(__DIR__ . '/../' . $img_path)) {
        unlink(__DIR__ . '/../' . $img_path);
    }

    $success = "Candidate deleted successfully.";
}

// --- CORRECTED SECTION: Always initialize all election keys ---
$elections = [];
// Get all elections with their details
$startDatesResult = mysqli_query($conn, "SELECT id, title, description, start_date FROM elections");
while ($row = mysqli_fetch_assoc($startDatesResult)) {
    $elections[$row['id']] = [
        'title' => $row['title'],
        'description' => $row['description'],
        'start_date' => $row['start_date'],
        'candidates' => []
    ];
}

// Now add candidates to each election
$sql = "SELECT e.id, c.id AS cand_id, c.candidate_name, c.image_path
        FROM elections e
        LEFT JOIN candidates c ON e.id = c.election_id
        ORDER BY e.id, c.id";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $eid = $row['id'];
    if ($row['cand_id'] !== null) {
        $elections[$eid]['candidates'][] = [
            'id' => $row['cand_id'],
            'name' => $row['candidate_name'],
            'image' => $row['image_path']
        ];
    }
}
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election & Candidate Management</title>
    <link rel="stylesheet" href="pollmagt.css">
</head>
<body>
<div class="content-pollmagt">
    <h1>Election & Candidate Management</h1>
    <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($elections)): ?>
        <p>No elections found. Please create elections first.</p>
    <?php else: ?>
        <?php foreach ($elections as $eid => $election): ?>
            <div class="election poll-item">
                <button class="collapse-toggle" type="button">
                    <span class="toggle-arrow">&#9654;</span>
                    <?= htmlspecialchars($election['title']) ?>
                </button>
                <div class="election-content" style="display:none;">
                    <?php if (!empty($election['description'])): ?>
                        <p class="description"><?= nl2br(htmlspecialchars($election['description'])) ?></p>
                    <?php endif; ?>

                    <h3>Candidates:</h3>
                    <?php if (empty($election['candidates'])): ?>
                        <p class="no-candidates">No candidates assigned yet.</p>
                    <?php else: ?>
                        <ul class="candidates">
                            <?php foreach ($election['candidates'] as $candidate): ?>
                                <li>
                                    <?php if ($candidate['image'] && file_exists(__DIR__ . '/' . $candidate['image'])): ?>
                                        <img src="<?= htmlspecialchars($candidate['image']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/150x100?text=No+Image" alt="No Image">
                                    <?php endif; ?>
                                    <div><?= htmlspecialchars($candidate['name']) ?></div>
                                    <form method="post" style="margin-top:8px;">
                                        <input type="hidden" name="delete_candidate_id" value="<?= $candidate['id'] ?>">
                                        <button type="submit" onclick="return confirm('Delete this candidate?')">Delete</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (strtotime($election['start_date']) > time()): ?>
                        <form class="assign-candidate poll-form" method="post" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" name="election_id" value="<?= $eid ?>" />

                            <label for="candidate_name_<?= $eid ?>">Candidate Name:</label>
                            <input type="text" id="candidate_name_<?= $eid ?>" name="candidate_name" required />

                            <label for="candidate_image_<?= $eid ?>">Candidate Image (optional):</label>
                            <input type="file" name="candidate_image" id="candidate_image_<?= $eid ?>" accept="image/*" />

                            <button type="submit">Add Candidate</button>
                        </form>
                    <?php else: ?>
                        <p class="info">This election has started. You cannot add more candidates.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
function setupElectionCollapsibles() {
    document.querySelectorAll('.collapse-toggle').forEach(btn => {
        btn.onclick = function() {
            const content = btn.nextElementSibling;
            const arrow = btn.querySelector('.toggle-arrow');
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                arrow.innerHTML = '\u25BC';
            } else {
                content.style.display = 'none';
                arrow.innerHTML = '\u25B6';
            }
        };
    });
    const first = document.querySelector('.election-content');
    if (first) {
        first.style.display = 'block';
        const btn = first.previousElementSibling.querySelector('.toggle-arrow');
        if (btn) btn.innerHTML = '\u25BC';
    }
}
setupElectionCollapsibles();

function setupAssignCandidateForms() {
    document.querySelectorAll('.assign-candidate.poll-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('.content-pollmagt');
                if (newContent) {
                    document.querySelector('.content-pollmagt').replaceWith(newContent);
                    setupElectionCollapsibles();
                    setupDeleteCandidateForms();
                    setupAssignCandidateForms();
                }
            })
            .catch(err => {
                alert('Submission failed: ' + err);
            });
        });
    });
}

function setupDeleteCandidateForms() {
    const content = document.querySelector('.content-pollmagt');
    if (!content) return;
    content.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.matches('.candidates form')) {
            e.preventDefault();
            if (!confirm('Delete this candidate?')) return;
            const formData = new FormData(form);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('.content-pollmagt');
                if (newContent) {
                    document.querySelector('.content-pollmagt').replaceWith(newContent);
                    setupElectionCollapsibles();
                    setupDeleteCandidateForms();
                    setupAssignCandidateForms();
                }
            })
            .catch(err => {
                alert('Delete failed: ' + err);
            });
        }
    });
}

setupDeleteCandidateForms();
setupAssignCandidateForms();
</script>
</body>
</html>
