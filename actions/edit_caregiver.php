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

// If form is submitted, process the update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $specialization = $_POST['specialization'] ?? '';

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update user details
        $user_update_sql = "UPDATE cancer_users 
                            SET first_name = ?, last_name = ?, 
                                email = ?, phone_number = ? 
                            WHERE user_id = (SELECT user_id FROM cancer_caregivers WHERE caregiver_id = ?)";
        $user_stmt = $conn->prepare($user_update_sql);
        $user_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone_number, $caregiver_id);
        $user_stmt->execute();

        // Update caregiver details
        $caregiver_update_sql = "UPDATE cancer_caregivers 
                                  SET specialization = ?
                                  WHERE caregiver_id = ?";
        $caregiver_stmt = $conn->prepare($caregiver_update_sql);
        $caregiver_stmt->bind_param("si", $specialization, $caregiver_id);
        $caregiver_stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Caregiver record updated successfully";
        $_SESSION['message_type'] = "success";
        header("Location: ../view/caregivers.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();

        $_SESSION['message'] = "Error updating caregiver: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

// Fetch current caregiver details
$sql = "SELECT 
    c.caregiver_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.phone_number,
    c.specialization
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Caregiver - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="welcome-container">
                <h2>Edit Caregiver Details</h2>
                <a href="../view/caregivers.php" class="add-new-btn">
                    <i class="fas fa-arrow-left"></i> Back to Caregivers
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
                                       value="<?php echo htmlspecialchars($caregiver['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($caregiver['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($caregiver['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="tel" id="phone_number" name="phone_number" 
                                       value="<?php echo htmlspecialchars($caregiver['phone_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="patient-medical-card">
                        <h3>Professional Information</h3>
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <textarea id="specialization" name="specialization" rows="3"><?php echo htmlspecialchars($caregiver['specialization'] ?? ''); ?></textarea>
                            <small>Enter the caregiver's area of expertise, certifications, or specialized skills.</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../view/caregivers.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="submit-btn">Update Caregiver</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>