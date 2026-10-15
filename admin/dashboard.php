<?php 
session_start();

// check if user is logged in
if(!isset($_SESSION['username']) || $_SESSION['role'] !=='admin') {
    header('Location: ../login/login.php');
    exit();
}
 //database connection
include('../connection.php');

//always keep admin_id in session if not set
if(!isset($_SESSION['admin_id'])) {
    if(!$conn->connect_error){
        $stmt = $conn->prepare('SELECT id FROM admins WHERE username = ?');
        if($stmt) {
            $stmt->bind_param('s',$_SESSION['username']);
            $stmt->execute();
            $stmt->bind_result($fetched_id);
            if($stmt->fetch) {
                $_SESSION['admin_id'] = $fetched_id;
            }
            $stmt->close;
        }
    }   
}

$admin_id = $_SESSION['admin_id'];

//fetch profile
$profile_image = '../images/candidate-default.jpg';
if(!$conn->connect_error){
    $stmt=$conn->prepare('SELECT profile_image FROM admins WHERE id=?');
    if($stmt){
        $stmt->bind_param('i',$admin_id);
        $stmt->execute();
        $stmt->bind_result($img);
        if($stmt->fetch() && $img) {
            $profile_image = $img;
        }
        $stmt->close();
    }
}

//registered voters
$registeredVoters=0;
$result= mysqli_query($conn, "select count(*) as total from register");
if($result && $row=mysqli_fetch_assoc($result)){
    $registeredVoters=(int)$row['total'];
}

//active elections
$activeElections=0;
$result=mysqli_query($conn, "select count(*) as total from elections where status='active'");
if($result && $row=mysqli_fetch_assoc($result)){
    $activeElections=(int)$row['total'];
}

//completed election
$completedelections=0;
$result=mysqli_query($conn, "select count(*) as total from elections where status = 'completed'");
if($result && $row=mysqli_fetch_assoc($result)){
    $completedelections=(int)$row['total'];
}

//upcoming elections
$upcomingElections=0;
$result=mysqli_query($conn, "select count(*) as total from elections WHERE status='upcoming'");
if($result && $row=mysqli_fetch_assoc($result)){
    $upcomingElections=(int)$row['total'];
}

//fetch messages
$messages=[];
$result=mysqli_query($conn, "select st.id, st.message, st.created_at, r.username as sender_name from support_tickets st join register r on st.user_id = r.id order by st.created_at desc limit 3");
if($result){
    while ($row=mysqli_fetch_assoc($result)) {
        $messages[]=$row;
    }
}

//fetch upcoming events
$UpcomingEvents=[];
$result=mysqli_query($conn, "select title,start_date from elections where start_date>=CURDATE() order by start_date desc limit 3");
if($result){
    while($row=mysqli_fetch_assoc($result)){
        $UpcomingEvents[]=$row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="elemag.css">
    <link rel="stylesheet" href="pollmagt.css">
    <link rel="stylesheet" href="result.css">
    <link rel="stylesheet" href="tech.css">
    <link rel="stylesheet" href="edit_user.css">
</head>
<body>
    <div class="side-navbar">
        <div class="brandname">
            <h1 class="name">E-Vote</h1>
        </div>

        <ul class = "side-list">
            <li><a href="#" data-page="dashboard.php"><i class = "fa fa-dashboard"></i><span>Dashboard</span></a></li>
            <li><a href="#" data-page="usermagt.php"><i class = "fa fa-users"></i><span>User Management</span></a></li>
            <li><a href="#" data-page="electionmagt.php"><i class = "fa fa-bullhorn"></i><span>Election Management</span></a></li>
            <li><a href="#" data-page="pollmagt.php"><i class = "fa fa-list-alt"></i><span>Poll Management</span></a></li>
            <li><a href="#" data-page="resultmagt.php"><i class = "fa fa-bar-chart"></i><span>Result Management</span></a></li>
            <li><a href="#" data-page="tech.php"><i class = "fa fa-wrench"></i><span>Technical Support</span></a></li>
            <li><a href="#" data-page="signout.php"><i class = "fa fa-sign-out"></i><span>Sign Out</span></a></li>
        </ul>
    </div>

    <div class="container">
        <div id="globalMsg"></div>
        <div class="header">
            <div class="user-header-info">
                <span class = "user-welcome">Welcome, <strong><?=htmlspecialchars($_SESSION['username'])?></strong></span>
                <span class="user-role">Admin</span>
                <img src="<?=htmlspecialchars($profile_image)?>" alt="Profile" class="header-profile-img" >
            </div>
        </div>

        <div class="content">
            <div class="cards">
                <div class="card">
                    <div class="icon-case">
                        <i class="fa fa-users" aria-hidden="true"></i>
                    </div>
                    <div class="box">
                        <h1><?=htmlspecialchars($registeredVoters??0)?></h1>
                        <h3>Registered Voters</h3>
                    </div>
                </div>
                <div class="card">
                    <div class="icon-case">
                        <i class="fa fa-balance-scale" aria-hidden="true"></i>
                    </div>
                    <div class="box">
                        <h1><?=htmlspecialchars($activeElections??0)?></h1>
                        <h3>Active Elections</h3>
                    </div>
                </div>
                <div class="card">
                    <div class="icon-case">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </div>
                    <div class="box">
                        <h1><?=htmlspecialchars($upcomingElections??0)?></h1>
                        <h3>Upcoming Elections</h3>
                    </div>
                </div>
                <div class="card">
                    <div class="icon-case">
                        <i class="fa fa-flag-checkered" aria-hidden="true"></i>
                    </div>
                    <div class="box">
                        <h1><?=htmlspecialchars($completedelections??0)?></h1>
                        <h3>Completed Elections</h3>
                    </div>
                </div>
            </div>

            <div class="content-2">
                <div class="messages">
                    <h2 class="cont">Messages</h2>
                    <hr class="rule">
                    <?php if(!empty($messages)):?>
                        <ul class="message-list">
                            <?php foreach($messages as $message):
                            $created_at = date('M d,Y H:i', strtotime($message['created_at']));
                            $messagePreview = strlen($message['message'])>100? substr($message['message'],0,97).'...': $message['message'];?>
                            <li class="message-item">
                                <div class="message-header">
                                    <span class="sender-name"><?=htmlspecialchars($message['sender_name']??'Unknown Sender')?></span>
                                    <time datetime="<?=htmlspecialchars($message['created_at'])?>" class="message-time" title="<?=$created_at?>"><?=$created_at?></time> 
                                </div>
                                <p class="message-preview"><?=htmlspecialchars($messagePreview)?></p>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                            <p class="no-messages">No recent messages available</p>
                            <?php endif;?>
                </div>
                <div class="upcoming">
                    <h2 class="cont">Upcoming Events</h2>
                    <hr class="rule">
                    <ul class="event-list">
                        <?php if(!empty($UpcomingEvents)):?>
                            <?php foreach ($UpcomingEvents as $event):?>
                                <li class="event-item">
                                    <div class="event-title"><?=htmlspecialchars($event['title'])?></div>
                                    <div class="event-date"><?=htmlspecialchars(date("F j, Y", strtotime($event['start_date'])))?></div>
                                </li>
                                <?php endforeach;?>
                                <?php else:?>
                                    <li class="no-events">No upcoming events found</li>
                                    <?php endif;?> 
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dashboard Ajax Setup

document.addEventListener("DOMContentLoaded", function() {
    const links = document.querySelectorAll(".side-list a");
    const contentDiv = document.querySelector(".content");

    // Helper to set active class
    function setActiveLink(page) {
        links.forEach(l => {
            if (l.getAttribute("data-page") === page) {
                l.classList.add('active');
            } else {
                l.classList.remove('active');
            }
        });
    }

    // Load initial dashboard content and set active
    setActiveLink('dashboard.php');
    loadContent('dashboard.php');

    links.forEach(link => {
        link.addEventListener("click", function(event) {
            event.preventDefault();
            const page = this.getAttribute("data-page");
            setActiveLink(page);

            if (page === 'signout.php') {
                window.location.href = '../signout.php';
                return;
            }
            loadContent(page);
        });
    });

    function setupTechTicketForms() {
    document.querySelectorAll('.update-ticket-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('tech.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                // Find the .content area in the current dashboard page
                const contentDiv = document.querySelector('.content');
                if (!contentDiv) return;

                // Replace the message
                const msg = doc.querySelector('#ticket-message');
                if (msg) {
                    let msgBox = contentDiv.querySelector('#ticket-message');
                    if (msgBox) {
                        msgBox.replaceWith(msg);
                    } else {
                        contentDiv.prepend(msg);
                    }
                }
                // Replace the tickets table
                const newTable = doc.querySelector('#tickets-table');
                if (newTable) {
                    const oldTable = contentDiv.querySelector('#tickets-table');
                    if (oldTable) {
                        oldTable.replaceWith(newTable);
                    }
                }
                // Re-attach handlers for new forms in the new table
                setupTechTicketForms();
            });
        });
    });
}


    function loadContent(page) {
        fetch(page)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                // Extract and show messages
                const msg = doc.querySelector('.success, .error');
                if (msg) {
                    document.getElementById('globalMsg').innerHTML = msg.outerHTML;
                } else {
                    document.getElementById('globalMsg').innerHTML = '';
                }
                contentDiv.innerHTML = doc.body.innerHTML;
                // After loading, re-initialize modal logic if present
                setupCreateElectionModalIfPresent();
                setupAddUserModalIfPresent();
                setupEditUserModalIfPresent();
                setupElectionCollapsibles();
                setupAssignCandidateForms();
                setupDeleteCandidateForms();
                setupEditElectionModalIfPresent();

                if (page === 'tech.php') {
                    setupTechTicketForms();
                }
                if (page === 'resultmagt.php') {
                    setupElectionDropdown();
                }
            })
            .catch(error => {
                contentDiv.innerHTML = `<p style="color:red;">Error loading page: ${error.message}</p>`;
            });
    }

    function setupCreateElectionModalIfPresent() {
        const btn = document.getElementById('createElectionBtn');
        const modal = document.getElementById('createElectionModal');
        const closeBtn = document.getElementById('closeCreateElection');
        if (!btn || !modal || !closeBtn) return;
        btn.onclick = function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        };
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
        window._createElectionModalWindowListener = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
        window.addEventListener('click', window._createElectionModalWindowListener);
    }

    function setupAddUserModalIfPresent() {
        const btn = document.getElementById('adduserbtn');
        const modal = document.getElementById('addUserModal');
        const closeBtn = document.getElementById('closeAddUser');
        if (!btn || !modal || !closeBtn) return;
        btn.onclick = function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        };
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
        window._addUserModalWindowListener = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
        window.addEventListener('click', window._addUserModalWindowListener);
    }

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
                        modal.style.display = 'flex';
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

    function setupElectionCollapsibles() {
        document.querySelectorAll('.collapse-toggle').forEach(btn => {
            btn.onclick = function() {
                const content = btn.nextElementSibling;
                const arrow = btn.querySelector('.toggle-arrow');
                if (content.style.display === 'none' || content.style.display === '') {
                    content.style.display = 'block';
                    arrow.innerHTML = '▼';
                } else {
                    content.style.display = 'none';
                    arrow.innerHTML = '▶';
                }
            };
        });
        const first = document.querySelector('.election-content');
        if (first) {
            first.style.display = 'block';
            const btn = first.previousElementSibling.querySelector('.toggle-arrow');
            if (btn) btn.innerHTML = '▼';
        }
    }

    function setupEditElectionModalIfPresent() {
        document.querySelectorAll('.edit-election-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var modal = document.getElementById('editElectionModal');
                var container = document.getElementById('editElectionFormContainer');
                fetch('edit_election.php?id=' + encodeURIComponent(id) + '&ajax=1')
                    .then(res => res.text())
                    .then(html => {
                        container.innerHTML = html;
                        modal.style.display = 'flex';
                        setupEditElectionForm();
                    });
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
    }

    function setupEditElectionForm() {
        var form = document.querySelector('#editElectionFormContainer form');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);
            fetch('edit_election.php?id=' + encodeURIComponent(form.elements['id'].value) + '&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('editElectionModal').style.display = 'none';
                document.getElementById('editElectionFormContainer').innerHTML = '';
                if (typeof loadContent === 'function') {
                    loadContent('electionmagt.php');
                } else {
                    location.reload();
                }
            });
        });
    }

    function setupAssignCandidateForms() {
        document.querySelectorAll('.assign-candidate.poll-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);

                fetch('pollmagt.php', {
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
                        setupAssignCandidateForms();
                        setupDeleteCandidateForms();
                    }
                })
                .catch(err => {
                    alert('Submission failed: ' + err);
                });
            });
        });
    }
    function setupElectionDeleteHandler() {
    let wrapper = document.querySelector('.table-wrapper');
    if (!wrapper) return;
    wrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-election-btn')) {
            e.preventDefault();
            var id = e.target.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this election?')) {
                updateElectionTableAfterDelete(id);
            }
        }
    });
}

function updateElectionTableAfterDelete(id) {
  fetch('electionmagt.php?delete_id=' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(res => res.text())
    .then(rows => {
      var tbody = document.querySelector('.table-wrapper tbody');
      if (tbody) {
        tbody.innerHTML = rows;
      }
    });
}

// You may call it after dashboard content loads or after any AJAX-load of election table:
setupElectionDeleteHandler();


    function setupDeleteCandidateForms() {
        document.querySelectorAll('.content-pollmagt').forEach(content => {
            content.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.matches('.candidates form')) {
                    e.preventDefault();
                    if (!confirm('Delete this candidate?')) return;
                    const formData = new FormData(form);

                    fetch('pollmagt.php', {
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
                            setupAssignCandidateForms();
                            setupDeleteCandidateForms();
                        }
                    })
                    .catch(err => {
                        alert('Delete failed: ' + err);
                    });
                }
            });
        });
    }

    function setupElectionDropdown() {
    const select = document.getElementById('election_id');
    if (select) {
        select.onchange = function() {
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
        };
    }
}

});



    </script>
    
</body>
</html>