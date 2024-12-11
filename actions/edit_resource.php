<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../view/login.php");
    exit();
}

// Check if resource_id is provided
if (!isset($_GET['resource_id']) || !is_numeric($_GET['resource_id'])) {
    $_SESSION['message'] = "Invalid resource ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

$resource_id = intval($_GET['resource_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch resource details
$sql = "SELECT 
    r.resource_id, 
    u.user_id,
    r.title,
    r.content,
    r.picture,
    r.status,
    r.resource_type,
    r.cancer_type_id
FROM cancer_resources r
JOIN cancer_users u ON r.user_id = u.user_id
WHERE r.resource_id = ?";

$stmt = $conn->prepare($sql);
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

// Check permission (only user who created the resource or admin can edit)
if (!($user_role === 'admin' || $resource['user_id'] == $user_id)) {
    $_SESSION['message'] = "You do not have permission to edit this resource";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}

// Fetch cancer types for dropdown
$cancer_types_query = "SELECT cancer_type_id, cancer_type_name FROM cancer_types ORDER BY cancer_type_name";
$cancer_types_result = $conn->query($cancer_types_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Edit Resource</h2>
                <a href="../view/resources.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>

            <div class="patient-details-container">
                <form action="update_resource.php" method="POST" enctype="multipart/form-data" class="resource-edit-form">
                    <input type="hidden" name="resource_id" value="<?php echo $resource_id; ?>">
                    
                    <div class="form-group">
                        <label for="title">Resource Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($resource['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="resource_type">Resource Type</label>
                        <select id="resource_type" name="resource_type" required>
                            <option value="lifestyle" <?php echo ($resource['resource_type'] == 'lifestyle') ? 'selected' : ''; ?>>Lifestyle</option>
                            <option value="knowledge_sharing" <?php echo ($resource['resource_type'] == 'knowledge_sharing') ? 'selected' : ''; ?>>Knowledge Sharing</option>
                            <option value="nutrition" <?php echo ($resource['resource_type'] == 'nutrition') ? 'selected' : ''; ?>>Nutrition</option>
                            <option value="mental_health" <?php echo ($resource['resource_type'] == 'mental_health') ? 'selected' : ''; ?>>Mental Health</option>
                            <option value="exercise" <?php echo ($resource['resource_type'] == 'exercise') ? 'selected' : ''; ?>>Exercise</option>
                            <option value="support_groups" <?php echo ($resource['resource_type'] == 'support_groups') ? 'selected' : ''; ?>>Support Groups</option>
                            <option value="treatment_tips" <?php echo ($resource['resource_type'] == 'treatment_tips') ? 'selected' : ''; ?>>Treatment Tips</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cancer_type_id">Related Cancer Type (Optional)</label>
                        <select id="cancer_type_id" name="cancer_type_id">
                            <option value="">Select Cancer Type</option>
                            <?php 
                            while ($type = $cancer_types_result->fetch_assoc()) {
                                $selected = ($type['cancer_type_id'] == $resource['cancer_type_id']) ? 'selected' : '';
                                echo "<option value='{$type['cancer_type_id']}' $selected>{$type['cancer_type_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="content">Resource Content</label>
                        <textarea id="content" name="content" rows="10" required><?php 
                            echo htmlspecialchars($resource['content']); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="picture">Update Resource Picture (Optional)</label>
                        <?php if (!empty($resource['picture'])): ?>
                            <div class="current-image">
                                <p>Current Image:</p>
                                <img src="<?php echo htmlspecialchars($resource['picture']); ?>" 
                                     alt="Current Resource Picture" style="max-width: 200px; margin-bottom: 10px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="picture" name="picture" accept="image/*">
                        <input type="hidden" name="current_picture" 
                               value="<?php echo htmlspecialchars($resource['picture']); ?>">
                    </div>

                    <div class="form-actions">
                        <a href="../view/resources.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="submit-btn">Update Resource</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>