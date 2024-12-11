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

    // Ensure only admin can access
    if ($user_role !== 'admin') {
        header("Location: ../../view/login.php");
        exit();
    }
} else {
    header("Location: ../../view/login.php");
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

// Total Users Statistics
$sql = "SELECT 
    COUNT(*) as total_users, 
    SUM(CASE WHEN role = 'patient' THEN 1 ELSE 0 END) as total_patients,
    SUM(CASE WHEN role = 'caregiver' THEN 1 ELSE 0 END) as total_caregivers
    FROM cancer_users";
$result = $conn->query($sql);
$user_stats = $result->fetch_assoc();

// Recent Appointments
$sql = "SELECT 
    a.appointment_date, 
    a.appointment_time, 
    u_patient.first_name as patient_name, 
    u_caregiver.first_name as caregiver_name 
    FROM cancer_appointments a
    JOIN cancer_patients p ON a.patient_id = p.patient_id
    JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
    LEFT JOIN cancer_caregivers cg ON a.caregiver_id = cg.caregiver_id
    LEFT JOIN cancer_users u_caregiver ON cg.user_id = u_caregiver.user_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5";
$recent_appointments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Resources Shared
//   WHERE r.status = 'approved'
$sql = "SELECT 
    r.resource_id,
    r.title,
    r.resource_type,
    u.first_name AS author_name,
    r.created_at
    FROM cancer_resources r
    JOIN cancer_users u ON r.user_id = u.user_id

    ORDER BY r.created_at DESC
    LIMIT 5";
$recent_resources = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Recent Stories Shared
//WHERE s.status = 'approved'
$sql = "SELECT 
    s.story_id,
    s.title,
    u.first_name AS author_name,
    s.created_at
    FROM cancer_stories s
    JOIN cancer_patients p ON s.patient_id = p.patient_id
    JOIN cancer_users u ON p.user_id = u.user_id
    ORDER BY s.created_at DESC
    LIMIT 5";
$recent_stories = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Resources and Stories Statistics
//WHERE status = 'approved'
//WHERE status = 'approved'
$sql = "SELECT 
    (SELECT COUNT(*) FROM cancer_resources ) as total_resources,
    (SELECT COUNT(*) FROM cancer_stories ) as total_stories";
$resource_story_stats = $conn->query($sql)->fetch_assoc();

// Cancer Type Distribution
$sql = "SELECT 
    ct.cancer_type_name, 
    COUNT(p.patient_id) as patient_count 
    FROM cancer_types ct 
    LEFT JOIN cancer_patients p ON ct.cancer_type_id = p.cancer_type_id 
    GROUP BY ct.cancer_type_id
    ORDER BY patient_count DESC";
$cancer_distribution = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cancer Support Platform</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../../assets/images/austine.jpeg" alt="Admin Avatar" class="user-avatar">
                <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="../caregivers.php"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                    <li><a href="../patients.php"><i class="fas fa-users"></i> Patients & Survivors</a></li>
                    <li><a href="../stories.php"><i class="fas fa-book-open"></i> Stories Shared</a></li>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Welcome, Admin, to the Cancer Support Platform</h2>
            </div>

            <div class="dashboard-layout">
                <div class="left-column">
                    <div class="analytics-cards">
                        <div class="analytics-card">
                            <h3>Total Users</h3>
                            <p class="analytics-number"><?php echo $user_stats['total_users']; ?></p>
                        </div>
                        <div class="analytics-card">
                            <h3>Patients</h3>
                            <p class="analytics-number"><?php echo $user_stats['total_patients']; ?></p>
                        </div>
                        <div class="analytics-card">
                            <h3>Caregivers</h3>
                            <p class="analytics-number"><?php echo $user_stats['total_caregivers']; ?></p>
                        </div>
                    </div>

                    <div class="top-users">
                        <h3>Recent Appointments</h3>
                        <ul>
                            <?php foreach ($recent_appointments as $appointment): ?>
                                <li>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . 
                                            ' at ' . date('h:i A', strtotime($appointment['appointment_time'])) . 
                                            ' - Patient: ' . $appointment['patient_name'] . 
                                            ' (Caregiver: ' . ($appointment['caregiver_name'] ?? 'Not Assigned') . ')'; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="piechart">
                        <h3>Patient Cancer Type Distribution</h3>
                        <canvas id="cancerDistributionChart"></canvas>
                    </div>
                </div>

                <div class="right-column">
                    <div class="top-users">
                        <h3>Resources Shared</h3>
                        <div class="analytics-card">
                            <h3>Total Resources</h3>
                            <p class="analytics-number"><?php echo $resource_story_stats['total_resources']; ?></p>
                        </div>
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Author</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_resources as $resource): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($resource['title'], 0, 20)) . (strlen($resource['title']) > 20 ? '...' : ''); ?></td>
                                        <td><?php echo str_replace('_', ' ', ucwords($resource['resource_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($resource['author_name']); ?></td>
                                        <td><?php echo date('M d', strtotime($resource['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="top-users">
                        <h3>Stories Shared</h3>
                        <div class="analytics-card">
                            <h3>Total Stories</h3>
                            <p class="analytics-number"><?php echo $resource_story_stats['total_stories']; ?></p>
                        </div>
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_stories as $story): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($story['title'], 0, 20)) . (strlen($story['title']) > 20 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($story['author_name']); ?></td>
                                        <td><?php echo date('M d', strtotime($story['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
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

    <script>
        // Cancer Distribution Chart
        function createPieChart(elementId, labels, data) {
            const ctx = document.getElementById(elementId);
            if (!ctx) return;

            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($cancer_distribution, 'cancer_type_name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($cancer_distribution, 'patient_count')); ?>,
                        backgroundColor: [
                            '#fdca9d', '#f3ada5', '#E6E6FA', 
                            '#9796b0', '#FF6B6B', '#4CAF50', 
                            '#2196F3'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Patient Cancer Type Distribution'
                        }
                    }
                }
            });
        }

        // Create chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            createPieChart('cancerDistributionChart');
        });
    </script>
</body>
</html>