// Helper functions for error handling
function showError(element, message) {
    element.textContent = message;
    element.style.display = 'block';
    element.parentElement.classList.add('error');
}

function hideError(element) {
    element.style.display = 'none';
    element.parentElement.classList.remove('error');
}

function resetErrors() {
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    emailError.style.display = 'none';
    passwordError.style.display = 'none';
    document.querySelectorAll('.input-group').forEach(group => {
        group.classList.remove('error');
    });
}

// Email validation function
function validateEmail() {
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const email = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!email) {
        showError(emailError, 'Please enter your email address');
        return false;
    }
    
    if (!emailRegex.test(email)) {
        showError(emailError, 'Please enter a valid email address');
        return false;
    }

    hideError(emailError);
    return true;
}

// Password validation function
function validatePassword() {
    const passwordInput = document.getElementById('password');
    const passwordError = document.getElementById('passwordError');
    const password = passwordInput.value;
    
    if (!password) {
        showError(passwordError, 'Please enter your password');
        return false;
    }

    if (password.length < 8) {
        showError(passwordError, 'Password must be at least 8 characters long');
        return false;
    }

    if (!/[A-Z]/.test(password)) {
        showError(passwordError, 'Password must contain at least one uppercase letter');
        return false;
    }

    if (!/\d{3,}/.test(password)) {
        showError(passwordError, 'Password must contain at least three numbers');
        return false;
    }

    if (!/[!@#$%^&*]/.test(password)) {
        showError(passwordError, 'Password must contain at least one special character (!@#$%^&*)');
        return false;
    }

    hideError(passwordError);
    return true;
}

// Main form validation function
function validateForm(event) {
    if (event) {
        event.preventDefault();
    }
    
    resetErrors();

    const isEmailValid = validateEmail();
    const isPasswordValid = validatePassword();

    if (isEmailValid && isPasswordValid) {
        const loginForm = document.getElementById('loginForm');
        const submitButton = loginForm.querySelector('.login-btn');
        
        if (submitButton) {
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Signing In...';
            submitButton.disabled = true;
        }
        
        return true;
    }

    return false;
}

// Initialize real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Add real-time validation on input
    emailInput.addEventListener('input', function() {
        if (emailInput.value.trim() !== '') {
            validateEmail();
        } else {
            hideError(document.getElementById('emailError'));
        }
    });

    passwordInput.addEventListener('input', function() {
        if (passwordInput.value !== '') {
            validatePassword();
        } else {
            hideError(document.getElementById('passwordError'));
        }
    });

    // Add validation on blur (when user leaves the field)
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // Add validation on form submission
    loginForm.addEventListener('submit', function(event) {
        return validateForm(event);
    });
});