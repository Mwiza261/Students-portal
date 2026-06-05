<?php
/**
 * Database Installation Script
 * Run this file once to set up your database
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chigoneka');

echo "<pre>";
echo "Starting database installation...\n\n";

try {
    // Create connection without database
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    // Drop and recreate database
    echo "Dropping existing database if exists...\n";
    $mysqli->query("DROP DATABASE IF EXISTS `" . DB_NAME . "`");
    
    echo "Creating fresh database...\n";
    $mysqli->query("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "Selecting database...\n";
    $mysqli->select_db(DB_NAME);
    
    // Create users table
    echo "Creating users table...\n";
    $mysqli->query("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE,
            phone VARCHAR(50) UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('student', 'staff', 'admin', 'teacher') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create courses table
    echo "Creating courses table...\n";
    $mysqli->query("
        CREATE TABLE courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_code VARCHAR(20) UNIQUE NOT NULL,
            course_name VARCHAR(100) NOT NULL,
            description TEXT,
            credits INT NOT NULL DEFAULT 3,
            department VARCHAR(50),
            semester VARCHAR(20),
            academic_year VARCHAR(20),
            max_students INT DEFAULT 50,
            current_students INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            teacher_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Create student_courses table
    echo "Creating student_courses table...\n";
    $mysqli->query("
        CREATE TABLE student_courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('registered', 'dropped', 'completed') DEFAULT 'registered',
            grade VARCHAR(2) DEFAULT NULL,
            score DECIMAL(5,2) DEFAULT NULL,
            semester VARCHAR(20),
            academic_year VARCHAR(20),
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_registration (student_id, course_id, semester, academic_year)
        )
    ");
    
    // Create grades table
    echo "Creating grades table...\n";
    $mysqli->query("
        CREATE TABLE grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            teacher_id INT NOT NULL,
            grade VARCHAR(2),
            score DECIMAL(5,2),
            semester VARCHAR(20),
            academic_year VARCHAR(20),
            assessment_type ENUM('continuous_assessment', 'mid_exam', 'final_exam', 'total') DEFAULT 'total',
            comments TEXT,
            entered_by INT,
            entered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_grade (student_id, course_id, semester, academic_year)
        )
    ");
    
    // Create teacher_course_assignments table
    echo "Creating teacher_course_assignments table...\n";
    $mysqli->query("
        CREATE TABLE teacher_course_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            course_id INT NOT NULL,
            academic_year VARCHAR(20),
            semester VARCHAR(20),
            assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active',
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (teacher_id, course_id, academic_year, semester)
        )
    ");
    
    // Create student_course_eligibility table
    echo "Creating student_course_eligibility table...\n";
    $mysqli->query("
        CREATE TABLE student_course_eligibility (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            teacher_id INT NOT NULL,
            academic_year VARCHAR(20),
            semester VARCHAR(20),
            assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_eligibility (student_id, course_id, academic_year, semester)
        )
    ");
    
    // Create user_activity_log table
    echo "Creating user_activity_log table...\n";
    $mysqli->query("
        CREATE TABLE user_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create notices table
    echo "Creating notices table...\n";
    $mysqli->query("
        CREATE TABLE notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample data
    echo "\nInserting sample data...\n";
    
    // Sample users (password: password123)
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $mysqli->query("
        INSERT INTO users (username, first_name, surname, email, password_hash, role) VALUES
        ('student1', 'John', 'Doe', 'john.doe@example.com', '$password_hash', 'student'),
        ('student2', 'Jane', 'Smith', 'jane.smith@example.com', '$password_hash', 'student'),
        ('teacher1', 'Robert', 'Johnson', 'robert.johnson@chigoneka.edu', '$password_hash', 'teacher'),
        ('admin1', 'Admin', 'User', 'admin@chigoneka.edu', '$password_hash', 'admin')
    ");
    
    // Sample courses
    $mysqli->query("
        INSERT INTO courses (course_code, course_name, description, credits, department, semester) VALUES
        ('MATH101', 'Mathematics 101', 'Basic algebra and calculus concepts', 3, 'Mathematics', 'Semester 1'),
        ('ENG101', 'English Literature', 'Introduction to English literature and composition', 3, 'Languages', 'Semester 1'),
        ('CS101', 'Computer Science', 'Introduction to programming and computing', 4, 'Computer Science', 'Semester 1'),
        ('PHY101', 'Physics', 'Basic physics principles and laws', 3, 'Science', 'Semester 1'),
        ('CHEM101', 'Chemistry', 'Introduction to chemical reactions', 3, 'Science', 'Semester 1')
    ");
    
    // Sample student registrations
    $mysqli->query("
        INSERT INTO student_courses (student_id, course_id, semester, academic_year, status) VALUES
        (1, 1, 'Semester 1', '2024', 'registered'),
        (1, 2, 'Semester 1', '2024', 'registered'),
        (1, 3, 'Semester 1', '2024', 'registered'),
        (2, 1, 'Semester 1', '2024', 'registered'),
        (2, 4, 'Semester 1', '2024', 'registered')
    ");
    
    // Sample grades
    $mysqli->query("
        INSERT INTO grades (student_id, course_id, teacher_id, grade, score, semester, academic_year, assessment_type) VALUES
        (1, 1, 3, 'A', 85.5, 'Semester 1', '2024', 'total'),
        (1, 2, 3, 'B', 78.0, 'Semester 1', '2024', 'total'),
        (1, 3, 3, 'A', 92.0, 'Semester 1', '2024', 'total')
    ");
    
    // Sample notices
    $mysqli->query("
        INSERT INTO notices (title, body) VALUES
        ('Welcome to the Portal', 'Welcome to Chigoneka School Student Portal. Access your courses, results and timetables here.'),
        ('Fee Payment Deadline', 'The deadline for fee payment is 30th June 2024. Late payment penalties will apply.')
    ");
    
    echo "\n✅ Database setup completed successfully!\n";
    echo "\n📝 Login Credentials:\n";
    echo "   Student: username='student1', password='password123'\n";
    echo "   Teacher: username='teacher1', password='password123'\n";
    echo "   Admin: username='admin1', password='password123'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}

echo "</pre>";
?>