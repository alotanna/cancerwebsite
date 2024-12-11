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

    // Ensure only admin and patient can access
    if ($user_role !== 'admin' && $user_role !== 'patient') {
        header("Location: ../view/login.php");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch stories with patient and cancer type details
$sql = "SELECT 
    s.story_id, 
    u.first_name, 
    u.last_name, 
    u.user_id,
    s.title,
    s.status,
    s.created_at
FROM cancer_stories s
JOIN cancer_patients p ON s.patient_id = p.patient_id
JOIN cancer_users u ON p.user_id = u.user_id
" . ($user_role === 'patient' ? "WHERE p.user_id = '$user_id'" : "") . "
ORDER BY s.created_at DESC";
$result = $conn->query($sql);
$stories = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Stories - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../../assets/images/austine.jpeg" alt="User Avatar" class="user-avatar">
                <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
            </div>
            <nav>
                <ul>
                    <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="caregivers.php"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                        <li><a href="patients.php"><i class="fas fa-users"></i> Patients & Survivors</a></li>
                    <?php endif; ?>
                    <li><a href="stories.php" class="active"><i class="fas fa-book-open"></i> Stories Shared</a></li>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Stories Shared</h2>
                <?php if ($user_role === 'patient'): ?>
                    <a href="#" class="add-new-btn" onclick="openAddStoryModal()">
                        <i class="fas fa-plus"></i> Add New Story
                    </a>
                <?php endif; ?>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Author</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Shared Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stories as $story): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($story['first_name'] . ' ' . $story['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($story['title']); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($story['status']))); ?></td>
                                <td><?php echo date('M d, Y', strtotime($story['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button onclick="viewStoryDetails(<?php echo $story['story_id']; ?>)" class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user_role === 'admin'): ?>
                                        <button onclick="updateStoryStatus(<?php echo $story['story_id']; ?>, 'approved')" class="edit-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="updateStoryStatus(<?php echo $story['story_id']; ?>, 'rejected')" class="delete-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($user_role === 'patient' && $story['user_id'] == $user_id): ?>
                                        <button onclick="editStoryDetails(<?php echo $story['story_id']; ?>)" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="deleteStory(<?php echo $story['story_id']; ?>)" class="delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Story Modal -->
    <?php if ($user_role === 'patient'): ?>
    <div id="addStoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddStoryModal()">&times;</span>
            <h2>Share Your Story</h2>
            <form id="addStoryForm" action="../actions/add_story.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Story Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Your Story</label>
                    <textarea id="content" name="content" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="picture">Story Picture (Optional)</label>
                    <input type="file" id="picture" name="picture" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddStoryModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Share Story</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Modal Functions
        function openAddStoryModal() {
            document.getElementById('addStoryModal').style.display = 'block';
        }

        function closeAddStoryModal() {
            document.getElementById('addStoryModal').style.display = 'none';
        }

        // CRUD Functions
        function viewStoryDetails(storyId) {
            window.location.href = `../actions/view_story.php?story_id=${storyId}`;
        }

        function editStoryDetails(storyId) {
            window.location.href = `../actions/edit_story.php?story_id=${storyId}`;
        }

        function deleteStory(storyId) {
            if (confirm('Are you sure you want to delete this story? This action cannot be undone.')) {
                window.location.href = `../actions/delete_story.php?story_id=${storyId}`;
            }
        }

        <?php if ($user_role === 'admin'): ?>
        function updateStoryStatus(storyId, status) {
            if (confirm(`Are you sure you want to ${status} this story?`)) {
                window.location.href = `../actions/update_story_status.php?story_id=${storyId}&status=${status}`;
            }
        }
        <?php endif; ?>

        // Close modal if clicked outside
        window.onclick = function(event) {
            <?php if ($user_role === 'patient'): ?>
            const modal = document.getElementById('addStoryModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            <?php endif; ?>
        }
    </script>
</body>
</html>