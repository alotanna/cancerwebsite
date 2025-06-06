<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Our Community - Healing Cells</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <!--<script src="signupvalidation.js"></script>-->
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
                <a href="login.php">Login</a>
            </div>
        </nav>
    </header>

    <main class="container signup-layout">
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Join Our Caring Community</h1>
                <p class="welcome-message">Connect with others who understand your journey. 
                    Share experiences, find support, and grow stronger together.</p>
                
                <div class="community-benefits">
                    <h3>What You'll Get</h3>
                    <ul class="benefits-list">
                        <li>
                            <i class='bx bxs-user-circle'></i>
                            <div>
                                <h4>Personal Profile</h4>
                                <p>Customize your journey and connect with others</p>
                            </div>
                        </li>
                        <li>
                            <i class='bx bxs-group'></i>
                            <div>
                                <h4>Support Groups</h4>
                                <p>Join specialized groups for your specific needs</p>
                            </div>
                        </li>
                        <li>
                            <i class='bx bxs-calendar-heart'></i>
                            <div>
                                <h4>Events & Workshops</h4>
                                <p>Access to both online and local events</p>
                            </div>
                        </li>
                        <li>
                            <i class='bx bxs-book-heart'></i>
                            <div>
                                <h4>Resource Library</h4>
                                <p>Educational materials and helpful guides</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="signup-container">
            <div class="signup-box">
                <h2>Create Your Account</h2>
                <br>
                <p class="signup-subtitle">Join our supportive community today</p>
                <br>

                <form action="../actions/signup.php" id="signupForm" method="POST"  onsubmit="return validateForm(event)">
                    <div class="input-group">
                        <div class="input-field">
                            <input type="text" id="first_name" name="first_name" required>
                            <label for="first_name">First Name</label>
                            <i class='bx bxs-user'></i>
                        </div>
                        <span class="error-message" id="fnameError"></span>
                    </div>

                    <div class="input-group">
                        <div class="input-field">
                            <input type="text" id="last_name" name="last_name" required>
                            <label for="last_name">Last Name</label>
                            <i class='bx bxs-user'></i>
                        </div>
                        <span class="error-message" id="lnameError"></span>
                    </div>

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
                            <button type="button" class="toggle-password" tabindex="-1">
                                <i class='bx bxs-hide'></i>
                            </button>
                        </div>
                        <span class="error-message" id="passwordError"></span>
                        <div class="password-strength">
                            <div class="strength-meter"></div>
                            <span class="strength-text"></span>
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="input-field">
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <label for="confirmPassword">Confirm Password</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <span class="error-message" id="confirmPasswordError"></span>
                    </div>

                    <div class="consent-section">
                        <label class="checkbox-container">
                            <input type="checkbox" id="termsConsent" name="termsConsent" required>
                            <span class="checkmark"></span>
                            <span class="consent-text">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                        </label>
                        <span class="error-message" id="termsError"></span>
                    </div>
                    
                    <button type="submit" class="signup-btn">Create Account</button>
                    <br>
                    <br>
                    <div class="login-prompt">
                        <p>Already have an account? <a href="login.php" class="login-link">Sign In</a></p>
                    </div>
                </form>

                <div class="help-section">
                    <p>Need assistance? <a href="contact.html">Contact Support</a></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Password Strength Meter Code
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthText = document.querySelector('.strength-text');
    
        // Add input event listener to password field
        passwordInput.addEventListener('input', updateStrengthMeter);
    
        function updateStrengthMeter() {
            const password = passwordInput.value;
            let strength = 0;
            let status = '';
    
            // Calculate password strength
            if (password.length >= 8) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[0-9]{3,}/)) strength += 20;
            if (password.match(/[!@#$%^&*]/)) strength += 20;
    
            // Update the strength meter appearance
            
            // Set color based on strength
            if (strength <= 20) {
                strengthMeter.classList.add('weak');
                status = 'Very Weak';
            } else if (strength <= 40) {
                strengthMeter.classList.add('weak');
                status = 'Weak';
            } else if (strength <= 60) {
                strengthMeter.classList.add('medium');
                status = 'Medium';
            } else if (strength <= 80) {
                strengthMeter.classList.add('strong');
                status = 'Strong';
            } else {
                strengthMeter.classList.add('strong');
                status = 'Very Strong';
            }
    
            // Update strength text
            strengthText.textContent = status;
        }
    
        // Add event listener for password visibility toggle
        const togglePassword = document.querySelector('.toggle-password');
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('bxs-hide');
            icon.classList.toggle('bxs-show');
        });
    
        // Your existing form validation function
        function validateForm(event) {
            event.preventDefault();
    
            const email = document.getElementById('email');
            const fname = document.getElementById('first_name');
            const lname = document.getElementById('last_name');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const fnameError = document.getElementById("fnameError");
            const lnameError = document.getElementById("lnameError");
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const confirmPasswordError = document.getElementById('confirmPasswordError');
    
            // Reset error messages
            [fnameError, lnameError, emailError, passwordError, confirmPasswordError].forEach(error => {
                error.style.display = 'none';
            });
    
            let isValid = true;
    
            // First name validation
            if (fname.value.trim() === '') {
                fnameError.textContent = 'First name is required.';
                fnameError.style.display = 'block';
                isValid = false;
            } else if (!/^[a-zA-Z-' ]*$/.test(fname.value.trim())) {
                fnameError.textContent = 'Only letters and white space allowed.';
                fnameError.style.display = 'block';
                isValid = false;
            }
    
            // Last name validation
            if (lname.value.trim() === '') {
                lnameError.textContent = 'Last name is required.';
                lnameError.style.display = 'block';
                isValid = false;
            } else if (!/^[a-zA-Z-' ]*$/.test(lname.value.trim())) {
                lnameError.textContent = 'Only letters and white space allowed.';
                lnameError.style.display = 'block';
                isValid = false;
            }
    
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
                passwordError.textContent = 'Password must be at least 8 characters long, contain at least one uppercase letter, at least three digits, and at least one special character.';
                passwordError.style.display = 'block';
                isValid = false;
            }
    
            // Confirm password validation
            if (password.value !== confirmPassword.value) {
                confirmPasswordError.textContent = 'Passwords do not match.';
                confirmPasswordError.style.display = 'block';
                isValid = false;
            }
    
            if (isValid) {
                // If all validations pass, submit the form
                document.getElementById('signupForm').submit();
            }
    
            return false;
        }
    </script>
</body>
</html>