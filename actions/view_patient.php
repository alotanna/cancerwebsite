<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../view/login.php");
    exit();
}

// Check if patient_id is provided
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    $_SESSION['message'] = "Invalid patient ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/patients.php");
    exit();
}

$patient_id = intval($_GET['patient_id']);

// Fetch patient details with all related information
$sql = "SELECT 
    p.patient_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    ct.cancer_type_name,
    p.gender,
    p.date_of_birth,
    p.health_condition,
    p.treatment_status,
    p.symptoms,
    p.nutritional_plan,
    p.medications,
    p.emotional_wellbeing,
    p.caregiver_info,
    p.immunotherapy_status,
    p.created_at,
    p.updated_at
FROM cancer_patients p
JOIN cancer_users u ON p.user_id = u.user_id
LEFT JOIN cancer_types ct ON p.cancer_type_id = ct.cancer_type_id
WHERE p.patient_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Patient not found";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/patients.php");
    exit();
}

$patient = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Cancer Support Platform</title>
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
                <h2>Patient Details</h2>
                <a href="../view/patients.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Patients
                </a>
            </div>

            <div class="patient-details-container">
                <div class="patient-info-card">
                    <h3>Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($patient['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <p><?php echo htmlspecialchars($patient['phone_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Gender</label>
                            <p><?php echo htmlspecialchars(ucfirst($patient['gender'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <p><?php echo $patient['date_of_birth'] ? htmlspecialchars(date('M d, Y', strtotime($patient['date_of_birth']))) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="patient-medical-card">
                    <h3>Medical Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Cancer Type</label>
                            <p><?php echo htmlspecialchars($patient['cancer_type_name'] ?? 'Unspecified'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Treatment Status</label>
                            <p><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($patient['treatment_status']))); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Immunotherapy Status</label>
                            <p><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($patient['immunotherapy_status'] ?? 'Not Available'))); ?></p>
                        </div>
                    </div>
                    
                    <div class="medical-details">
                        <div class="info-item full-width">
                            <label>Health Condition</label>
                            <p><?php echo htmlspecialchars($patient['health_condition'] ?? 'No details available'); ?></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Symptoms</label>
                            <p><?php echo htmlspecialchars($patient['symptoms'] ?? 'No symptoms recorded'); ?></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Nutritional Plan</label>
                            <p><?php echo htmlspecialchars($patient['nutritional_plan'] ?? 'No nutritional plan available'); ?></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Medications</label>
                            <p><?php echo htmlspecialchars($patient['medications'] ?? 'No medications recorded'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>