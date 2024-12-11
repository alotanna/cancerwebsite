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
} else {
    header("Location: login.php");
    exit();
}

// Fetch appointments based on user role
switch ($user_role) {
    case 'admin':
        // Fetch all appointments
        $appointments_sql = "SELECT 
            a.appointment_id, 
            u_patient.first_name AS patient_first_name, 
            u_patient.last_name AS patient_last_name,
            u_caregiver.first_name AS caregiver_first_name, 
            u_caregiver.last_name AS caregiver_last_name,
            a.appointment_date, 
            a.appointment_time, 
            a.status,
            a.notes
        FROM cancer_appointments a
        JOIN cancer_patients p ON a.patient_id = p.patient_id
        JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
        LEFT JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
        LEFT JOIN cancer_users u_caregiver ON c.user_id = u_caregiver.user_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        break;

    case 'patient':
        // Fetch appointments for specific patient
        $appointments_sql = "SELECT 
            a.appointment_id, 
            u_caregiver.first_name AS caregiver_first_name, 
            u_caregiver.last_name AS caregiver_last_name,
            a.appointment_date, 
            a.appointment_time, 
            a.status,
            a.notes
        FROM cancer_appointments a
        JOIN cancer_patients p ON a.patient_id = p.patient_id
        JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
        LEFT JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
        LEFT JOIN cancer_users u_caregiver ON c.user_id = u_caregiver.user_id
        WHERE u_patient.user_id = '$user_id'
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        break;

    case 'caregiver':
        // Fetch appointments for specific caregiver
        $appointments_sql = "SELECT 
            a.appointment_id, 
            u_patient.first_name AS patient_first_name, 
            u_patient.last_name AS patient_last_name,
            a.appointment_date, 
            a.appointment_time, 
            a.status,
            a.notes
        FROM cancer_appointments a
        JOIN cancer_patients p ON a.patient_id = p.patient_id
        JOIN cancer_users u_patient ON p.user_id = u_patient.user_id
        JOIN cancer_caregivers c ON a.caregiver_id = c.caregiver_id
        JOIN cancer_users u_caregiver ON c.user_id = u_caregiver.user_id
        WHERE u_caregiver.user_id = '$user_id'
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        break;
}

$appointments_result = $conn->query($appointments_sql);
$appointments = $appointments_result->fetch_all(MYSQLI_ASSOC);

// Fetch patients for admin and caregiver appointment creation
if ($user_role === 'admin' || $user_role === 'caregiver') {
    $patients_sql = "SELECT 
        p.patient_id, 
        u.first_name, 
        u.last_name 
    FROM cancer_patients p
    JOIN cancer_users u ON p.user_id = u.user_id";
    $patients_result = $conn->query($patients_sql);
    $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch caregivers for admin and patient appointment creation
if ($user_role === 'admin' || $user_role === 'patient') {
    $caregivers_sql = "SELECT 
        c.caregiver_id, 
        u.first_name, 
        u.last_name 
    FROM cancer_caregivers c
    JOIN cancer_users u ON c.user_id = u.user_id";
    $caregivers_result = $conn->query($caregivers_sql);
    $caregivers = $caregivers_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Cancer Support Platform</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../assets/images/austine.jpeg" alt="User Avatar" class="user-avatar">
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
                    <li><a href="stories.php"><i class="fas fa-book-open"></i> Stories Shared</a></li>
                    <li><a href="resources.php"><i class="fas fa-book-medical"></i> Resources</a></li>
                    <li><a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="welcome-container">
                <h2>Appointments</h2>
                    <a href="#" class="add-new-btn" onclick="openAddAppointmentModal()">
                        <i class="fas fa-plus"></i> Add New Appointment
                    </a>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <?php if ($user_role === 'admin'): ?>
                                <th>Patient</th>
                                <th>Caregiver</th>
                            <?php elseif ($user_role === 'patient'): ?>
                                <th>Caregiver</th>
                            <?php else: ?>
                                <th>Patient</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <?php if ($user_role === 'admin'): ?>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['caregiver_first_name'] . ' ' . $appointment['caregiver_last_name']); ?></td>
                                <?php elseif ($user_role === 'patient'): ?>
                                    <td><?php echo htmlspecialchars($appointment['caregiver_first_name'] . ' ' . $appointment['caregiver_last_name']); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($appointment['status']))); ?></td>
                                <td class="action-buttons">
                                    <button onclick="viewAppointmentDetails(<?php echo $appointment['appointment_id']; ?>)" class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php 
                                    $canEdit = false;
                                    if ($user_role === 'patient' && $appointment['status'] !== 'completed') {
                                        $canEdit = true;
                                    } elseif ($user_role === 'caregiver') {
                                        $canEdit = true;
                                    }
                                    ?>

                                    <?php if ($canEdit): ?>
                                        <button onclick="editAppointmentDetails(<?php echo $appointment['appointment_id']; ?>)" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (($user_role === 'patient' && $appointment['status'] !== 'completed') || 
                                              $user_role === 'caregiver'|| 
                                              $user_role === 'admin' ): ?>
                                        <button onclick="deleteAppointment(<?php echo $appointment['appointment_id']; ?>)" class="delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Appointment Modal -->

    <div id="addAppointmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddAppointmentModal()">&times;</span>
            <h2>Schedule New Appointment</h2>
            <form id="addAppointmentForm" action="../actions/add_appointment.php" method="POST">
                <?php if ($user_role === 'admin' || $user_role == 'caregiver'): ?>
                    <div class="form-group">
                        <label for="patient_id">Patient</label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($user_role === 'patient' || $user_role === 'admin'): ?>
                    <div class="form-group">
                        <label for="caregiver_id">Caregiver</label>
                        <select id="caregiver_id" name="caregiver_id" required>
                            <option value="">Select Caregiver</option>
                            <?php foreach ($caregivers as $caregiver): ?>
                                <option value="<?php echo $caregiver['caregiver_id']; ?>">
                                    <?php echo htmlspecialchars($caregiver['first_name'] . ' ' . $caregiver['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Date</label>
                        <input type="date" id="appointment_date" name="appointment_date" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time</label>
                        <input type="time" id="appointment_time" name="appointment_time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddAppointmentModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>

<!-- Edit Appointment Modal -->
<div id="editAppointmentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditAppointmentModal()">&times;</span>
        <h2>Edit Appointment</h2>
        <form id="editAppointmentForm" action="../actions/update_appointment.php" method="POST">
            <input type="hidden" id="edit_appointment_id" name="appointment_id">

            <?php if ($user_role === 'admin' || $user_role == 'caregiver'): ?>
                <div class="form-group">
                    <label for="edit_patient_id">Patient</label>
                    <select id="edit_patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>">
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($user_role === 'patient' || $user_role === 'admin'): ?>
                <div class="form-group">
                    <label for="edit_caregiver_id">Caregiver</label>
                    <select id="edit_caregiver_id" name="caregiver_id" required>
                        <option value="">Select Caregiver</option>
                        <?php foreach ($caregivers as $caregiver): ?>
                            <option value="<?php echo $caregiver['caregiver_id']; ?>">
                                <?php echo htmlspecialchars($caregiver['first_name'] . ' ' . $caregiver['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_appointment_date">Date</label>
                    <input type="date" id="edit_appointment_date" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label for="edit_appointment_time">Time</label>
                    <input type="time" id="edit_appointment_time" name="appointment_time" required>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_notes">Notes (Optional)</label>
                <textarea id="edit_notes" name="notes" rows="4"></textarea>
            </div>

            <?php if ($user_role === 'admin' || $user_role === 'caregiver'): ?>
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="canceled">Canceled</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeEditAppointmentModal()">Cancel</button>
                <button type="submit" class="submit-btn">Update Appointment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editAppointmentDetails(appointmentId) {
        // AJAX call to fetch appointment details
        fetch(`../actions/get_appointment_details.php?appointment_id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                // Populate edit modal with appointment details
                document.getElementById('edit_appointment_id').value = appointmentId;
                
                <?php if ($user_role === 'admin' || $user_role == 'caregiver'): ?>
                if (document.getElementById('edit_patient_id')) {
                    document.getElementById('edit_patient_id').value = data.patient_id;
                }
                <?php endif; ?>

                <?php if ($user_role === 'patient' || $user_role === 'admin'): ?>
                if (document.getElementById('edit_caregiver_id')) {
                    document.getElementById('edit_caregiver_id').value = data.caregiver_id;
                }
                <?php endif; ?>

                document.getElementById('edit_appointment_date').value = data.appointment_date;
                document.getElementById('edit_appointment_time').value = data.appointment_time;
                document.getElementById('edit_notes').value = data.notes;

                <?php if ($user_role === 'admin' || $user_role === 'caregiver'): ?>
                if (document.getElementById('edit_status')) {
                    document.getElementById('edit_status').value = data.status;
                }
                <?php endif; ?>

                // Open the edit modal
                document.getElementById('editAppointmentModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load appointment details');
            });
    }

        function closeEditAppointmentModal() {
            document.getElementById('editAppointmentModal').style.display = 'none';
        }
        // Modal Functions
        function openAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'block';
        }

        function closeAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'none';
        }

        // CRUD Functions
        function viewAppointmentDetails(appointmentId) {
            window.location.href = `../actions/view_appointment.php?appointment_id=${appointmentId}`;
        }

        function deleteAppointment(appointmentId) {
            if (confirm('Are you sure you want to delete this appointment? This action cannot be undone.')) {
                window.location.href = `../actions/delete_appointment.php?appointment_id=${appointmentId}`;
            }
        }
    </script>
</body>
</html>
                