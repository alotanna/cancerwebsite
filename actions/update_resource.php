<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../view/login.php");
    exit();
}

// Check form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/resources.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Validate resource ID
if (!isset($_POST['resource_id']) || !is_numeric($_POST['resource_id'])) {
    $_SESSION['message'] = "Invalid resource ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

$resource_id = intval($_POST['resource_id']);

// Validate ownership and permissions
$check_ownership_sql = "SELECT resource_id, user_id 
    FROM cancer_resources
    WHERE resource_id = ?";
$stmt = $conn->prepare($check_ownership_sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Resource not found";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

$resource = $result->fetch_assoc();
$stmt->close();

// Check permissions
if (!($user_role === 'admin' || $resource['user_id'] == $user_id)) {
    $_SESSION['message'] = "You do not have permission to edit this resource";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

// Validate form inputs
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$resource_type = $_POST['resource_type'];
$cancer_type_id = !empty($_POST['cancer_type_id']) ? intval($_POST['cancer_type_id']) : null;

if (empty($title) || empty($content)) {
    $_SESSION['message'] = "Title and content are required";
    $_SESSION['message_type'] = "error";
    header("Location: edit_resource.php?resource_id=$resource_id");
    exit();
}

// Handle file upload
$picture_path = $_POST['current_picture'];
if (!empty($_FILES['picture']['name'])) {
    $upload_dir = '../../uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_type = $_FILES['picture']['type'];
    $file_size = $_FILES['picture']['size'];
    
    // Validate file
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['message'] = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
        $_SESSION['message_type'] = "error";
        header("Location: edit_resource.php?resource_id=$resource_id");
        exit();
    }

    if ($file_size > 5 * 1024 * 1024) { // 5MB limit
        $_SESSION['message'] = "File size must be less than 5MB";
        $_SESSION['message_type'] = "error";
        header("Location: edit_resource.php?resource_id=$resource_id");
        exit();
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($_FILES['picture']['name']);
    $target_path = $upload_dir . $filename;

    // Delete old picture if exists and new picture is uploaded
    if (!empty($picture_path) && file_exists($picture_path)) {
        unlink($picture_path);
    }

    // Move uploaded file
    if (move_uploaded_file($_FILES['picture']['tmp_name'], $target_path)) {
        $picture_path = $target_path;
    } else {
        $_SESSION['message'] = "Failed to upload image";
        $_SESSION['message_type'] = "error";
        header("Location: edit_resource.php?resource_id=$resource_id");
        exit();
    }
}

// Update resource in database
$update_sql = "UPDATE cancer_resources 
    SET title = ?, content = ?, picture = ?, 
        resource_type = ?, cancer_type_id = ?, 
        status = 'pending', updated_at = CURRENT_TIMESTAMP 
    WHERE resource_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("sssssi", $title, $content, $picture_path, $resource_type, $cancer_type_id, $resource_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Resource updated successfully";
    $_SESSION['message_type'] = "success";
    header("Location: view_resource.php?resource_id=$resource_id");
} else {
    $_SESSION['message'] = "Failed to update resource";
    $_SESSION['message_type'] = "error";
    header("Location: edit_resource.php?resource_id=$resource_id");
}

$stmt->close();
$conn->close();