<?php
// Start the session to access session variables
session_start();

// Include database configuration
include '../db/config.php';

// Session and role validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login if not an admin
    header("Location: ../view/login.php");
    exit();
}

// Check if story_id and status are provided in the URL
if (!isset($_GET['story_id']) || !isset($_GET['status'])) {
    // Redirect back to stories page if parameters are missing
    header("Location: ../view/stories.php");
    exit();
}

// Sanitize inputs
$story_id = $conn->real_escape_string($_GET['story_id']);
$status = $conn->real_escape_string($_GET['status']);

// Validate status (only allow specific statuses)
$allowed_statuses = ['approved', 'rejected'];
if (!in_array($status, $allowed_statuses)) {
    // Redirect back to stories page if invalid status
    header("Location: ../view/stories.php");
    exit();
}

// Prepare and execute the update query
$update_sql = "UPDATE cancer_stories SET status = '$status' WHERE story_id = '$story_id'";

if ($conn->query($update_sql) === TRUE) {
    // Set success message in session
    $_SESSION['message'] = "Story status updated successfully.";
    $_SESSION['message_type'] = "success";
    
} else {
    // Set error message in session
    $_SESSION['message'] = "Error updating story status: " . $conn->error;
    $_SESSION['message_type'] = "error";
}

// Close database connection
$conn->close();

// Redirect back to stories page
header("Location: ../view/stories.php");
exit();
?>