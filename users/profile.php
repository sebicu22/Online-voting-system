<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

try {
    $conn = new mysqli('localhost', 'root', '', 'evoteplatform');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get or Create Voter ID
    $voter_id = null;
    $stmt_voter = $conn->prepare("SELECT id FROM voters WHERE register_id = ?");
    $stmt_voter->bind_param("i", $user_id);
    $stmt_voter->execute();
    $res_voter = $stmt_voter->get_result();
    if ($row_voter = $res_voter->fetch_assoc()) {
        $voter_id = $row_voter['id'];
    } else {
        $stmt_insert_voter = $conn->prepare("INSERT INTO voters (register_id) VALUES (?)");
        $stmt_insert_voter->bind_param("i", $user_id);
        if ($stmt_insert_voter->execute()) {
            $voter_id = $conn->insert_id;
        } else {
            $errorMsg = "Could not create voter record: " . $stmt_insert_voter->error;
        }
        $stmt_insert_voter->close();
    }
    $stmt_voter->close();

    // Handle profile update (name, email)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
        $new_full_name = trim($_POST['full_name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');

        if ($new_full_name && $new_email) {
            $stmt = $conn->prepare('UPDATE register SET full_name = ?, email = ? WHERE id = ?');
            $stmt->bind_param('ssi', $new_full_name, $new_email, $user_id);
            if ($stmt->execute()) {
                $successMsg = 'Profile updated successfully!';
            } else {
                $errorMsg = 'Failed to update profile: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMsg = 'Full name and email are required.';
        }
    }

    // Handle profile image upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profile_image']['type'];
            $fileSize = $_FILES['profile_image']['size'];
            $maxFileSize = 5 * 1024 * 1024; // 5 MB

            if (!in_array($fileType, $allowedTypes)) {
                $errorMsg = 'Only JPG, PNG, and GIF files are allowed.';
            } elseif ($fileSize > $maxFileSize) {
                $errorMsg = 'Image file is too large. Max 5MB allowed.';
            } else {
                $uploadDir = 'uploads/profile_images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $newFileName = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare('UPDATE register SET image = ? WHERE id = ?');
                    $stmt->bind_param('si', $targetFile, $user_id);
                    if ($stmt->execute()) {
                        $successMsg = 'Profile image updated successfully!';
                    } else {
                        $errorMsg = 'Failed to update profile image in database: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errorMsg = 'Failed to upload file to server.';
                }
            }
        } else {
            $errorMsg = 'No image selected or an upload error occurred. Error code: ' . ($_FILES['profile_image']['error'] ?? 'N/A');
        }
    }

    // Fetch user data after any potential updates
    $stmt = $conn->prepare('SELECT id, username, full_name, email, image, status, created_at FROM register WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("User data not found for session user ID.");
    }

    // Get total votes cast by this user (if voter_id is available)
    $total_votes = 0;
    $total_elections_participated = 0;

    if ($voter_id) {
        $stmt_votes = $conn->prepare('SELECT COUNT(*) AS total_votes FROM voter_tracking WHERE voter_id = ?');
        $stmt_votes->bind_param('i', $voter_id);
        $stmt_votes->execute();
        $votes_result = $stmt_votes->get_result()->fetch_assoc();
        $total_votes = (int)$votes_result['total_votes'];
        $stmt_votes->close();

        $stmt_elections_participated = $conn->prepare('SELECT COUNT(DISTINCT election_id) AS total_elections_participated FROM voter_tracking WHERE voter_id = ?');
        $stmt_elections_participated->bind_param('i', $voter_id);
        $stmt_elections_participated->execute();
        $elections_participated_result = $stmt_elections_participated->get_result()->fetch_assoc();
        $total_elections_participated = (int)$elections_participated_result['total_elections_participated'];
        $stmt_elections_participated->close();
    }

    $result_total_elections = $conn->query('SELECT COUNT(*) AS total_elections FROM elections');
    $row_total_elections = $result_total_elections->fetch_assoc();
    $total_elections = (int)$row_total_elections['total_elections'];
    $result_total_elections->free();

    $conn->close();

} catch (Exception $e) {
    error_log("Profile page error: " . $e->getMessage());
    die("<div class='error'>A system error occurred. Please try again later.</div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<link rel="stylesheet" href="prof.css" />
</head>
<body>
<main class="content-prof">
    <section class="profile-container" aria-label="User Profile">
        <article class="profile-card" role="region" aria-labelledby="profile-title">
            <header class="profile-header">
                <div class="avatar-container" aria-label="Profile picture" tabindex="0" role="button" onclick="document.getElementById('avatar-input').click()" onkeypress="if(event.key==='Enter' || event.key===' '){document.getElementById('avatar-input').click();}">
                    <img
                        src="<?= htmlspecialchars($user['image'] ?? 'default-avatar.jpg') ?>"
                        alt="Profile Image of <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>"
                        class="profile-avatar"
                        onerror="this.src='default-avatar.jpg';"
                    />
                    <div class="avatar-overlay" aria-hidden="true">
                        <i class="fas fa-camera"></i>
                    </div>
                    <form method="post" enctype="multipart/form-data" style="display:none;">
                        <input type="file" id="avatar-input" name="profile_image" accept="image/jpeg,image/png,image/gif" onchange="this.form.submit()" />
                        <input type="hidden" name="upload_image" value="1" />
                    </form>
                </div>
                <div class="profile-titles">
                    <h1 id="profile-title"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
                    <p class="profile-username">@<?= htmlspecialchars($user['username']) ?></p>
                </div>
            </header>

            <div class="profile-status <?= htmlspecialchars($user['status']) ?>" role="status" aria-live="polite">
                <i class="fas <?= $user['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle' ?>" aria-hidden="true"></i>
                <?= $user['status'] === 'active' ? 'Eligible Voter' : 'Account Restricted' ?>
            </div>

            <section class="profile-stats" aria-label="Voting statistics">
                <div class="stat-item">
                    <div class="stat-value"><?= $total_votes ?></div>
                    <div class="stat-label">Votes Cast</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $total_elections_participated ?></div>
                    <div class="stat-label">Elections Participated In</div>
                </div>
                
            </section>

            <section class="profile-details" aria-label="User contact and membership details">
                <div class="detail-item">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-user-tag" aria-hidden="true"></i>
                    <span>Member since <?= date('Y', strtotime($user['created_at'])) ?></span>
                </div>
            </section>

            <nav class="profile-actions" aria-label="Profile actions">
                <button class="action-btn" type="button" onclick="showEditModal()">
                    <i class="fas fa-edit" aria-hidden="true"></i> Edit Profile
                </button>
            </nav>
        </article>

        <?php if (!empty($successMsg)): ?>
            <div class="success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
    </section>
</main>

<div id="edit-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-profile-title" aria-hidden="true" tabindex="-1">
    <div class="modal-content">
        <button class="close" aria-label="Close edit profile modal" onclick="hideEditModal()" type="button">&times;</button>
        <h2 id="edit-profile-title">Edit Profile</h2>
        <form id="edit-form" class="modal-form" method="post" autocomplete="off" novalidate>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required aria-required="true" />
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required aria-required="true" />
            </div>
            <input type="hidden" name="edit_profile" value="1" />
            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>
        </div>

<script>
const editModal = document.getElementById('edit-modal');

function showEditModal() {
    editModal.style.display = 'flex';
    editModal.setAttribute('aria-hidden', 'false');
}

function hideEditModal() {
    editModal.style.display = 'none';
    editModal.setAttribute('aria-hidden', 'true');
}

window.addEventListener('click', function(event) {
    if (event.target === editModal) {
        hideEditModal();
    }
});

window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && editModal.style.display === 'flex') {
        hideEditModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const successDiv = document.querySelector('.success');
    const errorDiv = document.querySelector('.error');

    if (successDiv) {
        setTimeout(() => {
            successDiv.style.opacity = '0';
            successDiv.style.transition = 'opacity 1s ease-out';
            setTimeout(() => successDiv.remove(), 1000);
        }, 5000);
    }
    if (errorDiv) {
        setTimeout(() => {
            errorDiv.style.opacity = '0';
            errorDiv.style.transition = 'opacity 1s ease-out';
            setTimeout(() => errorDiv.remove(), 1000);
        }, 7000);
    }
});
</script>
</body>
</html>