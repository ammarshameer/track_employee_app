<?php
/**
 * Database Setup Script
 * This will create the database and all required tables
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Employee Tracking System - Database Setup</h2>";

try {
    // First, connect without specifying database to create it
    $host = "localhost";
    $username = "root";
    $password = "";
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to MySQL server</p>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS emp_track_db");
    echo "<p style='color: green;'>✅ Database 'emp_track_db' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE emp_track_db");
    
    // Create tables
    $tables = [
        "admins" => "
            CREATE TABLE IF NOT EXISTS admins (
                admin_id INT PRIMARY KEY AUTO_INCREMENT,
                admin_username VARCHAR(50) UNIQUE NOT NULL,
                admin_password VARCHAR(255) NOT NULL,
                admin_email VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
        
        "employees" => "
            CREATE TABLE IF NOT EXISTS employees (
                employee_id INT PRIMARY KEY AUTO_INCREMENT,
                employee_number VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(15),
                address TEXT,
                date_of_birth DATE,
                hire_date DATE NOT NULL,
                salary DECIMAL(10,2) DEFAULT 0.00,
                department VARCHAR(50),
                position VARCHAR(50),
                password VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
        
        "attendance" => "
            CREATE TABLE IF NOT EXISTS attendance (
                attendance_id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                login_time TIMESTAMP NOT NULL,
                logout_time TIMESTAMP NULL,
                login_image_path VARCHAR(255),
                logout_image_path VARCHAR(255),
                login_latitude DECIMAL(10, 8),
                login_longitude DECIMAL(11, 8),
                logout_latitude DECIMAL(10, 8),
                logout_longitude DECIMAL(11, 8),
                total_hours DECIMAL(5,2) DEFAULT 0.00,
                total_seconds INT DEFAULT 0,
                work_duration TIME NULL,
                attendance_date DATE NOT NULL,
                status ENUM('present', 'absent', 'partial') DEFAULT 'present',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            )",
        
        "gps_tracking" => "
            CREATE TABLE IF NOT EXISTS gps_tracking (
                tracking_id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                accuracy DECIMAL(8, 2),
                speed DECIMAL(8, 2),
                altitude DECIMAL(8, 2),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                tracking_date DATE NOT NULL,
                FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            )",
        
        "payroll" => "
            CREATE TABLE IF NOT EXISTS payroll (
                payroll_id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                pay_period_start DATE NOT NULL,
                pay_period_end DATE NOT NULL,
                total_hours DECIMAL(8,2) DEFAULT 0.00,
                hourly_rate DECIMAL(8,2) DEFAULT 0.00,
                overtime_hours DECIMAL(8,2) DEFAULT 0.00,
                overtime_rate DECIMAL(8,2) DEFAULT 0.00,
                gross_pay DECIMAL(10,2) DEFAULT 0.00,
                deductions DECIMAL(10,2) DEFAULT 0.00,
                net_pay DECIMAL(10,2) DEFAULT 0.00,
                status ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            )",
        
        "employee_sessions" => "
            CREATE TABLE IF NOT EXISTS employee_sessions (
                session_id VARCHAR(255) PRIMARY KEY,
                employee_id INT NOT NULL,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                device_info TEXT,
                FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
            )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Table '$tableName' created/verified</p>";
    }

    try {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS total_seconds INT DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS work_duration TIME NULL");
    } catch (PDOException $e) {}
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_employee_number ON employees(employee_number)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_employee_date ON attendance(employee_id, attendance_date)",
        "CREATE INDEX IF NOT EXISTS idx_gps_employee_date ON gps_tracking(employee_id, tracking_date)",
        "CREATE INDEX IF NOT EXISTS idx_gps_timestamp ON gps_tracking(timestamp)",
        "CREATE INDEX IF NOT EXISTS idx_payroll_employee ON payroll(employee_id)",
        "CREATE INDEX IF NOT EXISTS idx_sessions_employee ON employee_sessions(employee_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (PDOException $e) {
            // Ignore if index already exists
        }
    }
    echo "<p style='color: green;'>✅ Database indexes created/verified</p>";
    
    // Insert default admin user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_username = 'admin'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (admin_username, admin_password, admin_email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin@emptrack.com']);
        echo "<p style='color: green;'>✅ Default admin user created</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Admin user already exists</p>";
    }
    
    // Insert sample employees
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        
        $employees = [
            ['EMP001', 'John', 'Doe', 'john.doe@company.com', '1234567890', '2024-01-15', 50000.00, 'Field Operations', 'Field Worker'],
            ['EMP002', 'Jane', 'Smith', 'jane.smith@company.com', '0987654321', '2024-02-01', 45000.00, 'Field Operations', 'Field Worker']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO employees (employee_number, first_name, last_name, email, phone, hire_date, salary, department, position, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($employees as $emp) {
            $emp[] = $hashedPassword; // Add password
            $stmt->execute($emp);
        }
        
        echo "<p style='color: green;'>✅ Sample employees created</p>";
        echo "<p><strong>Sample Employee Credentials:</strong></p>";
        echo "<ul>";
        echo "<li>EMP001 / password</li>";
        echo "<li>EMP002 / password</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Employees already exist</p>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Database Setup Complete!</h3>";
    echo "<p><strong>Admin Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: password</li>";
    echo "</ul>";
    
    echo "<p><a href='../web_admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check if MySQL is accessible on localhost:3306</li>";
    echo "<li>Verify MySQL root user has no password (default XAMPP setup)</li>";
    echo "</ul>";
}
?>
