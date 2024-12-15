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

// Fetch patients with their user details and cancer type
$sql = "SELECT 
    p.patient_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    ct.cancer_type_name,
    p.gender,
    p.immunotherapy_status,
    p.created_at
FROM cancer_patients p
JOIN cancer_users u ON p.user_id = u.user_id
LEFT JOIN cancer_types ct ON p.cancer_type_id = ct.cancer_type_id
ORDER BY p.created_at DESC";
$result = $conn->query($sql);
$patients = $result->fetch_all(MYSQLI_ASSOC);

// Fetch cancer types for dropdown
$cancer_types_sql = "SELECT cancer_type_id, cancer_type_name FROM cancer_types";
$cancer_types_result = $conn->query($cancer_types_sql);
$cancer_types = $cancer_types_result->fetch_all(MYSQLI_ASSOC);

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
    <title>Patients & Survivors - Cancer Support Platform</title>
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
                    <li><a href="caregivers.php"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                    <li><a href="patients.php" class="active"><i class="fas fa-users"></i> Patients & Survivors</a></li>
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
                <h2>Patients & Survivors Management</h2>
                <a href="#" class="add-new-btn" onclick="openAddPatientModal()">
                    <i class="fas fa-plus"></i> Add New Patient
                </a>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Cancer Type</th>
                            <th>Gender</th>
                            <th>Immunotherapy Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($patient['cancer_type_name'] ?? 'Unspecified'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($patient['gender'])); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($patient['immunotherapy_status']))); ?></td>
                                <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button onclick="viewPatientDetails(<?php echo $patient['patient_id']; ?>)" class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editPatientDetails(<?php echo $patient['patient_id']; ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePatient(<?php echo $patient['patient_id']; ?>)" class="delete-btn">
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

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddPatientModal()">&times;</span>
            <h2>Add New Patient</h2>
            <form id="addPatientForm" action="../actions/add_patient.php" method="POST">
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cancer_type_id">Cancer Type</label>
                        <select id="cancer_type_id" name="cancer_type_id">
                            <option value="">Select Cancer Type</option>
                            <?php foreach ($cancer_types as $type): ?>
                                <option value="<?php echo $type['cancer_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['cancer_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="immunotherapy_status">Immunotherapy Status</label>
                        <select id="immunotherapy_status" name="immunotherapy_status" required>
                            <option value="not_started">Not Started</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddPatientModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddPatientModal() {
            document.getElementById('addPatientModal').style.display = 'block';
        }

        function closeAddPatientModal() {
            document.getElementById('addPatientModal').style.display = 'none';
        }

        // CRUD Functions
        function viewPatientDetails(patientId) {
            window.location.href = `../actions/view_patient.php?patient_id=${patientId}`;
        }

        function editPatientDetails(patientId) {
            window.location.href = `../actions/edit_patient.php?patient_id=${patientId}`;
        }

        function deletePatient(patientId) {
            if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                window.location.href = `../actions/delete_patient.php?patient_id=${patientId}`;
            }
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('addPatientModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>