-- Create the database
CREATE DATABASE IF NOT EXISTS cancer_website;

USE cancer_website;

-- Create Cancer Types Table
CREATE TABLE cancer_types (
    cancer_type_id INT AUTO_INCREMENT PRIMARY KEY,
    cancer_type_name VARCHAR(100) NOT NULL UNIQUE
);

-- Users Table with ENUM for role
CREATE TABLE cancer_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15),
    profile_picture VARCHAR(255),
    role ENUM('admin', 'caregiver', 'patient') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Combined Patients Table with Comprehensive Information
CREATE TABLE cancer_patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other') NOT NULL,
    cancer_type_id INT,
    
    health_condition VARCHAR(255),
    treatment_status ENUM('initial_diagnosis', 'in_treatment', 'post_treatment', 'remission', 'palliative_care'),
    symptoms TEXT,
    nutritional_plan TEXT,
    medications TEXT,
    emotional_wellbeing TEXT,
    caregiver_info TEXT,
    immunotherapy_status ENUM('not_started', 'ongoing', 'completed', 'discontinued'),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Caregivers Table
CREATE TABLE cancer_caregivers (
    caregiver_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialization VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Appointments Table
CREATE TABLE cancer_appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    caregiver_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    status ENUM('scheduled', 'completed', 'canceled', 'pending'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES cancer_patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (caregiver_id) REFERENCES cancer_caregivers(caregiver_id) ON DELETE SET NULL
);

-- Stories Table
CREATE TABLE cancer_stories (
    story_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    picture VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES cancer_patients(patient_id) ON DELETE CASCADE
);

-- Resources Table
CREATE TABLE cancer_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    cancer_type_id INT,
    resource_type ENUM('lifestyle', 'knowledge_sharing', 'nutrition', 'mental_health', 'exercise', 'support_groups', 'treatment_tips'),
    content TEXT,
    picture VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Payments Table
CREATE TABLE cancer_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    payment_status ENUM('pending', 'completed', 'failed'),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(255) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES cancer_appointments(appointment_id) ON DELETE CASCADE
);