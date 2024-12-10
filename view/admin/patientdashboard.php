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

    // Ensure only patients can access
    if ($user_role !== 'patient') {
        header("Location: ../../view/login.html");
        exit();
    }

    // Get patient-specific details
    $patient_query = "SELECT p.patient_id, p.cancer_type_id, ct.cancer_type_name 
                      FROM cancer_patients p
                      LEFT JOIN cancer_types ct ON p.cancer_type_id = ct.cancer_type_id
                      WHERE p.user_id = ?";
    $stmt = $conn->prepare($patient_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    $patient_details = $patient_result->fetch_assoc();
    $patient_id = $patient_details['patient_id'];
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

// Upcoming Appointments
$sql = "SELECT 
    a.appointment_id,
    a.appointment_date, 
    a.appointment_time, 
    u_caregiver.first_name as caregiver_name 
    FROM cancer_appointments a
    LEFT JOIN cancer_caregivers cg ON a.caregiver_id = cg.caregiver_id
    LEFT JOIN cancer_users u_caregiver ON cg.user_id = u_caregiver.user_id
    WHERE a.patient_id = ? AND a.status = 'scheduled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Patient's Resources
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
$patient_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Patient's Stories
$sql = "SELECT 
    s.story_id,
    s.title,
    ct.cancer_type_name,
    s.created_at
    FROM cancer_stories s
    LEFT JOIN cancer_types ct ON s.cancer_type_id = ct.cancer_type_id
    WHERE s.patient_id = ?
    ORDER BY s.created_at DESC
    LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient_stories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Patient's Resource and Story Counts
$sql = "SELECT 
    (SELECT COUNT(*) FROM cancer_resources WHERE user_id = ?) as total_resources,
    (SELECT COUNT(*) FROM cancer_stories WHERE patient_id = ?) as total_stories";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $patient_id);
$stmt->execute();
$resource_story_stats = $stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Cancer Support Platform</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../../assets/images/mcnobert.jpg" alt="Patient Avatar" class="user-avatar">
                <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
                <p><?php echo htmlspecialchars($patient_details['cancer_type_name'] ?? 'No Cancer Type'); ?></p>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a></li>
                    <li><a href="share-story.php"><i class="fas fa-book-open"></i> Share My Story</a></li>
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
                            <h3>My Resources</h3>
                            <p class="analytics-number"><?php echo $resource_story_stats['total_resources']; ?></p>
                        </div>
                        <div class="analytics-card">
                            <h3>My Stories</h3>
                            <p class="analytics-number"><?php echo $resource_story_stats['total_stories']; ?></p>
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
                                                ' - Caregiver: ' . ($appointment['caregiver_name'] ?? 'Not Assigned'); ?>
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
                                <?php if (empty($patient_resources)): ?>
                                    <tr>
                                        <td colspan="3">No resources shared yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patient_resources as $resource): ?>
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

                    <div class="top-users">
                        <h3>My Stories</h3>
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Cancer Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patient_stories)): ?>
                                    <tr>
                                        <td colspan="3">No stories shared yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patient_stories as $story): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($story['title'], 0, 20)) . (strlen($story['title']) > 20 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($story['cancer_type_name'] ?? 'Unspecified'); ?></td>
                                            <td><?php echo date('M d', strtotime($story['created_at'])); ?></td>
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