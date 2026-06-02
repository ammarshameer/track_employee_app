<?php
/**
 * Admin Login API
 */

include_once '../../config/helpers.php';

require_method('POST');

$db = get_db();
$data = get_json_input();

if (empty($data->username) || empty($data->password)) {
    json_error("Username and password are required");
}

try {
    $query = "SELECT admin_id, admin_username, admin_password, admin_email 
             FROM admins 
             WHERE admin_username = :username";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        json_error("Admin not found", 401);
    }

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($data->password, $admin['admin_password'])) {
        json_error("Invalid credentials", 401);
    }
    
    session_start();
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_username'] = $admin['admin_username'];
    
    json_success(array(
        "admin_id" => $admin['admin_id'],
        "username" => $admin['admin_username'],
        "email" => $admin['admin_email']
    ), "Admin login successful");

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
