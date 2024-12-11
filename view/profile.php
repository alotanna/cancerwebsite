<?php
// Start the session to access session variables
session_start();

include '../db/config.php';

// Session validation
if (isset($_SESSION['user_id'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $user_role = $_SESSION['role'];

    // Ensure only admin can access
    if ($user_role !== 'admin') {
        header("Location: ../view/login.php");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}

// Fetch full user details
$sql = "SELECT * FROM cancer_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $new_first_name = htmlspecialchars(trim($_POST['first_name']));
    $new_last_name = htmlspecialchars(trim($_POST['last_name']));
    $new_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_phone = htmlspecialchars(trim($_POST['phone']));

    // Prepare update statement
    $update_sql = "UPDATE cancer_users SET 
        first_name = ?, 
        last_name = ?, 
        email = ?, 
        phone_number = ? 
        WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", 
        $new_first_name, 
        $new_last_name, 
        $new_email, 
        $new_phone, 
        $user_id
    );

    if ($update_stmt->execute()) {
        // Update session variables
        $_SESSION['first_name'] = $new_first_name;
        $_SESSION['last_name'] = $new_last_name;
        
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile. Please try again.";
    }

    $update_stmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: var(--shadow-elevated);
            padding: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--gradient-secondary);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin-right: 2rem;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .profile-form .form-group {
            display: flex;
            flex-direction: column;
        }

        .profile-form label {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .profile-form input {
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .profile-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(199, 75, 102, 0.1);
        }

        .full-width {
            grid-column: span 2;
        }

        .submit-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-elevated);
        }

        .alert {
            grid-column: span 2;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
    </style>
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
                    <li><a href="../resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="../appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="profile-container">
                <div class="profile-header">
                    <img src="../../assets/images/austine.jpeg" alt="Admin Avatar" class="profile-avatar">
                    <div>
                        <h2>Admin Profile</h2>
                        <p>Manage your account information</p>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="profile-form">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user_details['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user_details['last_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_details['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="role">Role</label>
                        <input type="text" id="role" name="role" 
                               value="<?php echo ucfirst(htmlspecialchars($user_details['role'])); ?>" readonly>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" class="submit-btn">Save Changes</button>
                    </div>
                </form>
            </div>

            <footer>
                <p>&copy; 2024 Cancer Support Platform. All rights reserved.</p>
            </footer>
        </main>
    </div>
</body>
</html>