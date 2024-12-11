<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine user role and set patient_id or validate patient selection
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];

    // Validate and sanitize inputs
    $appointment_id = intval($_POST['appointment_id']);
    $patient_id = null;
    $caregiver_id = null;
    $appointment_date = $conn->real_escape_string(trim($_POST['appointment_date']));
    $appointment_time = $conn->real_escape_string(trim($_POST['appointment_time']));
    $notes = !empty($_POST['notes']) ? $conn->real_escape_string(trim($_POST['notes'])) : null;
    $status = null;

    // Role-specific validation and handling
    try {
        // Begin transaction
        $conn->begin_transaction();

        // First, verify the appointment exists and the user has permission to edit
        $check_stmt = $conn->prepare("
            SELECT 
                a.patient_id, 
                a.caregiver_id, 
                a.status,
                p.user_id AS patient_user_id,
                c.user_id AS caregiver_user_id
            FROM cancer_appointments a
            JOIN cancer_patients p ON a.patient_id = p.patient_id
            JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
            WHERE a.appointment_id = ?
        ");
        $check_stmt->bind_param("i", $appointment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            throw new Exception("Appointment not found.");
        }

        $appointment_details = $check_result->fetch_assoc();

        // Validate status input against allowed ENUM values
        $allowed_statuses = ['scheduled', 'completed', 'canceled', 'pending'];
        $proposed_status = !empty($_POST['status']) ? $conn->real_escape_string($_POST['status']) : null;

        // Authorization checks for status
        switch ($current_user_role) {
            case 'admin':
                // Admin can edit all details
                $patient_id = !empty($_POST['patient_id']) ? intval($_POST['patient_id']) : $appointment_details['patient_id'];
                $caregiver_id = !empty($_POST['caregiver_id']) ? intval($_POST['caregiver_id']) : $appointment_details['caregiver_id'];
                
                // Validate status if provided
                $status = ($proposed_status && in_array($proposed_status, $allowed_statuses)) 
                    ? $proposed_status 
                    : $appointment_details['status'];
                break;

            case 'patient':
                // Patient can only edit their own appointments and certain details
                if ($current_user_id !== $appointment_details['patient_user_id']) {
                    throw new Exception("Unauthorized to edit this appointment.");
                }
                $patient_id = $appointment_details['patient_id'];
                $caregiver_id = !empty($_POST['caregiver_id']) ? intval($_POST['caregiver_id']) : $appointment_details['caregiver_id'];
                $status = $appointment_details['status']; // Patient cannot change status
                break;

            case 'caregiver':
                // Caregiver can edit appointments they're involved in
                if ($current_user_id !== $appointment_details['caregiver_user_id']) {
                    throw new Exception("Unauthorized to edit this appointment.");
                }
                $patient_id = !empty($_POST['patient_id']) ? intval($_POST['patient_id']) : $appointment_details['patient_id'];
                $caregiver_id = $appointment_details['caregiver_id'];
                
                // Caregiver can change status to specific values
                $caregiver_allowed_statuses = ['scheduled', 'completed', 'canceled', 'pending'];
                $status = ($proposed_status && in_array($proposed_status, $caregiver_allowed_statuses)) 
                    ? $proposed_status 
                    : $appointment_details['status'];
                break;

            default:
                throw new Exception("Unauthorized access.");
        }

        // Validate date and time
        $current_date = date('Y-m-d');
        if ($appointment_date < $current_date) {
            throw new Exception("Appointment date cannot be in the past.");
        }

        // Prepare and execute the update statement
        $update_stmt = $conn->prepare("UPDATE cancer_appointments SET 
            patient_id = ?, 
            caregiver_id = ?, 
            appointment_date = ?, 
            appointment_time = ?, 
            notes = ?, 
            status = ?
            WHERE appointment_id = ?");

        $update_stmt->bind_param(
            "iissssi", 
            $patient_id, 
            $caregiver_id, 
            $appointment_date, 
            $appointment_time, 
            $notes,
            $status,
            $appointment_id
        );
        $update_stmt->execute();

        // Commit transaction
        $conn->commit();

        // Set success message
        $_SESSION['success_message'] = "Appointment updated successfully!";
        header("Location: ../view/appointments.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();

        // Set error message
        $_SESSION['error_message'] = "Error updating appointment: " . $e->getMessage();
        header("Location: ../view/appointments.php");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: ../view/appointments.php");
    exit();
}
?>