<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../view/login.php");
    exit();
}

// Check if story_id is provided
if (!isset($_GET['story_id']) || !is_numeric($_GET['story_id'])) {
    $_SESSION['message'] = "Invalid story ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}

$story_id = intval($_GET['story_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch story details
$sql = "SELECT 
    s.story_id, 
    u.user_id,
    s.title,
    s.content,
    s.picture,
    s.status
FROM cancer_stories s
JOIN cancer_patients p ON s.patient_id = p.patient_id
JOIN cancer_users u ON p.user_id = u.user_id
WHERE s.story_id = ?";

$stmt = $conn->prepare($sql);
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

// Check permission (only patient who owns the story or admin can edit)
if (!($user_role === 'admin' || ($user_role === 'patient' && $story['user_id'] == $user_id))) {
    $_SESSION['message'] = "You do not have permission to edit this story";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/stories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Story - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Edit Story</h2>
                <a href="../view/stories.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Stories
                </a>
            </div>

            <div class="patient-details-container">
                <form action="update_story.php" method="POST" enctype="multipart/form-data" class="story-edit-form">
                    <input type="hidden" name="story_id" value="<?php echo $story_id; ?>">
                    
                    <div class="form-group">
                        <label for="title">Story Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($story['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Your Story</label>
                        <textarea id="content" name="content" rows="10" required><?php 
                            echo htmlspecialchars($story['content']); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="picture">Update Story Picture (Optional)</label>
                        <?php if (!empty($story['picture'])): ?>
                            <div class="current-image">
                                <p>Current Image:</p>
                                <img src="<?php echo htmlspecialchars($story['picture']); ?>" 
                                     alt="Current Story Picture" style="max-width: 200px; margin-bottom: 10px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="picture" name="picture" accept="image/*">
                        <input type="hidden" name="current_picture" 
                               value="<?php echo htmlspecialchars($story['picture']); ?>">
                    </div>

                    <div class="form-actions">
                        <a href="../view/stories.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="submit-btn">Update Story</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>