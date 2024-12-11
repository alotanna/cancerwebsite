<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    $_SESSION['message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/login.php");
    exit();
}

// Check if appointment_id is provided
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    $_SESSION['message'] = "Invalid appointment ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/appointments.php");
    exit();
}

$appointment_id = intval($_GET['appointment_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Confirmation check before deletion
if (!isset($_POST['confirm_delete']) || strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE') {
    // Render a confirmation page with potential error message
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Appointment Deletion</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Confirm Appointment Deletion</h2>
            </div>
            <?php 
            // Display error message if deletion confirmation was incorrect
            if (isset($_POST['confirm_delete']) && strtoupper(trim($_POST['confirm_delete'])) !== 'DELETE'): ?>
            <div class="error-message">
                <p>❌ Incorrect confirmation. You must type exactly "DELETE" (case-insensitive).</p>
            </div>
            <?php endif; ?>
            <div class="appointment-details-container">
                <div class="warning-card">
                    <h3 class="text-danger">⚠️ Permanent Deletion Warning</h3>
                    <p>You are about to permanently delete this appointment. This action CANNOT be undone.</p>
                    <p>To confirm, type <strong>DELETE</strong> in the box below:</p>
                    <form method="POST" action="?appointment_id=<?php echo $appointment_id; ?>">
                        <div class="form-group">
                            <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm">
                        </div>
                        <div class="form-actions">
                            <a href="../view/appointments.php" class="cancel-btn">Cancel</a>
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

try {
    // Begin transaction for safe deletion
    $conn->begin_transaction();

    // First, verify the appointment exists and the user has permission to delete
    $permission_check_query = "";
    
    switch ($user_role) {
        case 'admin':
            // Admin can delete any appointment
            $permission_check_query = "SELECT appointment_id FROM cancer_appointments WHERE appointment_id = ?";
            break;
        
        case 'patient':
            // Patient can only delete their own appointments that are not completed
            $permission_check_query = "SELECT a.appointment_id 
                FROM cancer_appointments a
                JOIN cancer_patients p ON a.patient_id = p.patient_id
                JOIN cancer_users u ON p.user_id = u.user_id
                WHERE a.appointment_id = ? AND u.user_id = ? AND a.status != 'completed'";
            break;
        
        case 'caregiver':
            // Caregiver can delete appointments they are assigned to
            $permission_check_query = "SELECT a.appointment_id 
                FROM cancer_appointments a
                JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
                JOIN cancer_users u ON c.user_id = u.user_id
                WHERE a.appointment_id = ? AND u.user_id = ?";
            break;
        
        default:
            throw new Exception("Unauthorized role");
    }

    $permission_stmt = $conn->prepare($permission_check_query);
    
    if ($user_role === 'admin') {
        $permission_stmt->bind_param("i", $appointment_id);
    } else {
        $permission_stmt->bind_param("ii", $appointment_id, $user_id);
    }
    
    $permission_stmt->execute();
    $permission_result = $permission_stmt->get_result();
    
    if ($permission_result->num_rows === 0) {
        throw new Exception("You do not have permission to delete this appointment");
    }

    // Delete the appointment
    $delete_appointment_sql = "DELETE FROM cancer_appointments WHERE appointment_id = ?";
    $delete_stmt = $conn->prepare($delete_appointment_sql);
    $delete_stmt->bind_param("i", $appointment_id);
    
    if ($delete_stmt->execute()) {
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['message'] = "Appointment deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        throw new Exception("Failed to delete appointment");
    }
    
    $delete_stmt->close();

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to appointments page
header("Location: ../view/appointments.php");
exit();
?>