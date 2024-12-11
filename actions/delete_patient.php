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

// Check if patient_id is provided
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    $_SESSION['message'] = "Invalid patient ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/patients.php");
    exit();
}

$patient_id = intval($_GET['patient_id']);

// Confirmation check before deletion
if (!isset($_POST['confirm_delete']) || strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE') {
    // Render a confirmation page with potential error message
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Patient Deletion</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Confirm Patient Deletion</h2>
            </div>
            <?php 
            // Display error message if deletion confirmation was incorrect
            if (isset($_POST['confirm_delete']) && strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE'): ?>
            <div class="error-message">
                <p>❌ Incorrect confirmation. You must type exactly "DELETE" (case-insensitive).</p>
            </div>
            <?php endif; ?>
            <div class="patient-details-container">
                <div class="warning-card">
                    <h3 class="text-danger">⚠️ Permanent Deletion Warning</h3>
                    <p>You are about to permanently delete this patient's record. This action CANNOT be undone.</p>
                    <p>To confirm, type <strong>DELETE</strong> in the box below:</p>
                    <form method="POST" action="?patient_id=<?php echo $patient_id; ?>">
                        <div class="form-group">
                            <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm">
                        </div>
                        <div class="form-actions">
                            <a href="../view/patients.php" class="cancel-btn">Cancel</a>
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

    // First, check if patient already exists
    $check_patient_query = "SELECT * FROM cancer_patients WHERE patient_id = ?";
    $check_stmt = $conn->prepare($check_patient_query);
    $check_stmt->bind_param("i", $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Patient has already been deleted
        $_SESSION['message'] = "Patient record has already been deleted";
        $_SESSION['message_type'] = "warning";
        header("Location: ../view/patients.php");
        exit();
    }

    // Retrieve the user_id associated with this patient
    $user_query = "SELECT user_id FROM cancer_patients WHERE patient_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $patient_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception("Patient not found");
    }
    
    $user_row = $user_result->fetch_assoc();
    $user_id = $user_row['user_id'];
    $user_stmt->close();

    // Delete the patient record (this will cascade to other related tables)
    $delete_patient_sql = "DELETE FROM cancer_patients WHERE patient_id = ?";
    $patient_stmt = $conn->prepare($delete_patient_sql);
    $patient_stmt->bind_param("i", $patient_id);
    
    if ($patient_stmt->execute()) {
        // Delete the associated user record (which will further cascade)
        $delete_user_sql = "DELETE FROM cancer_users WHERE user_id = ?";
        $user_delete_stmt = $conn->prepare($delete_user_sql);
        $user_delete_stmt->bind_param("i", $user_id);
        
        if ($user_delete_stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            
            $_SESSION['message'] = "Patient and associated user record deleted successfully";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception("Failed to delete user record");
        }
        
        $user_delete_stmt->close();
    } else {
        throw new Exception("Failed to delete patient record");
    }
    
    $patient_stmt->close();
} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to patients page
header("Location: ../view/patients.php");
exit();
?>