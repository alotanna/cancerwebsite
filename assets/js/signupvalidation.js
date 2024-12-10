document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    const inputs = {
        first_name: document.getElementById('first_name'),
        last_name: document.getElementById('last_name'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        confirmPassword: document.getElementById('confirmPassword'),
        termsConsent: document.getElementById('termsConsent')
    };
    const errors = {
        fnameError: document.getElementById('fnameError'),
        lnameError: document.getElementById('lnameError'),
        emailError: document.getElementById('emailError'),
        passwordError: document.getElementById('passwordError'),
        confirmPasswordError: document.getElementById('confirmPasswordError'),
        termsError: document.getElementById('termsError')
    };

    // Validation rules
    const validators = {
        first_name: (value) => {
            if (!value.trim()) return 'First name is required';
            if (value.trim().length < 2) return 'First name must be at least 2 characters long';
            if (!/^[a-zA-Z\s-']+$/.test(value.trim())) return 'Name can only contain letters, spaces, hyphens, and apostrophes';
            return '';
        },
        last_name: (value) => {
            if (!value.trim()) return 'Last name is required';
            if (value.trim().length < 2) return 'Last name must be at least 2 characters long';
            if (!/^[a-zA-Z\s-']+$/.test(value.trim())) return 'Name can only contain letters, spaces, hyphens, and apostrophes';
            return '';
        },
        email: (value) => {
            if (!value.trim()) return 'Email is required';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) return 'Please enter a valid email address';
            return '';
        },
        password: (value) => {
            if (!value) return 'Password is required';
            if (value.length < 8) return 'Password must be at least 8 characters long';
            if (!/(?=.*[A-Z])/.test(value)) return 'Password must include at least one uppercase letter';
            if (!/(?=.*\d{3,})/.test(value)) return 'Password must include at least three numbers';
            if (!/(?=.*[!@#$%^&*])/.test(value)) return 'Password must include at least one special character (!@#$%^&*)';
            return '';
        },
        confirmPassword: (value) => {
            if (!value) return 'Please confirm your password';
            if (value !== inputs.password.value) return 'Passwords do not match';
            return '';
        },
        termsConsent: (checked) => {
            return checked ? '' : 'You must agree to the Terms of Service and Privacy Policy';
        }
    };

    // Password visibility toggle
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = inputs.password;
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.innerHTML = type === 'password' ? 
                '<i class="bx bxs-hide"></i>' : 
                '<i class="bx bxs-show"></i>';
        });
    }

    // Show/hide error messages
    function showError(errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function hideError(errorElement) {
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    // Validate individual field
    function validateField(fieldName) {
        const input = inputs[fieldName];
        const errorElement = errors[`${fieldName.replace('first_name', 'fname').replace('last_name', 'lname')}Error`];
        
        if (!input || !errorElement) return true;
        
        const value = fieldName === 'termsConsent' ? input.checked : input.value;
        const errorMessage = validators[fieldName]?.(value);
        
        if (errorMessage) {
            showError(errorElement, errorMessage);
            return false;
        } else {
            hideError(errorElement);
            return true;
        }
    }

    // Password strength indicator
    function updatePasswordStrength(password) {
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthText = document.querySelector('.strength-text');
        
        if (!password) {
            strengthMeter.className = 'strength-meter';
            strengthText.textContent = '';
            return;
        }

        let strength = 0;
        if (password.length >= 8) strength++;
        if (/(?=.*[A-Z])/.test(password)) strength++;
        if (/(?=.*\d{3,})/.test(password)) strength++;
        if (/(?=.*[!@#$%^&*])/.test(password)) strength++;

        strengthMeter.className = 'strength-meter';
        switch (true) {
            case (strength <= 2):
                strengthMeter.classList.add('weak');
                strengthText.textContent = 'Weak password';
                break;
            case (strength === 3):
                strengthMeter.classList.add('medium');
                strengthText.textContent = 'Medium password';
                break;
            case (strength === 4):
                strengthMeter.classList.add('strong');
                strengthText.textContent = 'Strong password';
                break;
        }
    }

    // Real-time validation
    Object.keys(inputs).forEach(key => {
        if (inputs[key]) {
            inputs[key].addEventListener('input', () => {
                validateField(key);
                if (key === 'password') {
                    updatePasswordStrength(inputs[key].value);
                    if (inputs.confirmPassword.value) {
                        validateField('confirmPassword');
                    }
                }
            });
        }
    });

    // Form submission
    if (signupForm) {
        signupForm.addEventListener('submit', async function(event) {
            // Don't prevent default form submission - let the form submit normally to the server
            
            // Validate all fields
            let isValid = true;
            Object.keys(inputs).forEach(key => {
                if (!validateField(key)) {
                    isValid = false;
                }
            });



            // If validation fails, prevent form submission
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});