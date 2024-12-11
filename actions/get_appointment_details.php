<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);

    // Prepare SQL to get appointment details
    $stmt = $conn->prepare("SELECT 
        patient_id, 
        caregiver_id, 
        appointment_date, 
        appointment_time, 
        notes, 
        status 
    FROM cancer_appointments 
    WHERE appointment_id = ?");
    
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        echo json_encode($appointment);
    } else {
        echo json_encode(['error' => 'Appointment not found']);
    }
    exit();
}