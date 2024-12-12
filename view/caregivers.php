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

    // Ensure only admin can access
    if ($user_role !== 'admin') {
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch caregivers with their user details
$sql = "SELECT 
    c.caregiver_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    c.specialization,
    u.created_at
FROM cancer_caregivers c
JOIN cancer_users u ON c.user_id = u.user_id
ORDER BY u.created_at DESC";
$result = $conn->query($sql);
$caregivers = $result->fetch_all(MYSQLI_ASSOC);

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caregivers - Cancer Support Platform</title>
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
                    <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="caregivers.php" class="active"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients & Survivors</a></li>
                    <li><a href="stories.php"><i class="fas fa-book-open"></i> Stories</a></li>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Caregivers Management</h2>
                <a href="#" class="add-new-btn" onclick="openAddCaregiverModal()">
                    <i class="fas fa-plus"></i> Add New Caregiver
                </a>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($caregivers as $caregiver): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($caregiver['first_name'] . ' ' . $caregiver['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($caregiver['email']); ?></td>
                                <td><?php echo htmlspecialchars($caregiver['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($caregiver['specialization'] ?? 'Unspecified'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($caregiver['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button onclick="viewCaregiverDetails(<?php echo $caregiver['caregiver_id']; ?>)" class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editCaregiverDetails(<?php echo $caregiver['caregiver_id']; ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCaregiver(<?php echo $caregiver['caregiver_id']; ?>)" class="delete-btn">
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

    <!-- Add Caregiver Modal -->
    <div id="addCaregiverModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCaregiverModal()">&times;</span>
            <h2>Add New Caregiver</h2>
            <form id="addCaregiverForm" action="../actions/add_caregiver.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number">
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <input type="text" id="specialization" name="specialization" placeholder="e.g., Oncology Nurse, Counselor">
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddCaregiverModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add Caregiver</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddCaregiverModal() {
            document.getElementById('addCaregiverModal').style.display = 'block';
        }

        function closeAddCaregiverModal() {
            document.getElementById('addCaregiverModal').style.display = 'none';
        }

        // CRUD Functions
        function viewCaregiverDetails(caregiverId) {
            window.location.href = `../actions/view_caregiver.php?caregiver_id=${caregiverId}`;
        }

        function editCaregiverDetails(caregiverId) {
            window.location.href = `../actions/edit_caregiver.php?caregiver_id=${caregiverId}`;
        }

        function deleteCaregiver(caregiverId) {
            if (confirm('Are you sure you want to delete this caregiver? This action cannot be undone.')) {
                window.location.href = `../actions/delete_caregiver.php?caregiver_id=${caregiverId}`;
            }
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('addCaregiverModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>