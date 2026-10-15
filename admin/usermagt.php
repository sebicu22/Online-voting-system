<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not authenticated
    header('Location: ../login/login.php');
    exit();
}

// Database connection parameters
$host = 'localhost';
$dbname = 'evoteplatform';
$username = 'root';
$password = '';

// Connect to database using MySQLi
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle delete request with CSRF protection
if (isset($_GET['delete_id'], $_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: usermagt.php"); // Adjust if your filename is different
    exit;
}

// Fetch all users
$sql = "SELECT id, full_name, email, image, status, created_at FROM register ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Management</title>
<link rel="stylesheet" href="userm.css">
<link rel="stylesheet" href="edit_user.css">
</head>
<body>

<div class="content-user">
    <h1>User Management</h1>
    <button id="adduserbtn" class="btn add">+ Add New User</button>

    <?php if (!empty($successMsg)): ?>
      <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Profile Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Registered On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td>
                        <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Profile Image" style="width:50px; height:50px; object-fit:cover; border-radius:50%;" />
                        <?php else: ?>
                            <img src="images/default.png" alt="No Image" style="width:50px; height:50px; object-fit:cover; border-radius:50%;" />
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                    <td><?= htmlspecialchars(date("Y-m-d H:i", strtotime($row['created_at']))) ?></td>
                    <td>
                        <div class="action-button">
                            <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn edit edit-user-btn" data-id="<?= $row['id'] ?>">Edit</a>
                            <a href="usermagt.php?delete_id=<?= $row['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </div>
                        
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Popup for Create Election -->
    <div id="addUserModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeAddUser">&times;</span>
        <iframe src="add_user.php" frameborder="0" style="width:100%;height:80vh;"></iframe>
      </div>
    </div>

    <!-- Modal Popup for Edit User -->
    <div id="editUserModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeEditUser">&times;</span>
        <div id="editUserFormContainer"></div>
      </div>
    </div>

    <script>
function setupEditUserModalIfPresent() {
    document.querySelectorAll('.edit-user-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var modal = document.getElementById('editUserModal');
            var container = document.getElementById('editUserFormContainer');
            fetch('edit_user.php?id=' + encodeURIComponent(id) + '&ajax=1')
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    modal.style.display = 'block';
                    setupEditUserForm();
                });
        });
    });

    var closeEdit = document.getElementById('closeEditUser');
    var editModal = document.getElementById('editUserModal');
    if (closeEdit && editModal) {
        closeEdit.onclick = function() {
            editModal.style.display = 'none';
            document.getElementById('editUserFormContainer').innerHTML = '';
        };
        window.addEventListener('click', function(event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
                document.getElementById('editUserFormContainer').innerHTML = '';
            }
        });
    }
}

function setupEditUserForm() {
    var form = document.querySelector('#editUserFormContainer form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(form);
        fetch('edit_user.php?id=' + encodeURIComponent(form.elements['id'].value) + '&ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('editUserModal').style.display = 'none';
            document.getElementById('editUserFormContainer').innerHTML = '';
            if (typeof loadContent === 'function') {
                loadContent('usermagt.php');
            } else {
                location.reload();
            }
        });
    });
}

function setupAddUserModalIfPresent() {
    var addBtn = document.getElementById('adduserbtn');
    var addModal = document.getElementById('addUserModal');
    var closeAdd = document.getElementById('closeAddUser');
    if (addBtn && addModal && closeAdd) {
        addBtn.onclick = function(e) {
            e.preventDefault();
            addModal.style.display = 'block';
        };
        closeAdd.onclick = function() {
            addModal.style.display = 'none';
            // Optionally reset the iframe to clear the form:
            // addModal.querySelector('iframe').src = addModal.querySelector('iframe').src;
        };
        window.addEventListener('click', function(event) {
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        });
    }
}

// Initialize modals setup
setupEditUserModalIfPresent();
setupAddUserModalIfPresent();
    </script>

</body>
</html>

<?php
$conn->close();
?>
