<?php
// Start the session to access session variables
session_start();

include '../db/config.php';

// Session validation
if (isset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $user_role = $_SESSION['role'];
} else {
    header("Location: login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch user profile picture
$profile_picture_sql = "SELECT profile_picture 
                        FROM cancer_users 
                        WHERE user_id = ?";
$stmt = $conn->prepare($profile_picture_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();

// Use default image if no profile picture exists
$profile_picture = $user_profile['profile_picture'] ?? '../assets/images/defaultuser.jpg';
$stmt->close();

// Fetch resources with user and cancer type details
$sql = "SELECT 
    r.resource_id,
    r.title,
    r.resource_type,
    r.status,
    r.created_at,
    u.first_name,
    u.last_name,
    u.user_id,
    ct.cancer_type_name
FROM cancer_resources r
JOIN cancer_users u ON r.user_id = u.user_id
LEFT JOIN cancer_types ct ON r.cancer_type_id = ct.cancer_type_id
";

// If user is not admin, only show their resources
if ($user_role !== 'admin') {
    $sql .= " WHERE r.user_id = '$user_id'";
}

$sql .= " ORDER BY r.created_at DESC";
$result = $conn->query($sql);
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Fetch cancer types for dropdown
$cancer_types_sql = "SELECT cancer_type_id, cancer_type_name FROM cancer_types";
$cancer_types_result = $conn->query($cancer_types_sql);
$cancer_types = $cancer_types_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Admin Avatar" class="user-avatar">
            <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
        </div>
            <nav>
                <ul>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="caregivers.php"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                        <li><a href="patients.php"><i class="fas fa-users"></i> Patients & Survivors</a></li>
                    <?php elseif ($user_role === 'patient'): ?>
                        <li><a href="admin/patientdashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php elseif ($user_role === 'caregiver'): ?>
                        <li><a href="admin/caregiversdashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user_role !== 'caregiver'): ?>
                        <li><a href="stories.php"><i class="fas fa-book-open"></i> Stories</a></li>
                    <?php endif; ?>
                    <li><a href="resources.php" class="active"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Resources</h2>
                <a href="#" class="add-new-btn" onclick="openAddResourceModal()">
                    <i class="fas fa-plus"></i> Add New Resource
                </a>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Author</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Cancer Type</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($resource['title']); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($resource['resource_type']))); ?></td>
                                <td><?php echo htmlspecialchars($resource['cancer_type_name'] ?? 'General'); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($resource['status']))); ?></td>
                                <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button onclick="viewResourceDetails(<?php echo $resource['resource_id']; ?>)" class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user_role === 'admin'): ?>
                                        <button onclick="updateResourceStatus(<?php echo $resource['resource_id']; ?>, 'approved')" class="edit-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="updateResourceStatus(<?php echo $resource['resource_id']; ?>, 'rejected')" class="delete-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($user_role === 'admin' || $resource['user_id'] == $user_id): ?>
                                        <button onclick="editResourceDetails(<?php echo $resource['resource_id']; ?>)" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteResource(<?php echo $resource['resource_id']; ?>)" class="delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Resource Modal -->
    <div id="addResourceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddResourceModal()">&times;</span>
            <h2>Add New Resource</h2>
            <form id="addResourceForm" action="../actions/add_resource.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Resource Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="resource_type">Resource Type</label>
                    <select id="resource_type" name="resource_type" required>
                        <option value="lifestyle">Lifestyle</option>
                        <option value="knowledge_sharing">Knowledge Sharing</option>
                        <option value="nutrition">Nutrition</option>
                        <option value="mental_health">Mental Health</option>
                        <option value="exercise">Exercise</option>
                        <option value="support_groups">Support Groups</option>
                        <option value="treatment_tips">Treatment Tips</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cancer_type">Cancer Type</label>
                    <select id="cancer_type" name="cancer_type_id">
                        <option value="">General (All Types)</option>
                        <?php foreach ($cancer_types as $type): ?>
                            <option value="<?php echo $type['cancer_type_id']; ?>">
                                <?php echo htmlspecialchars($type['cancer_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="picture">Resource Picture (Optional)</label>
                    <input type="file" id="picture" name="picture" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddResourceModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add Resource</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddResourceModal() {
            document.getElementById('addResourceModal').style.display = 'block';
        }

        function closeAddResourceModal() {
            document.getElementById('addResourceModal').style.display = 'none';
        }

        // CRUD Functions
        function viewResourceDetails(resourceId) {
            window.location.href = `../actions/view_resource.php?resource_id=${resourceId}`;
        }

        function editResourceDetails(resourceId) {
            window.location.href = `../actions/edit_resource.php?resource_id=${resourceId}`;
        }

        function deleteResource(resourceId) {
            if (confirm('Are you sure you want to delete this resource? This action cannot be undone.')) {
                window.location.href = `../actions/delete_resource.php?resource_id=${resourceId}`;
            }
        }

        <?php if ($user_role === 'admin'): ?>
        function updateResourceStatus(resourceId, status) {
            if (confirm(`Are you sure you want to ${status} this resource?`)) {
                window.location.href = `../actions/update_resource_status.php?resource_id=${resourceId}&status=${status}`;
            }
        }
        <?php endif; ?>

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('addResourceModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>