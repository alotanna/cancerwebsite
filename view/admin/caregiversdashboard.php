<?php
// Start the session to access session variables
session_start();

include '../../db/config.php';

// Session validation
if (isset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $user_role = $_SESSION['role'];

    // Ensure only caregivers can access
    if ($user_role !== 'caregiver') {
        header("Location: ../../view/login.html");
        exit();
    }

    // Get caregiver-specific details
    $caregiver_query = "SELECT caregiver_id, specialization 
                        FROM cancer_caregivers 
                        WHERE user_id = ?";
    $stmt = $conn->prepare($caregiver_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $caregiver_result = $stmt->get_result();
    $caregiver_details = $caregiver_result->fetch_assoc();
    $caregiver_id = $caregiver_details['caregiver_id'];
} else {
    header("Location: ../../view/login.html");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Upcoming Appointments with Patients
$sql = "SELECT 
    a.appointment_id,
    a.appointment_date, 
    a.appointment_time, 
    u_patient.first_name as patient_name,
    u_patient.last_name as patient_last_name,
    ct.cancer_type_name
    FROM cancer_appointments a
    JOIN cancer_patients p ON a.patient_id = p.patient_id
    JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
    LEFT JOIN cancer_types ct ON p.cancer_type_id = ct.cancer_type_id
    WHERE a.caregiver_id = ? AND a.status = 'scheduled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caregiver_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Caregiver's Shared Resources
$sql = "SELECT 
    r.resource_id,
    r.title,
    r.resource_type,
    r.created_at
    FROM cancer_resources r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$caregiver_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Caregiver's Resource Counts
$sql = "SELECT COUNT(*) as total_resources 
        FROM cancer_resources 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resource_stats = $stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caregiver Dashboard - Cancer Support Platform</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../../assets/images/samuel.jpeg" alt="Caregiver Avatar" class="user-avatar">
                <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
                <p><?php echo htmlspecialchars($caregiver_details['specialization'] ?? 'General Caregiver'); ?></p>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> My Patients</a></li>
                    <li><a href="share-resource.php"><i class="fas fa-book-medical"></i> Share Resources</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="../../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Welcome, <?php echo $first_name; ?>, to the Cancer Support Platform</h2>
            </div>

            <div class="dashboard-layout">
                <div class="left-column">
                    <div class="analytics-cards">
                        <div class="analytics-card">
                            <h3>Resources Shared</h3>
                            <p class="analytics-number"><?php echo $resource_stats['total_resources']; ?></p>
                        </div>
                    </div>

                    <div class="top-users">
                        <h3>Upcoming Appointments</h3>
                        <ul>
                            <?php if (empty($upcoming_appointments)): ?>
                                <li>No upcoming appointments</li>
                            <?php else: ?>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <li>
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . 
                                                ' at ' . date('h:i A', strtotime($appointment['appointment_time'])) . 
                                                ' - Patient: ' . htmlspecialchars($appointment['patient_name'] . ' ' . $appointment['patient_last_name']) . 
                                                ' (' . htmlspecialchars($appointment['cancer_type_name'] ?? 'No Type') . ')'; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="right-column">
                    <div class="top-users">
                        <h3>My Resources</h3>
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($caregiver_resources)): ?>
                                    <tr>
                                        <td colspan="3">No resources shared yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($caregiver_resources as $resource): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($resource['title'], 0, 20)) . (strlen($resource['title']) > 20 ? '...' : ''); ?></td>
                                            <td><?php echo str_replace('_', ' ', ucwords($resource['resource_type'])); ?></td>
                                            <td><?php echo date('M d', strtotime($resource['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <footer>
                <p>&copy; 2024 Cancer Support Platform. All rights reserved.</p>
            </footer>
        </main>
    </div>
</body>
</html>