-- Drop the database if it exists
DROP DATABASE IF EXISTS cancer_website;

-- Create the database
CREATE DATABASE IF NOT EXISTS cancer_website;

USE cancer_website;

-- Create Cancer Types Table
CREATE TABLE cancer_types (
    cancer_type_id INT AUTO_INCREMENT PRIMARY KEY,
    cancer_type_name VARCHAR(100) NOT NULL UNIQUE
);

-- Create Users Table
CREATE TABLE cancer_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role INT DEFAULT 3,  -- 1 for Super Admin, 2 for Admin, 3 for Regular User
    phone_number VARCHAR(15),
    profile_picture VARCHAR(255),
    date_of_birth DATE,
    gender VARCHAR(10),
    location VARCHAR(255),
    cancer_type_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Create Caregivers Table
CREATE TABLE cancer_caregivers (
    caregiver_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(15),
    specialization VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Doctors Table
CREATE TABLE cancer_doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(15),
    specialization VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Profiles Table
CREATE TABLE cancer_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    health_condition VARCHAR(255),
    treatment_status VARCHAR(255),
    symptoms TEXT,
    nutritional_plan TEXT,
    medications TEXT,
    emotional_wellbeing TEXT,
    caregiver_info TEXT,
    immunotherapy_status VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create Stories Table
CREATE TABLE cancer_stories (
    story_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cancer_type_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Create Appointments Table
CREATE TABLE cancer_appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    caregiver_id INT,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    location VARCHAR(255),
    notes TEXT,
    status VARCHAR(20) DEFAULT 'Scheduled' CHECK (status IN ('Scheduled', 'Completed', 'Canceled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (caregiver_id) REFERENCES cancer_caregivers(caregiver_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES cancer_doctors(doctor_id) ON DELETE SET NULL
);

-- Create Nutrition Table
CREATE TABLE cancer_nutrition (
    nutrition_id INT AUTO_INCREMENT PRIMARY KEY,
    cancer_type_id INT,
    nutrition_title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Create Resources Table
CREATE TABLE cancer_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    cancer_type_id INT,
    resource_type VARCHAR(50) CHECK (resource_type IN ('article', 'video', 'guide')),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cancer_type_id) REFERENCES cancer_cancer_types(cancer_type_id) ON DELETE SET NULL
);

-- Create Events Table
CREATE TABLE cancer_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Event Registrations Table
CREATE TABLE cancer_event_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Registered' CHECK (status IN ('Registered', 'Attended', 'Canceled')),
    FOREIGN KEY (event_id) REFERENCES cancer_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create News/Blog Table
CREATE TABLE cancer_news_blog (
    news_id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create Donations Table
CREATE TABLE cancer_donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    campaign_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create Payments Table
CREATE TABLE cancer_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_type VARCHAR(50) CHECK (payment_type IN ('appointment', 'donation', 'event')),
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    payment_status VARCHAR(20) DEFAULT 'Pending' CHECK (payment_status IN ('Pending', 'Completed', 'Failed')),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(255) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create Donors Table
CREATE TABLE cancer_donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount_donated DECIMAL(10, 2) NOT NULL CHECK (amount_donated >= 0),
    last_donation_date TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cancer_users(user_id) ON DELETE CASCADE
);

-- Create FAQs Table
CREATE TABLE cancer_faqs (
    faq_id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);