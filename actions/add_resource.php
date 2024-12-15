<?php
session_start();
include '../db/config.php';

// Validate user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Unauthorized access. Please log in.";
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate and process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $resource_type = trim($_POST['resource_type']);
    $cancer_type_id = !empty($_POST['cancer_type_id']) ? (int)$_POST['cancer_type_id'] : null;

    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = "Resource title is required.";
    if (empty($content)) $errors[] = "Resource content is required.";
    if (empty($resource_type)) $errors[] = "Resource type is required.";
    if (strlen($title) > 255) $errors[] = "Resource title cannot exceed 255 characters.";
    if (strlen($content) > 10000) $errors[] = "Resource content cannot exceed 10,000 characters.";

    // Handle picture upload
    $picture_path = null;
    if (!empty($_FILES['picture']['name'])) {
        $upload_dir = '../../uploads/';
        
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

        // Generate unique filename using timestamp and original name
        $timestamp = time();
        $filename = $timestamp . '_' . basename($_FILES['picture']['name']);
        $target_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (empty($errors) && move_uploaded_file($_FILES['picture']['tmp_name'], $target_path)) {
            $picture_path = '../../uploads/' . $filename;
        } else {
            $errors[] = "Failed to upload picture.";
        }
    }

    // If no errors, insert resource
    if (empty($errors)) {
        try {
            // Set initial status based on user role
            $status = ($_SESSION['role'] === 'admin') ? 'approved' : 'pending';
            
            $sql = "INSERT INTO cancer_resources (user_id, title, content, resource_type, cancer_type_id, picture, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssiss", $user_id, $title, $content, $resource_type, $cancer_type_id, $picture_path, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = ($_SESSION['role'] === 'admin') 
                    ? "Resource has been added successfully." 
                    : "Your resource has been submitted and is pending approval.";
                header("Location: ../view/resources.php");
                exit();
            } else {
                throw new Exception("Database insertion failed.");
            }
        } catch (Exception $e) {
            // Log the error
            error_log("Resource submission error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to submit resource. Please try again.";
            
            // Delete uploaded file if database insertion fails
            if ($picture_path && file_exists($target_path)) {
                unlink($target_path);
            }
        }
    }

    // If there are errors, store them in session and redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = [
            'title' => $title,
            'content' => $content,
            'resource_type' => $resource_type,
            'cancer_type_id' => $cancer_type_id
        ];
    }

    // Redirect back to resources page
    header("Location: ../view/resources.php");
    exit();
} else {
    // If not a POST request, redirect to resources page
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../view/resources.php");
    exit();
}

// Close database connection
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>