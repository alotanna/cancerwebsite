<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healing Cells - Cancer Support Community</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <!--<script src="loginvalidation.js"></script>-->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-logo">
                <span>HEALING<i class='bx bxs-heart-circle'></i>CELLS</span>
            </div>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="indexresources.php">Resources</a>
                <a href="indexstories.php">Stories</a>
                <a href="signup.php">Join Us</a>
            </div>
        </nav> 
    </header>

    <main class="container">
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Welcome to Our Community</h1>
                <p class="welcome-message">A safe space where strength meets support. 
                   Every story matters, every journey is unique.</p>
                
                <div class="feature-grid">
                    <div class="feature-item">
                        <i class='bx bxs-group'></i>
                        <span>Connect with Others</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bxs-book-heart'></i>
                        <span>Share Your Story</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bxs-calendar-heart'></i>
                        <span>Join Events</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bxs-message-rounded-dots'></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-container">
            <div class="login-box">
                <h2>Sign In to Your Account</h2>
                <p class="login-subtitle">Welcome back, we've missed you</p>

                <form id="loginForm" method="POST" action="../actions/login.php" onsubmit="return validateForm(event)">
                    <div class="input-group">
                        <div class="input-field">
                            <input type="email" id="email" name="email" required>
                            <label for="email">Email Address</label>
                            <i class='bx bxs-envelope'></i>
                        </div>
                        <span class="error-message" id="emailError"></span>
                    </div>

                    <div class="input-group">
                        <div class="input-field">
                            <input type="password" id="password" name="password" required>
                            <label for="password">Password</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <span class="error-message" id="passwordError"></span>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">Sign In</button>

                    <div class="register-prompt">
                        <p>New to our community?</p>
                        <a href="signup.php" class="register-link">Join Us</a>
                    </div>

                <div class="help-section">
                    <p>Need assistance? <a href="contact.html">Contact Support</a></p>
                </div>
            </div>
        </div>
    </main>
    <script>
        function validateForm(event) {
            event.preventDefault();

            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Reset error messages
            emailError.style.display = 'none';
            passwordError.style.display = 'none';

            let isValid = true;

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                emailError.textContent = 'Please enter a valid email address.';
                emailError.style.display = 'block';
                isValid = false;
            }

            // Password validation
            const passwordRegex = /^(?=.*[A-Z])(?=.*\d{3,})(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;
            if (!passwordRegex.test(password.value)) {
                passwordError.textContent = 'Password must be at least 8 characters long, contain at least one uppercase, at least three digits, and at least one special character.';
                passwordError.style.display = 'block';
                isValid = false;
            }

            if (isValid) {
                // If all validations pass, submit the form
                document.getElementById('loginForm').submit();
            }

            return false;
        }
    </script>
</body>
</html>