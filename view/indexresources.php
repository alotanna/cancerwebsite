<?php 
include '../db/config.php';

// Prepare the query for approved resources
$query = "SELECT cr.resource_id, cr.title, cr.content, cr.picture, cr.resource_type,
                 cu.first_name, cu.last_name, ct.cancer_type_name 
          FROM cancer_resources cr 
          JOIN cancer_users cu ON cr.user_id = cu.user_id
          LEFT JOIN cancer_types ct ON cr.cancer_type_id = ct.cancer_type_id
          WHERE cr.status = ?
          ORDER BY cr.created_at DESC";

// Prepare statement
$stmt = $conn->prepare($query);

// Bind parameters
$status = 'approved';
$stmt->bind_param("s", $status);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch all resources
$resources = [];
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

// Close statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancer Resources - HealingCells</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
            max-height: 80%;
            overflow-y: auto;
            padding: 30px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: var(--peach);
        }

        .resources-section {
            background-color: var(--lavender);
            padding: 60px 0;
        }

        .resources-section .section-title {
            color: var(--peach);
            margin-bottom: 30px;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .resource-card {
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .resource-card:hover {
            transform: scale(1.05);
        }

        .resource-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .resource-content {
            padding: 20px;
        }

        .resource-type {
            display: inline-block;
            padding: 5px 10px;
            background-color: var(--peach);
            color: var(--white);
            border-radius: 15px;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .resource-card .read-more {
            color: var(--peach);
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact">
                <span>📞 24/7 Support Hotline: +233 46493 8388</span>
            </div>
            <div class="social-icons">
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-youtube'></i></a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="nav-logo">
                <span>HEALING<i class='bx bxs-heart-circle'></i>CELLS</span>
            </div>
            <nav>
                <div class="nav-links">
                    <a href="../index.php">Home</a>
                    <a href="indexresources.php">Resources</a>
                    <a href="indexstories.php">Stories</a>
                    <a href="login.php">Support</a>
                </div>
                <div class="nav-auth">
                    <a href="login.php">Login</a>
                    <a href="signup.php">Join Us</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Resources Section -->
    <section class="resources-section">
        <div class="container">
            <h2 class="section-title">Cancer Resources</h2>
            <div class="resources-grid">
                <?php foreach($resources as $resource): ?>
                <div class="resource-card">
                    <img src="<?= htmlspecialchars( $resource['picture']) ?? '../assets/images/defaultresource.jpg' ?>" 
                         alt="<?= htmlspecialchars($resource['title']) ?>">
                    <div class="resource-content">
                        <span class="resource-type"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($resource['resource_type']))) ?></span>
                        <h3><?= htmlspecialchars($resource['title']) ?></h3>
                        <p><?= substr(strip_tags($resource['content']), 0, 150) ?>...</p>
                        <a href="#" class="read-more" data-resource-id="<?= $resource['resource_id'] ?>">Read More</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Resource Modal -->
    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div id="resourceModalContent">
                <!-- Resource details will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-main">
                    <div class="footer-brand">
                        <h3>HealingCells</h3>
                        <p>A supportive community dedicated to empowering cancer patients, survivors, and caregivers. Together, we create a space of hope, strength, and healing.</p>
                        <div class="footer-social">
                            <a href="#"><i class='bx bxl-instagram'></i></a>
                            <a href="#"><i class='bx bxl-twitter'></i></a>
                            <a href="#"><i class='bx bxl-facebook'></i></a>
                            <a href="#"><i class='bx bxl-youtube'></i></a>
                        </div>
                    </div>
                    
                    <div class="footer-links">
                        <div class="footer-section">
                            <h3>Quick Links</h3>
                            <ul>
                                <li><a href="#">Resources</a></li>
                                <li><a href="#">Support Groups</a></li>
                                <li><a href="#">Contact Help</a></li>
                                <li><a href="#">Emergency Support</a></li>
                            </ul>
                        </div>
                        
                        <div class="footer-section">
                            <h3>Contact Us</h3>
                            <ul>
                                <li><a href="#">📍 26 God shall save us, Ndimmm Imi state</a></li>
                                <li><a href="tel:2348903900342">📞 24/7 Helpline: (234) 890 5662 342</a></li>
                                <li><a href="mailto:support@healingcells.com">✉️ support@healingcells.com</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>2024 HealingCells. All rights reserved by Austine Omo Naija.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('resourceModal');
        const modalContent = document.getElementById('resourceModalContent');
        const closeModal = document.querySelector('.modal-close');
        const readMoreButtons = document.querySelectorAll('.read-more');

        // Load resource details via AJAX
        readMoreButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const resourceId = this.getAttribute('data-resource-id');

                fetch(`functions/get_resource_details.php?resource_id=${resourceId}`)
                    .then(response => response.text())
                    .then(data => {
                        modalContent.innerHTML = data;
                        modal.style.display = 'flex';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Could not load resource details.');
                    });
            });
        });

        // Close modal
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>