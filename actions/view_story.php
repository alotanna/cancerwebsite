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
    header("Location: ../view/admin/stories.php");
    exit();
}

$story_id = intval($_GET['story_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch story details with patient and cancer type information
$sql = "SELECT 
    s.story_id, 
    u.first_name, 
    u.last_name, 
    u.user_id,
    s.title,
    s.content,
    s.picture,
    s.status,
    s.created_at,
    p.patient_id
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
    header("Location: ../view/admin/stories.php");
    exit();
}

$story = $result->fetch_assoc();
$stmt->close();

// Check if the user has permission to view the story
$can_view = ($user_role === 'admin') || 
            ($user_role === 'patient' && $story['user_id'] == $user_id) || 
            ($story['status'] === 'approved');

if (!$can_view) {
    $_SESSION['message'] = "You do not have permission to view this story";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/admin/stories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Details - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link rel="stylesheet" href="../assets/css/viewpage.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Story Details</h2>
                <a href="../view/stories.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Stories
                </a>
            </div>

            <div class="patient-details-container">
                <div class="patient-info-card">
                    <h3>Story Information</h3>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <label>Title</label>
                            <p><?php echo htmlspecialchars($story['title']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Author</label>
                            <p><?php echo htmlspecialchars($story['first_name'] . ' ' . $story['last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($story['status']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Shared Date</label>
                            <p><?php echo htmlspecialchars(date('M d, Y', strtotime($story['created_at']))); ?></p>
                        </div>
                    </div>
                </div>

                <div class="patient-medical-card">
                    <h3>Story Content</h3>
                    <?php if (!empty($story['picture'])): ?>
                        <div class="story-image-container">
                            <img src="<?php echo htmlspecialchars($story['picture']); ?>" alt="Story Picture">
                        </div>
                    <?php endif; ?>
                    <div class="story-content">
                        <p><?php echo nl2br(htmlspecialchars($story['content'])); ?></p>
                    </div>
                </div>

                <?php if ($user_role === 'admin' || ($user_role === 'patient' && $story['user_id'] == $user_id)): ?>
                <div class="action-container">
                    <?php if ($user_role === 'admin'): ?>
                        <div class="admin-actions">
                            <a href="../actions/update_story_status.php?story_id=<?php echo $story_id; ?>&status=approved" class="btn btn-approve">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="../actions/update_story_status.php?story_id=<?php echo $story_id; ?>&status=rejected" class="btn btn-reject">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user_role === 'patient' && $story['user_id'] == $user_id): ?>
                        <div class="user-actions">
                            <a href="../actions/edit_story.php?story_id=<?php echo $story_id; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit Story
                            </a>
                            <a href="../actions/delete_story.php?story_id=<?php echo $story_id; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this story?');">
                                <i class="fas fa-trash"></i> Delete Story
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>