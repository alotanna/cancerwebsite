<?php
include 'config.php';

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
    $email = sanitize_input($_POST["email"]);
    $password = sanitize_input($_POST["password"]);
    $errors = array();

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*\d{3,})(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/", $password)) {
        $errors['password'] = "Password must meet all requirements.";
    }

// If there are no errors, proceed with login
if (empty($errors)) {
    // Check if user exists in the database
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];

        // Verify the password
        if (password_verify($password, $hashedPassword)) {
            // Set session variables
            $_SESSION['user_id'] = $row['user_id']; 
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role'] = $row['role'];

            //echo "Welcome, " . $row['fname'] . " " . $row['lname'] . " (Role: " . $_SESSION['role'] . ")";  // Fixed to display role

            // Redirect to the appropriate dashboard based on the user role
            header("Location: dashboard.php");
            exit();
        } else {
            $errors['login'] = "Invalid email or password.";
        }
    } else {
        $errors['login'] = "Invalid email or password.";
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