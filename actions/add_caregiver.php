<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../view/login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = $conn->real_escape_string(trim($_POST['first_name']));
    $last_name = $conn->real_escape_string(trim($_POST['last_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone_number = !empty($_POST['phone_number']) ? $conn->real_escape_string(trim($_POST['phone_number'])) : NULL;
    $specialization = !empty($_POST['specialization']) ? $conn->real_escape_string(trim($_POST['specialization'])) : NULL;
    
    $default_password = password_hash('Maureen123*', PASSWORD_DEFAULT);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into cancer_users table
        $user_sql = "INSERT INTO cancer_users (
            email, 
            password, 
            first_name, 
            last_name, 
            phone_number, 
            role
        ) VALUES (?, ?, ?, ?, ?, 'caregiver')";
        
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param(
            "sssss", 
            $email, 
            $default_password, 
            $first_name, 
            $last_name, 
            $phone_number
        );
        $stmt->execute();
        
        // Get the last inserted user_id
        $user_id = $conn->insert_id;
        
        // Insert into cancer_caregivers table
        $caregiver_sql = "INSERT INTO cancer_caregivers (
            user_id, 
            specialization
        ) VALUES (?, ?)";
        
        $caregiver_stmt = $conn->prepare($caregiver_sql);
        $caregiver_stmt->bind_param(
            "is", 
            $user_id, 
            $specialization
        );
        $caregiver_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Caregiver added successfully!";
        header("Location: ../view/caregivers.php");
        exit();
    } catch (Exception $e) {

        $conn->rollback();
        
        // Redirect with error message
        $_SESSION['error_message'] = "Error adding caregiver: " . $e->getMessage();
        header("Location: ../view/caregivers.php");
        exit();
    }
} else {
    header("Location: ../view/caregivers.php");
    exit();
}