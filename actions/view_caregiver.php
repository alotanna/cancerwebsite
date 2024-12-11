<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../view/login.php");
    exit();
}

// Check if caregiver_id is provided
if (!isset($_GET['caregiver_id']) || !is_numeric($_GET['caregiver_id'])) {
    $_SESSION['message'] = "Invalid caregiver ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/caregivers.php");
    exit();
}

$caregiver_id = intval($_GET['caregiver_id']);

// Fetch caregiver details with all related information
$sql = "SELECT 
    c.caregiver_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    c.specialization,
    u.profile_picture,
    u.created_at AS user_created_at,
    c.created_at AS caregiver_created_at
FROM cancer_caregivers c
JOIN cancer_users u ON c.user_id = u.user_id
WHERE c.caregiver_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caregiver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Caregiver not found";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/caregivers.php");
    exit();
}

$caregiver = $result->fetch_assoc();
$stmt->close();

// Fetch caregiver's appointments
$appointments_sql = "SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    p.patient_id,
    CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name
FROM cancer_appointments a
JOIN cancer_patients p ON a.patient_id = p.patient_id
JOIN cancer_users pu ON p.user_id = pu.user_id
WHERE a.caregiver_id = ?
ORDER BY a.appointment_date DESC";

$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $caregiver_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
$appointments = $appointments_result->fetch_all(MYSQLI_ASSOC);
$appointments_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caregiver Details - Cancer Support Platform</title>
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
                <h2>Caregiver Details</h2>
                <a href="../view/caregivers.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Caregivers
                </a>
            </div>

            <div class="patient-details-container">
                <div class="patient-info-card">
                    <h3>Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($caregiver['first_name'] . ' ' . $caregiver['last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($caregiver['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <p><?php echo htmlspecialchars($caregiver['phone_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Specialization</label>
                            <p><?php echo htmlspecialchars($caregiver['specialization'] ?? 'Not Specified'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Registered On</label>
                            <p><?php echo htmlspecialchars(date('M d, Y', strtotime($caregiver['caregiver_created_at']))); ?></p>
                        </div>
                    </div>
                </div>

                <div class="patient-medical-card">
                    <h3>Appointment History</h3>
                    <?php if (!empty($appointments)): ?>
                        <table class="patients-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($appointment['appointment_date']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($appointment['status']))); ?></td>
                                        <td class="action-buttons">
                                            <a href="view_patient.php?patient_id=<?php echo $appointment['patient_id']; ?>" class="view-btn">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No appointments found for this caregiver.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>