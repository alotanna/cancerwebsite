<?php
// Start the session to access session variables
session_start();

include '../db/config.php';

// Session validation
if (!isset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['role'])) {
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$user_role = $_SESSION['role'];

// Fetch user details based on role
$profile_data = null;
switch ($user_role) {
    case 'admin':
        // For admin, only fetch from users table
        $sql = "SELECT * FROM cancer_users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_data = $stmt->get_result()->fetch_assoc();
        break;

    case 'patient':
        // For patient, fetch from both users and patients tables
        $sql = "SELECT cu.*, cp.*, ct.cancer_type_name 
        FROM cancer_users cu
        JOIN cancer_patients cp ON cu.user_id = cp.user_id
        LEFT JOIN cancer_types ct ON cp.cancer_type_id = ct.cancer_type_id
        WHERE cu.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_data = $stmt->get_result()->fetch_assoc();

        // Fetch all cancer types for dropdown
        $cancer_types_query = "SELECT cancer_type_id, cancer_type_name FROM cancer_types ORDER BY cancer_type_name";
        $cancer_types_result = $conn->query($cancer_types_query);
        break;

    case 'caregiver':
        // For caregiver, fetch from both users and caregivers tables
        $sql = "SELECT cu.*, cc.* 
                FROM cancer_users cu
                JOIN cancer_caregivers cc ON cu.user_id = cc.user_id
                WHERE cu.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_data = $stmt->get_result()->fetch_assoc();
        break;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
    $picture_path = $profile_data['profile_picture'] ?? '';
    
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload_dir = '../../uploads/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        
        // Validate file
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['message'] = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
            $_SESSION['message_type'] = "error";
            header("Location: profile.php");
            exit();
        }

        if ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $_SESSION['message'] = "File size must be less than 5MB";
            $_SESSION['message_type'] = "error";
            header("Location: profile.php");
            exit();
        }

        // Generate unique filename
        $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $target_path = $upload_dir . $filename;

        // Delete old picture if exists and new picture is uploaded
        if (!empty($picture_path) && file_exists($picture_path)) {
            unlink($picture_path);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
            $picture_path = $target_path;
            
            // Update profile picture in database
            $update_pic_sql = "UPDATE cancer_users SET profile_picture = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_pic_sql);
            $update_stmt->bind_param("si", $picture_path, $user_id);
            $update_stmt->execute();
        } else {
            $_SESSION['message'] = "Failed to upload image";
            $_SESSION['message_type'] = "error";
            header("Location: profile.php");
            exit();
        }
    }

    // Update other profile details based on role
    switch ($user_role) {
        case 'admin':
            $sql = "UPDATE cancer_users SET 
                    first_name = ?, 
                    last_name = ?, 
                    phone_number = ? 
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", 
                $_POST['first_name'], 
                $_POST['last_name'], 
                $_POST['phone_number'], 
                $user_id
            );
            break;

        case 'patient':
            // Update users table
            $user_update_sql = "UPDATE cancer_users SET 
            first_name = ?, 
            last_name = ?, 
            phone_number = ? 
            WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_update_sql);
        $user_stmt->bind_param("sssi", 
            $_POST['first_name'], 
            $_POST['last_name'], 
            $_POST['phone_number'], 
            $user_id
        );
        $user_stmt->execute();
    
        // Prepare update for patients table
        $patient_update_sql = "UPDATE cancer_patients SET 
            date_of_birth = ?, 
            gender = ?, 
            cancer_type_id = ?, 
            immunotherapy_status = ?
            WHERE user_id = ?";
        $stmt = $conn->prepare($patient_update_sql);
        $stmt->bind_param("ssssi", 
            $_POST['date_of_birth'], 
            $_POST['gender'], 
            $_POST['cancer_type_id'], 
            $_POST['immunotherapy_status'], 
            $user_id
        );
            break;

        case 'caregiver':
            // Update users table
            $user_update_sql = "UPDATE cancer_users SET 
                    first_name = ?, 
                    last_name = ?, 
                    phone_number = ? 
                    WHERE user_id = ?";
            $user_stmt = $conn->prepare($user_update_sql);
            $user_stmt->bind_param("sssi", 
                $_POST['first_name'], 
                $_POST['last_name'], 
                $_POST['phone_number'], 
                $user_id
            );
            $user_stmt->execute();

            // Update caregivers table
            $sql = "UPDATE cancer_caregivers SET 
                    specialization = ?
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", 
                $_POST['specialization'], 
                $user_id
            );
            break;
    }

    // Execute the main update
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update profile.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="<?php echo !empty($profile_data['profile_picture']) ? $profile_data['profile_picture'] : '../assets/images/defaultuser.jpg'; ?>" alt="User Avatar" class="user-avatar">
                <h3><span id="user-name"><?php echo $first_name . ' ' . $last_name; ?></span></h3>
            </div>
            <nav>
                <ul>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="caregivers.php"><i class="fas fa-user-nurse"></i> Caregivers</a></li>
                        <li><a href="patients.php"><i class="fas fa-users"></i> Patients & Survivors</a></li>
                    <?php endif; ?>
                    <?php if ($user_role === 'patient'): ?>
                        <li><a href="admin/patientdashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user_role === 'caregiver'): ?>
                        <li><a href="admin/caregiversdashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user_role !== 'caregiver'): ?>
                        <li><a href="stories.php"><i class="fas fa-book-open"></i> Stories</a></li>
                    <?php endif; ?>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="profile-container">
                <h2>My Profile</h2>
                
                <?php 
                // Display any session messages
                if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">
                    <div class="profile-picture-upload">
                        <img src="<?php echo !empty($profile_data['profile_picture']) ? $profile_data['profile_picture'] : '../assets/images/defaultuser.jpg'; ?>" alt="Profile Picture" class="profile-preview">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif" class="file-input">
                        <label for="profile_picture" class="file-label">
                            <i class="fas fa-camera"></i> Change Picture
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo $profile_data['first_name']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo $profile_data['last_name']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo $profile_data['email']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" name="phone_number" id="phone_number" value="<?php echo $profile_data['phone_number'] ?? ''; ?>">
                    </div>

                    <?php if ($user_role === 'patient'): ?>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo $profile_data['date_of_birth'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select name="gender" id="gender">
                                <option value="male" <?php echo ($profile_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($profile_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($profile_data['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                        <label for="cancer_type_id">Cancer Type</label>
                        <select name="cancer_type_id" id="cancer_type_id">
                            <option value= 4 >Select a cancer type</option>
                            <?php 
                            while ($cancer_type = $cancer_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $cancer_type['cancer_type_id']; ?>" 
                                    <?php echo ($profile_data['cancer_type_id'] == $cancer_type['cancer_type_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cancer_type['cancer_type_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                        <div class="form-group">
                        <label for="immunotherapy_status">Immunotherapy Status</label>
                        <select name="immunotherapy_status" id="immunotherapy_status">
                            <option value="not_started" <?php echo ($profile_data['immunotherapy_status'] ?? '') == 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                            <option value="ongoing" <?php echo ($profile_data['immunotherapy_status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo ($profile_data['immunotherapy_status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="discontinued" <?php echo ($profile_data['immunotherapy_status'] ?? '') == 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                        </select>
                    </div>

                    <?php endif; ?>

                    <?php if ($user_role === 'caregiver'): ?>
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <input type="text" name="specialization" id="specialization" value="<?php echo $profile_data['specialization'] ?? ''; ?>">
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-update-profile">Update Profile</button>
                    </form>
            </div>

            <footer>
                <p>&copy; 2024 Cancer Support Platform. All rights reserved.</p>
            </footer>
        </main>
    </div>

    <script>
        // Profile picture preview
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('profile_picture');
            const imagePreview = document.querySelector('.profile-preview');

            fileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>