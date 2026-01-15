<?php
/**
 * Mobile Connection Test
 * Test if the API is accessible from mobile devices
 */

// Enable CORS for mobile apps
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo "<h2>Mobile Connection Test</h2>";
echo "<p><strong>Status:</strong> <span style='color: green;'>✅ API is accessible from mobile devices!</span></p>";

echo "<h3>Connection Details:</h3>";
echo "<ul>";
echo "<li><strong>Server IP:</strong> " . $_SERVER['SERVER_ADDR'] . "</li>";
echo "<li><strong>Client IP:</strong> " . $_SERVER['REMOTE_ADDR'] . "</li>";
echo "<li><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</li>";
echo "<li><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not provided') . "</li>";
echo "<li><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

echo "<h3>API Endpoints to Test:</h3>";
echo "<ul>";
echo "<li><a href='api/auth/login.php' target='_blank'>Login API</a></li>";
echo "<li><a href='api/tracking/gps_update.php' target='_blank'>GPS Update API</a></li>";
echo "<li><a href='../web_admin/' target='_blank'>Web Admin Interface</a></li>";
echo "</ul>";

echo "<h3>Test from Mobile Browser:</h3>";
echo "<p>Open this URL on your mobile device:</p>";
echo "<p><strong>http://10.234.150.102/emp_track_2/backend/test_mobile_connection.php</strong></p>";

echo "<h3>Flutter App Configuration:</h3>";
echo "<p>The Flutter app is configured to use:</p>";
echo "<code>http://10.234.150.102/emp_track_2/backend/api</code>";

// Test database connection
echo "<h3>Database Connection Test:</h3>";
try {
    include_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test if admin user exists
        $stmt = $db->query("SELECT COUNT(*) as count FROM admins WHERE admin_username = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "<p style='color: green;'>✅ Admin user exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Admin user not found - run setup_database.php</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>If you can see this page on your mobile device, the connection is working!</em></p>";
?>
