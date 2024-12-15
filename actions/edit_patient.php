<?php
session_start();
include '../db/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../view/login.php");
    exit();
}

// Fetch cancer types for dropdown
$cancer_types_sql = "SELECT cancer_type_id, cancer_type_name FROM cancer_types";
$cancer_types_result = $conn->query($cancer_types_sql);
$cancer_types = $cancer_types_result->fetch_all(MYSQLI_ASSOC);

// Check if patient_id is provided
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    $_SESSION['message'] = "Invalid patient ID";
    $_SESSION['message_type'] = "error";
    header("Location: ../view/patients.php");
    exit();
}

$patient_id = intval($_GET['patient_id']);

// If form is submitted, process the update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $cancer_type_id = $_POST['cancer_type_id'] ?? null;
    $immunotherapy_status = $_POST['immunotherapy_status'] ?? '';

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update user details
        $user_update_sql = "UPDATE cancer_users 
                            SET first_name = ?, last_name = ?, 
                                email = ?, phone_number = ? 
                            WHERE user_id = (SELECT user_id FROM cancer_patients WHERE patient_id = ?)";
        $user_stmt = $conn->prepare($user_update_sql);
        $user_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone_number, $patient_id);
        $user_stmt->execute();

        // Update patient details
        $patient_update_sql = "UPDATE cancer_patients 
                                SET gender = ?, date_of_birth = ?, cancer_type_id = ?,  immunotherapy_status = ?
                                WHERE patient_id = ?";
        $patient_stmt = $conn->prepare($patient_update_sql);
        $patient_stmt->bind_param("ssssi", 
            $gender, $date_of_birth, $cancer_type_id, 
            $immunotherapy_status, $patient_id
        );
        $patient_stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Patient record updated successfully";
        $_SESSION['message_type'] = "success";
        header("Location: ../view/patients.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();

        $_SESSION['message'] = "Error updating patient: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

// Fetch current patient details
$sql = "SELECT 
    p.patient_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    p.gender,
    p.date_of_birth,
    p.cancer_type_id,
    p.immunotherapy_status
FROM cancer_patients p
JOIN cancer_users u ON p.user_id = u.user_id
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Edit Patient Details</h2>
                <a href="../view/patients.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Patients
                </a>
            </div>

            <div class="patient-details-container">
                <form action="" method="POST">
                    <div class="patient-info-card">
                        <h3>Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="tel" id="phone_number" name="phone_number" 
                                       value="<?php echo htmlspecialchars($patient['phone_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="male" <?php echo $patient['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $patient['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $patient['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="patient-medical-card">
                        <h3>Medical Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cancer_type_id">Cancer Type</label>
                                <select id="cancer_type_id" name="cancer_type_id">
                                    <option value="">Select Cancer Type</option>
                                    <?php foreach ($cancer_types as $type): ?>
                                        <option value="<?php echo $type['cancer_type_id']; ?>"
                                            <?php echo $patient['cancer_type_id'] == $type['cancer_type_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['cancer_type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                        <div class="form-group">
                            <label for="immunotherapy_status">Immunotherapy Status</label>
                            <select id="immunotherapy_status" name="immunotherapy_status">
                                <option value="not_started" <?php echo $patient['immunotherapy_status'] === 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                                <option value="ongoing" <?php echo $patient['immunotherapy_status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $patient['immunotherapy_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="discontinued" <?php echo $patient['immunotherapy_status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                            </select>
                        </div>

                    <div class="form-actions">
                        <a href="../view/patients.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="submit-btn">Update Patient</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>