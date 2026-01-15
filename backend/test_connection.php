<?php
/**
 * Database Connection Test Script
 * Use this to verify database setup and create admin user if needed
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Employee Tracking System - Database Test</h2>";

// Test database connection
try {
    $host = "localhost";
    $db_name = "emp_track_db";
    $username = "root";
    $password = "";
    
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if admin table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Admin table exists</p>";
        
        // Check if admin user exists
        $stmt = $pdo->query("SELECT * FROM admins WHERE admin_username = 'admin'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Admin user exists</p>";
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Admin ID: " . $admin['admin_id'] . "</p>";
            echo "<p>Username: " . $admin['admin_username'] . "</p>";
            echo "<p>Email: " . ($admin['admin_email'] ?? 'Not set') . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Admin user does not exist. Creating...</p>";
            
            // Create admin user
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (admin_username, admin_password, admin_email) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $hashedPassword, 'admin@emptrack.com']);
            
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> password</p>";
        }
        
        // Check other tables
        $tables = ['employees', 'attendance', 'gps_tracking', 'payroll', 'employee_sessions'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✅ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Admin table does not exist. Please run the database schema first.</p>";
    }
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p style='color: red;'>❌ Database 'emp_track_db' does not exist.</p>";
        echo "<p><strong>Solution:</strong> Create the database first:</p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Click 'New' to create a database</li>";
        echo "<li>Name it: <code>emp_track_db</code></li>";
        echo "<li>Import the SQL file: <code>database/emp_track_db.sql</code></li>";
        echo "</ol>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "<p><strong>Common solutions:</strong></p>";
        echo "<ul>";
        echo "<li>Make sure XAMPP MySQL service is running</li>";
        echo "<li>Check if MySQL is running on port 3306</li>";
        echo "<li>Verify database credentials in config/database.php</li>";
        echo "</ul>";
    }
}

// Test API endpoint
echo "<hr>";
echo "<h3>API Endpoint Test</h3>";

$api_url = "http://localhost/emp_track_2/backend/api/admin/admin_login.php";
echo "<p>Testing API endpoint: <a href='$api_url' target='_blank'>$api_url</a></p>";

// Test with curl if available
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'test', 'password' => 'test']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ API endpoint is accessible (HTTP $http_code)</p>";
        echo "<p>Response: <code>" . htmlspecialchars($response) . "</code></p>";
    } else {
        echo "<p style='color: red;'>❌ API endpoint not accessible</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ cURL not available for API testing</p>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>If database connection failed: Start XAMPP MySQL service</li>";
echo "<li>If database doesn't exist: Create it in phpMyAdmin and import the SQL file</li>";
echo "<li>If admin user doesn't exist: It will be created automatically above</li>";
echo "<li>Try logging in again with: admin / password</li>";
echo "</ol>";
?>
