<?php
// update_election_status.php

// Database connection
$conn = new mysqli('localhost', 'root', '', 'evoteplatform');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update elections whose end_date has passed and are not ended
$sql = "UPDATE elections SET status = 'completed' WHERE end_date <= NOW() AND status != 'ended'";
if ($conn->query($sql) === TRUE) {
    echo "Election statuses updated successfully.";
} else {
    echo "Error updating statuses: " . $conn->error;
}

$conn->close();
?>