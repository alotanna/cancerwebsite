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
    $patient_id = null;
    $caregiver_id = null;
    $appointment_date = $conn->real_escape_string(trim($_POST['appointment_date']));
    $appointment_time = $conn->real_escape_string(trim($_POST['appointment_time']));
    $notes = !empty($_POST['notes']) ? $conn->real_escape_string(trim($_POST['notes'])) : null;

    // Role-specific patient and caregiver ID handling
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Validate patient ID based on user role
        switch ($current_user_role) {
            case 'admin':
                // Admin must select a patient
                if (empty($_POST['patient_id'])) {
                    throw new Exception("Patient must be selected.");
                }
                $patient_id = intval($_POST['patient_id']);
                break;

            case 'patient':
                // Get patient ID for logged-in patient
                $patient_stmt = $conn->prepare("SELECT patient_id FROM cancer_patients WHERE user_id = ?");
                $patient_stmt->bind_param("i", $current_user_id);
                $patient_stmt->execute();
                $patient_result = $patient_stmt->get_result();
                
                if ($patient_result->num_rows === 0) {
                    throw new Exception("Patient profile not found.");
                }
                $patient_row = $patient_result->fetch_assoc();
                $patient_id = $patient_row['patient_id'];
                break;

            case 'caregiver':
                // Get patient ID from the selected patient
                if (empty($_POST['patient_id'])) {
                    throw new Exception("Patient must be selected.");
                }
                $patient_id = intval($_POST['patient_id']);
                break;

            default:
                throw new Exception("Unauthorized access.");
        }

        // Validate caregiver ID based on user role
        switch ($current_user_role) {
            case 'admin':
            case 'patient':
                if (empty($_POST['caregiver_id'])) {
                    throw new Exception("Caregiver must be selected.");
                }
                $caregiver_id = intval($_POST['caregiver_id']);
                break;

            case 'caregiver':
                // Automatically set caregiver ID to logged-in caregiver
                $caregiver_stmt = $conn->prepare("SELECT caregiver_id FROM cancer_caregivers WHERE user_id = ?");
                $caregiver_stmt->bind_param("i", $current_user_id);
                $caregiver_stmt->execute();
                $caregiver_result = $caregiver_stmt->get_result();
                
                if ($caregiver_result->num_rows === 0) {
                    throw new Exception("Caregiver profile not found.");
                }
                $caregiver_row = $caregiver_result->fetch_assoc();
                $caregiver_id = $caregiver_row['caregiver_id'];
                break;

            default:
                throw new Exception("Unauthorized access.");
        }

        // Validate date and time
        $current_date = date('Y-m-d');
        if ($appointment_date < $current_date) {
            throw new Exception("Appointment date cannot be in the past.");
        }

        // Prepare and execute the insert statement
        $insert_stmt = $conn->prepare("INSERT INTO cancer_appointments (
            patient_id, 
            caregiver_id, 
            appointment_date, 
            appointment_time, 
            notes, 
            status
        ) VALUES (?, ?, ?, ?, ?, 'scheduled')");

        $insert_stmt->bind_param(
            "iisss", 
            $patient_id, 
            $caregiver_id, 
            $appointment_date, 
            $appointment_time, 
            $notes
        );
        $insert_stmt->execute();

        // Commit transaction
        $conn->commit();

        // Set success message
        $_SESSION['success_message'] = "Appointment scheduled successfully!";
        header("Location: ../view/appointments.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();

        // Set error message
        $_SESSION['error_message'] = "Error scheduling appointment: " . $e->getMessage();
        header("Location: ../view/appointments.php");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: ../view/appointments.php");
    exit();
}
?>