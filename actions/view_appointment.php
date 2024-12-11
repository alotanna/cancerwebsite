<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../view/login.php");
    exit();
}

// Check if appointment_id is provided
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    $_SESSION['message'] = "Invalid appointment ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/appointments.php");
    exit();
}

$appointment_id = intval($_GET['appointment_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch appointment details with patient, caregiver, and user information
$sql = "SELECT 
    a.appointment_id, 
    a.appointment_date, 
    a.appointment_time, 
    a.notes,
    a.status,
    a.created_at,
    u_patient.first_name AS patient_first_name,
    u_patient.last_name AS patient_last_name,
    u_caregiver.first_name AS caregiver_first_name,
    u_caregiver.last_name AS caregiver_last_name,
    p.patient_id,
    c.caregiver_id
FROM cancer_appointments a
JOIN cancer_patients p ON a.patient_id = p.patient_id
JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
LEFT JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
LEFT JOIN cancer_users u_caregiver ON c.user_id = u_caregiver.user_id
WHERE a.appointment_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Appointment not found";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/appointments.php");
    exit();
}

$appointment = $result->fetch_assoc();
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Cancer Support Platform</title>
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
                <h2>Appointment Details</h2>
                <a href="../view/appointments.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
            </div>

            <div class="patient-details-container">
                <div class="patient-info-card">
                    <h3>Appointment Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Patient</label>
                            <p><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Caregiver</label>
                            <p><?php echo htmlspecialchars($appointment['caregiver_first_name'] . ' ' . $appointment['caregiver_last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Date</label>
                            <p><?php echo htmlspecialchars(date('M d, Y', strtotime($appointment['appointment_date']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Time</label>
                            <p><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($appointment['status']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Scheduled Date</label>
                            <p><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($appointment['created_at']))); ?></p>
                        </div>
                    </div>
                </div>

                <div class="patient-medical-card">
                    <h3>Appointment Notes</h3>
                    <div class="story-content">
                        <p><?php echo !empty($appointment['notes']) ? nl2br(htmlspecialchars($appointment['notes'])) : 'No additional notes'; ?></p>
                    </div>
                </div>

                <?php if ($user_role === 'admin' || 
                          ($user_role === 'patient' && $appointment['status'] !== 'completed') || 
                          $user_role === 'caregiver'): ?>
                <div class="action-container">
                    <?php if ($user_role === 'patient' || $user_role === 'caregiver'): ?>
                        <div class="admin-actions">
                            <a href="../actions/delete_appointment.php?appointment_id=<?php echo $appointment_id; ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this appointment?');">
                                <i class="fas fa-trash"></i> Delete Appointment
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