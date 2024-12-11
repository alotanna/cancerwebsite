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
    header("Location: ../view/stories.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Validate story ID
if (!isset($_POST['story_id']) || !is_numeric($_POST['story_id'])) {
    $_SESSION['message'] = "Invalid story ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}

$story_id = intval($_POST['story_id']);

// Validate ownership and permissions
$check_ownership_sql = "SELECT s.story_id, p.user_id 
    FROM cancer_stories s
    JOIN cancer_patients p ON s.patient_id = p.patient_id
    WHERE s.story_id = ?";
$stmt = $conn->prepare($check_ownership_sql);
$stmt->bind_param("i", $story_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Story not found";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}

$story = $result->fetch_assoc();
$stmt->close();

// Check permissions
if (!($user_role === 'admin' || ($user_role === 'patient' && $story['user_id'] == $user_id))) {
    $_SESSION['message'] = "You do not have permission to edit this story";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}

// Validate form inputs
$title = trim($_POST['title']);
$content = trim($_POST['content']);

if (empty($title) || empty($content)) {
    $_SESSION['message'] = "Title and content are required";
    $_SESSION['message_type'] = "error";
    header("Location: edit_story.php?story_id=$story_id");
    exit();
}

// Handle file upload
$picture_path = $_POST['current_picture'];
if (!empty($_FILES['picture']['name'])) {
    $upload_dir = '../uploads/story_images/';
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
        header("Location: edit_story.php?story_id=$story_id");
        exit();
    }

    if ($file_size > 5 * 1024 * 1024) { // 5MB limit
        $_SESSION['message'] = "File size must be less than 5MB";
        $_SESSION['message_type'] = "error";
        header("Location: edit_story.php?story_id=$story_id");
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
        header("Location: edit_story.php?story_id=$story_id");
        exit();
    }
}

// Update story in database
$update_sql = "UPDATE cancer_stories 
    SET title = ?, content = ?, picture = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP 
    WHERE story_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("sssi", $title, $content, $picture_path, $story_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Story updated successfully";
    $_SESSION['message_type'] = "success";
    header("Location: view_story.php?story_id=$story_id");
} else {
    $_SESSION['message'] = "Failed to update story";
    $_SESSION['message_type'] = "error";
    header("Location: edit_story.php?story_id=$story_id");
}

$stmt->close();
$conn->close();