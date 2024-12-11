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

// Fetch resource details with user and cancer type information
$sql = "SELECT 
    r.resource_id,
    r.title,
    r.content,
    r.picture,
    r.status,
    r.resource_type,
    r.created_at,
    u.user_id,
    u.first_name,
    u.last_name,
    ct.cancer_type_name
FROM cancer_resources r
JOIN cancer_users u ON r.user_id = u.user_id
LEFT JOIN cancer_types ct ON r.cancer_type_id = ct.cancer_type_id
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

// Check if the user has permission to view the resource
$can_view = ($user_role === 'admin') || 
            ($resource['user_id'] == $user_id) || 
            ($resource['status'] === 'approved');

if (!$can_view) {
    $_SESSION['message'] = "You do not have permission to view this resource";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/resources.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Details - Cancer Support Platform</title>
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
                <h2>Resource Details</h2>
                <a href="../view/resources.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>

            <div class="patient-details-container">
                <div class="patient-info-card">
                    <h3>Resource Information</h3>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <label>Title</label>
                            <p><?php echo htmlspecialchars($resource['title']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Author</label>
                            <p><?php echo htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Resource Type</label>
                            <p><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($resource['resource_type']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Cancer Type</label>
                            <p><?php echo $resource['cancer_type_name'] ? htmlspecialchars($resource['cancer_type_name']) : 'General'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p><?php echo htmlspecialchars(ucfirst($resource['status'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Shared Date</label>
                            <p><?php echo htmlspecialchars(date('M d, Y', strtotime($resource['created_at']))); ?></p>
                        </div>
                    </div>
                </div>

                <div class="patient-medical-card">
                    <h3>Resource Content</h3>
                    <?php if (!empty($resource['picture'])): ?>
                        <div class="story-image-container">
                            <img src="<?php echo htmlspecialchars($resource['picture']); ?>" alt="Resource Picture">
                        </div>
                    <?php endif; ?>
                    <div class="resource-content">
                        <p><?php echo nl2br(htmlspecialchars($resource['content'])); ?></p>
                    </div>
                </div>

                <?php if ($user_role === 'admin' || ($resource['user_id'] == $user_id)): ?>
                <div class="action-container">
                    <?php if ($user_role === 'admin'): ?>
                        <div class="admin-actions">
                            <a href="../actions/update_resource_status.php?resource_id=<?php echo $resource_id; ?>&status=approved" class="btn btn-approve">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="../actions/update_resource_status.php?resource_id=<?php echo $resource_id; ?>&status=rejected" class="btn btn-reject">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($resource['user_id'] == $user_id): ?>
                        <div class="user-actions">
                            <a href="../actions/edit_resource.php?resource_id=<?php echo $resource_id; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit Resource
                            </a>
                            <a href="../actions/delete_resource.php?resource_id=<?php echo $resource_id; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this resource?');">
                                <i class="fas fa-trash"></i> Delete Resource
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