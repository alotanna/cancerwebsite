<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/login.php");
    exit();
}

// Check if resource_id is provided
if (!isset($_GET['resource_id']) || !is_numeric($_GET['resource_id'])) {
    $_SESSION['message'] = "Invalid resource ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

$resource_id = intval($_GET['resource_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user has permission to delete the resource
try {
    // First, check if the resource exists and belongs to the user or can be deleted by admin
    $check_resource_query = "
        SELECT user_id, picture 
        FROM cancer_resources
        WHERE resource_id = ?
    ";
    $check_stmt = $conn->prepare($check_resource_query);
    $check_stmt->bind_param("i", $resource_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("Resource not found");
    }
    
    $resource_details = $check_result->fetch_assoc();
    
    // Verify deletion permissions
    if ($user_role !== 'admin' && $resource_details['user_id'] != $user_id) {
        throw new Exception("You are not authorized to delete this resource");
    }

    // Confirmation check before deletion
    if (!isset($_POST['confirm_delete']) || strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE') {
        // Render a confirmation page
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Resource Deletion</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Confirm Resource Deletion</h2>
            </div>
            <?php 
            // Display error message if deletion confirmation was incorrect
            if (isset($_POST['confirm_delete']) && strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE'): ?>
            <div class="error-message">
                <p>❌ Incorrect confirmation. You must type exactly "DELETE" (case-insensitive).</p>
            </div>
            <?php endif; ?>
            <div class="resource-deletion-container">
                <div class="warning-card">
                    <h3 class="text-danger">⚠️ Permanent Deletion Warning</h3>
                    <p>You are about to permanently delete this resource. This action CANNOT be undone.</p>
                    <p>To confirm, type <strong>DELETE</strong> in the box below:</p>
                    <form method="POST" action="?resource_id=<?php echo $resource_id; ?>">
                        <div class="form-group">
                            <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm">
                        </div>
                        <div class="form-actions">
                            <a href="../view/resources.php" class="cancel-btn">Cancel</a>
                            <button type="submit" class="delete-btn">Confirm Deletion</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
        <?php
        exit();
    }

    // Begin transaction for safe deletion
    $conn->begin_transaction();

    // Delete the resource
    $delete_resource_sql = "DELETE FROM cancer_resources WHERE resource_id = ?";
    $resource_stmt = $conn->prepare($delete_resource_sql);
    $resource_stmt->bind_param("i", $resource_id);
    
    if ($resource_stmt->execute()) {
        // If deletion is successful, also delete the associated picture file if it exists
        if (!empty($resource_details['picture']) && file_exists($resource_details['picture'])) {
            unlink($resource_details['picture']);
        }
        
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['message'] = "Resource deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        throw new Exception("Failed to delete resource");
    }
    
    $resource_stmt->close();

} catch (Exception $e) {
    // Rollback transaction in case of error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to resources page
header("Location: ../view/resources.php");
exit();
?>