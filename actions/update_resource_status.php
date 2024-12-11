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

// Check if resource_id and status are provided in the URL
if (!isset($_GET['resource_id']) || !isset($_GET['status'])) {
    // Redirect back to resources page if parameters are missing
    header("Location: ../view/resources.php");
    exit();
}

// Sanitize inputs
$resource_id = $conn->real_escape_string($_GET['resource_id']);
$status = $conn->real_escape_string($_GET['status']);

// Validate status (only allow specific statuses)
$allowed_statuses = ['approved', 'rejected'];
if (!in_array($status, $allowed_statuses)) {
    // Redirect back to resources page if invalid status
    header("Location: ../view/resources.php");
    exit();
}

// Prepare and execute the update query
$update_sql = "UPDATE cancer_resources SET status = '$status' WHERE resource_id = '$resource_id'";

if ($conn->query($update_sql) === TRUE) {
    // Set success message in session
    $_SESSION['message'] = "Resource status updated successfully.";
    $_SESSION['message_type'] = "success";
} else {
    // Set error message in session
    $_SESSION['message'] = "Error updating resource status: " . $conn->error;
    $_SESSION['message_type'] = "error";
}

// Close database connection
$conn->close();

// Redirect back to resources page
header("Location: ../view/resources.php");
exit();
?>