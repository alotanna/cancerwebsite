<?php
// Start the session to access session variables
session_start();

include 'config.php';

// Session validation
if (isset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $user_role = $_SESSION['role'];
} else {
    header("Location: login.html");
    exit();
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Super Admin Statistics
$sql = "SELECT COUNT(*) as total_users FROM Users";
$result = $conn->query($sql);
$total_users = $result->fetch_assoc()['total_users'];

$sql = "SELECT COUNT(*) as total_resources FROM Resources";
$result = $conn->query($sql);
$total_resources = $result->fetch_assoc()['total_resources'];

$sql = "SELECT COUNT(*) as total_events FROM Events WHERE event_date >= CURDATE()";
$result = $conn->query($sql);
$upcoming_events = $result->fetch_assoc()['total_events'];

// Get monthly user registrations
$sql = "SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS user_count 
        FROM Users 
        GROUP BY month 
        ORDER BY created_at";
$user_registration_data = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get cancer type distribution
$sql = "SELECT ct.cancer_type_name, COUNT(u.user_id) as count 
        FROM Cancer_Types ct 
        LEFT JOIN Users u ON ct.cancer_type_id = u.cancer_type_id 
        GROUP BY ct.cancer_type_id";
$cancer_distribution = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get upcoming events
$sql = "SELECT event_title, event_date, location 
        FROM Events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date 
        LIMIT 5";
$upcoming_events_list = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Regular User Statistics
if ($user_role == 2) {
    // Get user's appointments
    $sql = "SELECT a.appointment_date, a.appointment_time, d.first_name as doctor_name, a.location 
            FROM Appointments a 
            LEFT JOIN Doctors d ON a.doctor_id = d.doctor_id 
            WHERE a.user_id = ? AND a.appointment_date >= CURDATE() 
            ORDER BY a.appointment_date, a.appointment_time 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's shared resources
    $sql = "SELECT title, resource_type, created_at 
            FROM Resources 
            WHERE cancer_type_id = (SELECT cancer_type_id FROM Users WHERE user_id = ?) 
            ORDER BY created_at DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's registered events
    $sql = "SELECT e.event_title, e.event_date, e.location 
            FROM Events e 
            JOIN Event_Registrations er ON e.event_id = er.event_id 
            WHERE er.user_id = ? AND e.event_date >= CURDATE() 
            ORDER BY e.event_date 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's recent payments
    $sql = "SELECT payment_type, amount, payment_date, payment_status 
            FROM Payments 
            WHERE user_id = ? 
            ORDER BY payment_date DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html> 
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cancer Support Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Alkatra&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="cancer7.jpg" alt="User Avatar" class="user-avatar">
                <h3><span id="user-name"></span></h3>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php if ($_SESSION['role'] == 1) { ?>
                        <li><a href="users.php"><i class="fas fa-users"></i> User Management</a></li>
                    <?php } ?>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="events.php"><i class="fas fa-calendar-day"></i> Events</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <?php if ($user_role == 1) { ?>
                <div class="welcome-container">
                    <h2>Welcome, Super Admin, to the Cancer Support Platform</h2>
                </div>

                <div class="dashboard-layout">
                    <div class="left-column">
                        <div class="analytics-cards">
                            <div class="analytics-card">
                                <h3>Total Users</h3>
                                <p class="analytics-number"><?php echo $total_users; ?></p>
                            </div>
                            <div class="analytics-card">
                                <h3>Total Resources</h3>
                                <p class="analytics-number"><?php echo $total_resources; ?></p>
                            </div>
                            <div class="analytics-card">
                                <h3>Upcoming Events</h3>
                                <p class="analytics-number"><?php echo $upcoming_events; ?></p>
                            </div>
                        </div>
                        <div class="top-users">
                            <h3>Upcoming Events</h3>
                            <ul>
                                <?php foreach ($upcoming_events_list as $event) { ?>
                                    <li><?php echo $event['event_title'] . ' - ' . $event['event_date'] . ' at ' . $event['location']; ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="piechart">
                            <h3>Cancer Type Distribution</h3>
                            <canvas id="cancerDistributionChart"></canvas>
                        </div>
                    </div>
                    <div class="right-column">
                        <div class="chart-container">
                            <h3>Monthly User Registrations</h3>
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>
                </div>

            <?php } else if ($user_role == 2) { ?>
                <div class="welcome-container">
                    <h2>Welcome to Your Cancer Support Dashboard</h2>
                </div>

                <div class="dashboard-layout">
                    <div class="left-column">
                        <div class="analytics-cards">
                            <div class="analytics-card">
                                <h3>Upcoming Appointments</h3>
                                <p class="analytics-number"><?php echo count($upcoming_appointments); ?></p>
                            </div>
                        </div>
                        <br>
                        <div class="top-users">
                            <h3>Your Next Appointments</h3>
                            <ul>
                                <?php foreach ($upcoming_appointments as $appointment) { ?>
                                    <li>
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . 
                                                ' at ' . date('h:i A', strtotime($appointment['appointment_time'])) . 
                                                ' with Dr. ' . $appointment['doctor_name']; ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <br>
                        <div class="top-users">
                            <h3>Recent Payments</h3>
                            <ul>
                                <?php foreach ($recent_payments as $payment) { ?>
                                    <li>
                                        <?php echo $payment['payment_type'] . ' - $' . $payment['amount'] . 
                                                ' (' . $payment['payment_status'] . ')'; ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <br>
                    <div class="right-column">
                        <div class="top-users">
                            <h3>Your Registered Events</h3>
                            <ul>
                                <?php foreach ($user_events as $event) { ?>
                                    <li><?php echo $event['event_title'] . ' - ' . $event['event_date']; ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <br>
                        <div class="top-users">
                            <h3>Recent Resources Shared</h3>
                            <ul>
                                <?php foreach ($user_resources as $resource) { ?>
                                    <li><?php echo $resource['title'] . ' (' . $resource['resource_type'] . ')'; ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <footer>
                <p>&copy; 2024 Cancer Support Platform. All rights reserved.</p>
            </footer>
        </main>
    </div>

    <script>
        // Set user name
        document.getElementById('user-name').textContent = '<?php echo $first_name . ' ' . $last_name; ?>';

        // Initialize charts based on role
        if (<?php echo $user_role; ?> === 1) {
            // Registration Chart
            createChart('registrationChart', 'bar', {
                labels: <?php echo json_encode(array_column($user_registration_data, 'month')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($user_registration_data, 'user_count')); ?>,
                    backgroundColor: '#f3ada5',
                    borderColor: '#fdca9d',
                    borderWidth: 1
                }]
            });

            // Cancer Distribution Chart
            createChart('cancerDistributionChart', 'pie', {
                labels: <?php echo json_encode(array_column($cancer_distribution, 'cancer_type_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($cancer_distribution, 'count')); ?>,
                    backgroundColor: ['#fdca9d', '#f3ada5', '#E6E6FA', '#9796b0', '#FF6B6B'],
                }]
            });
        }

        // Utility function to create charts
        function createChart(elementId, type, data) {
            const ctx = document.getElementById(elementId);
            if (!ctx) return null;
            
            return new Chart(ctx, {
                type: type,
                data: data,
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    </script>
</body>
</html>