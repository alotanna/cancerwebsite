<?php
include '../db/config.php';

session_start();

// error checking and debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = sanitize_input($_POST["first_name"]);
    $lname = sanitize_input($_POST["last_name"]);
    $email = sanitize_input($_POST["email"]);
    $password = sanitize_input($_POST["password"]);
    $confirmPassword = sanitize_input($_POST["confirmPassword"]);
  
    $termsConsent = isset($_POST["termsConsent"]) ? true : false;
    
    $errors = array();

    // Server-side validation
    // First name validation
    if (empty($fname)) {
        $errors['fname'] = "First name is required.";
    } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $fname)) {
        $errors['fname'] = "Only letters and white space allowed.";
    }

    // Last name validation
    if (empty($lname)) {
        $errors['lname'] = "Last name is required.";
    } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $lname)) {
        $errors['lname'] = "Only letters and white space allowed.";
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM cancer_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = "Email already registered.";
        }
        $stmt->close();
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*\d{3,})(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/", $password)) {
        $errors['password'] = "Password must meet all requirements.";
    }

    // Confirm password validation
    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = "Passwords do not match.";
    }


    // Terms consent validation
    if (!$termsConsent) {
        $errors['terms'] = "You must agree to the Terms of Service and Privacy Policy.";
    }

    // If there are no errors, proceed with registration
    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert into cancer_users table with default role as patient
            $stmt = $conn->prepare("INSERT INTO cancer_users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fname, $lname, $email, $hashedPassword);
            $stmt->execute();
            
            $userId = $conn->insert_id;
            
            // Create corresponding entry in cancer_patients table
            $stmt = $conn->prepare("INSERT INTO cancer_patients (user_id) VALUES (?)");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $conn->commit();
            
            // Registration successful - redirect to login page
            header("Location: ../view/login.html");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = "Registration failed: " . $e->getMessage();
        }
        $stmt->close();
    }

    // If there are errors, send them back to the client
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => $errors]);
        exit();
    }
}

$conn->close();
?>