<?php
session_start();
include '../db/config.php';

// Validate user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    $_SESSION['error'] = "Unauthorized access. Please log in as a patient.";
    header("Location: ../view/login.php");
    exit();
}

// Retrieve patient ID for the logged-in user
$user_id = $_SESSION['user_id'];
$patient_query = "SELECT patient_id FROM cancer_patients WHERE user_id = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Patient profile not found.";
    header("Location: ../view/stories.php");
    exit();
}

$patient_row = $result->fetch_assoc();
$patient_id = $patient_row['patient_id'];

// Validate and process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = "Story title is required.";
    if (empty($content)) $errors[] = "Story content is required.";
    if (strlen($title) > 255) $errors[] = "Story title cannot exceed 255 characters.";
    if (strlen($content) > 10000) $errors[] = "Story content cannot exceed 10,000 characters.";

    // Handle picture upload
    $picture_path = null;
    if (!empty($_FILES['picture']['name'])) {
        $upload_dir = '../uploads/stories/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        $file_type = mime_content_type($_FILES['picture']['tmp_name']);
        $file_size = $_FILES['picture']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and GIF images are allowed.";
        }

        if ($file_size > $max_file_size) {
            $errors[] = "File size cannot exceed 5MB.";
        }

        // Generate unique filename
        $timestamp = time();
        $filename = $timestamp . '_' . basename($_FILES['picture']['name']);
        $target_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (empty($errors) && move_uploaded_file($_FILES['picture']['tmp_name'], $target_path)) {
            $picture_path = '../uploads/stories/' . $filename;
        } else {
            $errors[] = "Failed to upload picture.";
        }
    }

    // If no errors, insert story
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO cancer_stories (patient_id, title, content, picture, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $patient_id, $title, $content, $picture_path);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Your story has been submitted and is pending approval.";
                header("Location: ../view/stories.php");
                exit();
            } else {
                throw new Exception("Database insertion failed.");
            }
        } catch (Exception $e) {
            // Log the error 
            error_log("Story submission error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to submit story. Please try again.";
        }
    }

    // If there are errors, store them in session and redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = [
            'title' => $title,
            'content' => $content,
        ];
    }

    // Redirect back to stories page
    header("Location: ../view/stories.php");
    exit();
} else {
    // If not a POST request, redirect to stories page
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../view/stories.php");
    exit();
}

// Close database connection
$stmt->close();
$conn->close();
?>