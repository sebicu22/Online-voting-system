<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'voter') {
    header('Location: ../login/login.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $conn = new mysqli('localhost', 'root', '', 'evoteplatform');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare('SELECT id FROM register WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $_SESSION['username']);
            $stmt->execute();
            $stmt->bind_result($fetched_id);
            if ($stmt->fetch()) {
                $_SESSION['user_id'] = $fetched_id;
            }
            $stmt->close();
        }
        $conn->close();
    }
}
$user_id = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';
$user = null;


try {
    $conn = new mysqli('localhost', 'root', '', 'evoteplatform');
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
        $new_full_name = trim($_POST['full_name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        if ($new_full_name && $new_email) {
            $stmt = $conn->prepare('UPDATE register SET full_name = ?, email = ? WHERE id = ?');
            $stmt->bind_param('ssi', $new_full_name, $new_email, $user_id);
            if ($stmt->execute()) {
                $successMsg = 'Profile updated successfully!';
            } else {
                $errorMsg = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        } else {
            $errorMsg = 'Name and email cannot be empty.';
        }
    }

    // Get user data
    $stmt = $conn->prepare('SELECT id, username, full_name, email, image FROM register WHERE id = ?');
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $profile_image = $user && !empty($user['image']) ? $user['image'] : 'images/propic1.jpg';

    $conn->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("<div class='error'>System error. Please try again later.</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Vote Voter Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="prof.css">
    <link rel="stylesheet" href="result.css">
    </head>
<body>
  <div class="side-bar">
    <div class="brandname">
        <h1 class="name">E-Vote</h1>
    </div>
    <ul class="side-list">
        <li><a href="#" data-page="udashboard.php"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
        <li><a href="#" data-page="profile.php"><i class="fa fa-user"></i><span>Profile</span></a></li>
        <li><a href="#" data-page="elecsec.php"><i class="fa fa-calendar"></i><span>Elections Section</span></a></li>
        <li><a href="#" data-page="vothis.php"><i class="fa fa-history"></i><span>Voting History</span></a></li>
        <li><a href="#" data-page="notification.php"><i class="fa fa-bell"></i><span>Notifications</span></a></li>
        <li><a href="#" data-page="helsup.php"><i class="fa fa-question-circle"></i><span>Help & Support</span></a></li>
        <li><a href="../signout.php"><i class="fa fa-sign-out"></i><span>Sign Out</span></a></li>
    </ul>
</div>
  <div id="globalMsg"></div>
  <div class="container">
    <header class="header">
  <nav class="nav">
    <div class=""></div>
    <div class="user-header-info">
      <span class="user-welcome">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
      <span class="user-role">Voter</span>
      <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="header-profile-img" />
    </div>
  </nav>
</header>


        <section class="content">
            <div class="cards">
                <div class="card">
                    <div class="box">
                        <h3>Upcoming Elections</h3>
                        <p>See all elections you can participate in and their status.</p>
                    </div>
                    <div class="icon-case">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="box">
                        <h3>Your Voting History</h3>
                        <p>Review your past votes and participation record.</p>
                    </div>
                    <div class="icon-case">
                        <i class="fa fa-history"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="box">
                        <h3>Notifications</h3>
                        <p>Stay updated with important announcements and results.</p>
                    </div>
                    <div class="icon-case">
                        <i class="fa fa-bell"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="box">
                        <h3>Help & Support</h3>
                        <p>Get assistance or find answers to your questions.</p>
                    </div>
                    <div class="icon-case">
                        <i class="fa fa-question-circle"></i>
                    </div>
                </div>
            </div>
            <div class="content-2">
                <div class="welcome-box">
                    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                    <hr>
                    <p>Access all your voting features, view upcoming elections, check your voting history, and stay informed with notifications. Use the sidebar to navigate.</p>
                </div>
            </div>
 <!-- Vote Modal -->
<div id="voteModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" id="closeVoteModal">&times;</span>
    <div id="voteFormContainer"></div>
  </div>
</div>

<!-- Results Modal -->
<div id="resultsModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" id="closeResultsModal">&times;</span>
    <div id="resultsFormContainer"></div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if (!empty($errorMsg)): ?>
  <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<script>
  function showEditModal() {
    const modal = document.getElementById('edit-modal');
    if (modal) modal.style.display = 'flex';
  }

  function hideEditModal() {
    const modal = document.getElementById('edit-modal');
    if (modal) modal.style.display = 'none';
  }

  // Global click listener to close modals
  window.onclick = function(event) {
    const modals = ['edit-modal', 'voteModal', 'resultsModal'];
    modals.forEach(id => {
      const modal = document.getElementById(id);
      if (modal && event.target === modal) {
        modal.style.display = 'none';
        if (id === 'voteModal') document.getElementById('voteFormContainer').innerHTML = '';
        if (id === 'resultsModal') document.getElementById('resultsFormContainer').innerHTML = '';
      }
    });
  };

  document.addEventListener("DOMContentLoaded", function () {
  const links = document.querySelectorAll(".side-list a");
  const contentDiv = document.querySelector(".content");

  // Sidebar navigation: attach only once
  links.forEach(link => {
    link.addEventListener("click", function (event) {
      const page = this.getAttribute("data-page");
      if (!page) return;
      event.preventDefault();
      links.forEach(l => l.classList.remove('active'));
      this.classList.add('active');
      loadContent(page);
    });
  });

  function loadContent(page) {
    fetch(page)
      .then(res => res.text())
      .then(html => {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const msg = temp.querySelector('.success, .error');
        if (msg) {
          document.getElementById('globalMsg').innerHTML = msg.outerHTML;
        } else {
          document.getElementById('globalMsg').innerHTML = '';
        }
        contentDiv.innerHTML = html;

        // Re-attach handlers for new content
        attachSupportFormHandler();
        attachElectionFilterHandler();
        attachVoteModalHandler();
        attachResultsModalHandler();
      });
  }

  function attachElectionFilterHandler() {
    const container = document.querySelector('.elecsec-container');
    if (!container) return;
    container.addEventListener('click', function (e) {
      if (e.target.classList.contains('filter-btn')) {
        e.preventDefault();
        const status = e.target.dataset.status.toLowerCase();
        container.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        e.target.classList.add('active');
        container.querySelectorAll('.election-card').forEach(card => {
          const match = card.className.match(/\b(active|upcoming|completed)\b/i);
          const cardStatus = match ? match[0].toLowerCase() : '';
          card.style.display = (status === 'all' || cardStatus === status) ? 'block' : 'none';
        });
      }
    });
  }

  function attachVoteModalHandler() {
    const content = document.querySelector('.content');
    if (!content) return;
    content.addEventListener('click', function (e) {
      const btn = e.target.closest('.vote-now-btn');
      if (!btn) return;
      e.preventDefault();
      const electionId = btn.getAttribute('data-election-id');
      if (!electionId) return;
      fetch('vote.php?ajax=1&election_id=' + encodeURIComponent(electionId))
        .then(res => res.text())
        .then(html => {
          document.getElementById('voteFormContainer').innerHTML = html;
          document.getElementById('voteModal').style.display = 'flex';
          const closeBtn = document.getElementById('closeVoteModal');
          if (closeBtn) {
            closeBtn.onclick = function () {
              document.getElementById('voteModal').style.display = 'none';
              document.getElementById('voteFormContainer').innerHTML = '';
            };
          }
          attachVoteFormHandler();
        });
    });
  }

  function attachVoteFormHandler() {
    const voteForm = document.querySelector('#voteFormContainer form');
    if (!voteForm) return;
    voteForm.onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(voteForm);
      fetch('vote.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(html => {
        document.getElementById('voteFormContainer').innerHTML = html;
        attachVoteFormHandler();
      });
    };
  }

  function attachResultsModalHandler() {
    const content = document.querySelector('.content');
    if (!content) return;
    content.addEventListener('click', function (e) {
      const btn = e.target.closest('.view-results-btn');
      if (!btn) return;
      e.preventDefault();
      const electionId = btn.getAttribute('data-election-id');
      if (!electionId) return;
      fetch('results.php?election_id=' + encodeURIComponent(electionId))
        .then(res => res.text())
        .then(html => {
          document.getElementById('resultsFormContainer').innerHTML = html;
          document.getElementById('resultsModal').style.display = 'flex';
          const closeBtn = document.getElementById('closeResultsModal');
          if (closeBtn) {
            closeBtn.onclick = function () {
              document.getElementById('resultsModal').style.display = 'none';
              document.getElementById('resultsFormContainer').innerHTML = '';
            };
          }
        });
    });
  }

  function attachSupportFormHandler() {
    const form = document.getElementById('supportForm');
    if (!form) return;
    form.onsubmit = function (e) {
      e.preventDefault();
      const formData = new FormData(form);
      fetch('helsup.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newForm = doc.getElementById('supportForm');
        const newMsg = doc.getElementById('supportMsg');
        if (newForm && newMsg) {
          form.replaceWith(newForm);
          document.getElementById('supportMsg').replaceWith(newMsg);
          attachSupportFormHandler();
        } else {
          contentDiv.innerHTML = html;
          attachSupportFormHandler();
        }
      });
    };
  }

  // Mark notification as read
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.mark-read-btn');
    if (btn) {
      e.preventDefault();
      const notifId = btn.getAttribute('data-id');
      fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(notifId)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const notifItem = btn.closest('.notification-item');
          if (notifItem) {
            notifItem.classList.add('read');
            btn.remove();
          }
        } else {
          alert('Failed to mark as read.');
        }
      });
    }
  });
  });
</script>
   
    </body>
</html>


