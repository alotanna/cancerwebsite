<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../view/login.php");
    exit();
}

// Validate and sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_number = sanitizeInput($_POST['phone_number'] ?? '');
    $gender = sanitizeInput($_POST['gender']);
    $cancer_type_id = !empty($_POST['cancer_type_id']) ? intval($_POST['cancer_type_id']) : null;
    $treatment_status = sanitizeInput($_POST['treatment_status']);

    // Validate required fields
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($treatment_status)) $errors[] = "Treatment status is required";

    // Check if email already exists
    $check_email_sql = "SELECT user_id FROM cancer_users WHERE email = ?";
    $check_stmt = $conn->prepare($check_email_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    $check_stmt->close();

    // If no errors, proceed with insertion
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert into cancer_users
            $user_sql = "INSERT INTO cancer_users (email, password, first_name, last_name, phone_number, role) 
                         VALUES (?, ?, ?, ?, ?, 'patient')";
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // Generate random password
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("sssss", $email, $password, $first_name, $last_name, $phone_number);
            $user_stmt->execute();
            $user_id = $user_stmt->insert_id;
            $user_stmt->close();

            // Insert into cancer_patients
            $patient_sql = "INSERT INTO cancer_patients (user_id, gender, cancer_type_id, treatment_status) 
                            VALUES (?, ?, ?, ?)";
            $patient_stmt = $conn->prepare($patient_sql);
            $patient_stmt->bind_param("iiss", $user_id, $gender, $cancer_type_id, $treatment_status);
            $patient_stmt->execute();
            $patient_stmt->close();

            // Commit transaction
            $conn->commit();

            // Set success message
            $_SESSION['message'] = "Patient added successfully";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();

            // Set error message
            $_SESSION['message'] = "Error adding patient: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    } else {
        // Set error messages
        $_SESSION['errors'] = $errors;
        $_SESSION['message_type'] = "error";
    }

    // Redirect back to patients page
    header("Location: ../view/patients.php");
    exit();
} else {
    // If accessed directly without POST
    header("Location: ../view/patients.php");
    exit();
}