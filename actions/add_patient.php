<?php
session_start();
include '../db/config.php';

// Session and role validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Input sanitization function
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form inputs
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_number = !empty($_POST['phone_number']) ? sanitizeInput($_POST['phone_number']) : null;
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender = sanitizeInput($_POST['gender']);
    $cancer_type_id = !empty($_POST['cancer_type_id']) ? intval($_POST['cancer_type_id']) : null;
    $immunotherapy_status = sanitizeInput($_POST['immunotherapy_status']);

    // Validation
    $errors = [];

    // Validate required fields
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($immunotherapy_status)) $errors[] = "Immunotherapy status is required.";

    // Check if email already exists
    $email_check_sql = "SELECT user_id FROM cancer_users WHERE email = ?";
    $email_stmt = $conn->prepare($email_check_sql);
    $email_stmt->bind_param("s", $email);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    if ($email_result->num_rows > 0) {
        $errors[] = "Email address is already in use.";
    }
    $email_stmt->close();

    // If no errors, proceed with patient addition
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Generate a secure random password
            $temporary_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);

            // Insert into cancer_users table
            $user_sql = "INSERT INTO cancer_users 
                         (email, password, first_name, last_name, phone_number, role) 
                         VALUES (?, ?, ?, ?, ?, 'patient')";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("sssss", 
                $email, 
                $hashed_password, 
                $first_name, 
                $last_name, 
                $phone_number
            );
            $user_stmt->execute();
            $user_id = $user_stmt->insert_id;
            $user_stmt->close();

            // Insert into cancer_patients table
            $patient_sql = "INSERT INTO cancer_patients 
                            (user_id, date_of_birth, gender, cancer_type_id, immunotherapy_status) 
                            VALUES (?, ?, ?, ?, ?)";
            $patient_stmt = $conn->prepare($patient_sql);
            $patient_stmt->bind_param("issss", 
                $user_id, 
                $date_of_birth, 
                $gender, 
                $cancer_type_id, 
                $immunotherapy_status
            );
            $patient_stmt->execute();
            $patient_stmt->close();

            // Commit the transaction
            $conn->commit();

            // Set success message
            $_SESSION['message'] = "Patient added successfully.";
            $_SESSION['message_type'] = "success";

            // TODO: Send welcome email with temporary password (optional)

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();

            // Set error message
            $_SESSION['message'] = "Error adding patient: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    } else {
        // Store errors in session
        $_SESSION['errors'] = $errors;
        $_SESSION['message_type'] = "error";
    }

    // Redirect back to patients page
    header("Location: ../view/patients.php");
    exit();
} else {
    // Direct access prevention
    header("Location: ../view/patients.php");
    exit();
}
?>