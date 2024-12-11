<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/login.php");
    exit();
}

// Check if caregiver_id is provided
if (!isset($_GET['caregiver_id']) || !is_numeric($_GET['caregiver_id'])) {
    $_SESSION['message'] = "Invalid caregiver ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/caregivers.php");
    exit();
}

$caregiver_id = intval($_GET['caregiver_id']);

// Confirmation check before deletion
if (!isset($_POST['confirm_delete']) || strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE') {
    // Render a confirmation page with potential error message
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Caregiver Deletion</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Confirm Caregiver Deletion</h2>
            </div>
            <?php 
            // Display error message if deletion confirmation was incorrect
            if (isset($_POST['confirm_delete']) && strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE'): ?>
            <div class="error-message">
                <p>❌ Incorrect confirmation. You must type exactly "DELETE" (case-insensitive).</p>
            </div>
            <?php endif; ?>
            <div class="caregiver-details-container">
                <div class="warning-card">
                    <h3 class="text-danger">⚠️ Permanent Deletion Warning</h3>
                    <p>You are about to permanently delete this caregiver's record. This action CANNOT be undone.</p>
                    <p>To confirm, type <strong>DELETE</strong> in the box below:</p>
                    <form method="POST" action="?caregiver_id=<?php echo $caregiver_id; ?>">
                        <div class="form-group">
                            <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm">
                        </div>
                        <div class="form-actions">
                            <a href="../view/caregivers.php" class="cancel-btn">Cancel</a>
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

// If we're here, deletion is confirmed
try {
    // Begin transaction for safe deletion
    $conn->begin_transaction();

    // First, check if caregiver already exists
    $check_caregiver_query = "SELECT * FROM cancer_caregivers WHERE caregiver_id = ?";
    $check_stmt = $conn->prepare($check_caregiver_query);
    $check_stmt->bind_param("i", $caregiver_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Caregiver has already been deleted
        $_SESSION['message'] = "Caregiver record has already been deleted";
        $_SESSION['message_type'] = "warning";
        header("Location: ../view/caregivers.php");
        exit();
    }

    // Retrieve the user_id associated with this caregiver
    $user_query = "SELECT user_id FROM cancer_caregivers WHERE caregiver_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $caregiver_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception("Caregiver not found");
    }
    
    $user_row = $user_result->fetch_assoc();
    $user_id = $user_row['user_id'];
    $user_stmt->close();

    // Delete the caregiver record (this will cascade to other related tables)
    $delete_caregiver_sql = "DELETE FROM cancer_caregivers WHERE caregiver_id = ?";
    $caregiver_stmt = $conn->prepare($delete_caregiver_sql);
    $caregiver_stmt->bind_param("i", $caregiver_id);
    
    if ($caregiver_stmt->execute()) {
        // Delete the associated user record (which will further cascade)
        $delete_user_sql = "DELETE FROM cancer_users WHERE user_id = ?";
        $user_delete_stmt = $conn->prepare($delete_user_sql);
        $user_delete_stmt->bind_param("i", $user_id);
        
        if ($user_delete_stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            
            $_SESSION['message'] = "Caregiver and associated user record deleted successfully";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception("Failed to delete user record");
        }
        
        $user_delete_stmt->close();
    } else {
        throw new Exception("Failed to delete caregiver record");
    }
    
    $caregiver_stmt->close();
} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to caregivers page
header("Location: ../view/caregivers.php");
exit();
?>