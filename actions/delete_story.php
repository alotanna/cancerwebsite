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

// Check if story_id is provided
if (!isset($_GET['story_id']) || !is_numeric($_GET['story_id'])) {
    $_SESSION['message'] = "Invalid story ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}

$story_id = intval($_GET['story_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user has permission to delete the story
try {
    // First, check if the story exists and belongs to the user (for patients) or can be deleted by admin
    $check_story_query = "
        SELECT s.patient_id, p.user_id 
        FROM cancer_stories s
        JOIN cancer_patients p ON s.patient_id = p.patient_id
        WHERE s.story_id = ?
    ";
    $check_stmt = $conn->prepare($check_story_query);
    $check_stmt->bind_param("i", $story_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("Story not found");
    }
    
    $story_details = $check_result->fetch_assoc();
    
    // Verify deletion permissions
    if ($user_role !== 'admin' && $story_details['user_id'] != $user_id) {
        throw new Exception("You are not authorized to delete this story");
    }

    // Confirmation check before deletion
    if (!isset($_POST['confirm_delete']) || strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE') {
        // Render a confirmation page
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Story Deletion</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Confirm Story Deletion</h2>
            </div>
            <?php 
            // Display error message if deletion confirmation was incorrect
            if (isset($_POST['confirm_delete']) && strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE'): ?>
            <div class="error-message">
                <p>❌ Incorrect confirmation. You must type exactly "DELETE" (case-insensitive).</p>
            </div>
            <?php endif; ?>
            <div class="story-deletion-container">
                <div class="warning-card">
                    <h3 class="text-danger">⚠️ Permanent Deletion Warning</h3>
                    <p>You are about to permanently delete this story. This action CANNOT be undone.</p>
                    <p>To confirm, type <strong>DELETE</strong> in the box below:</p>
                    <form method="POST" action="?story_id=<?php echo $story_id; ?>">
                        <div class="form-group">
                            <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm">
                        </div>
                        <div class="form-actions">
                            <a href="../view/stories.php" class="cancel-btn">Cancel</a>
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

    // Delete the story
    $delete_story_sql = "DELETE FROM cancer_stories WHERE story_id = ?";
    $story_stmt = $conn->prepare($delete_story_sql);
    $story_stmt->bind_param("i", $story_id);
    
    if ($story_stmt->execute()) {
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['message'] = "Story deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        throw new Exception("Failed to delete story");
    }
    
    $story_stmt->close();

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to stories page
header("Location: ../view/stories.php");
exit();
?>