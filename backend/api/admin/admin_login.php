<?php
/**
 * Admin Login API
 */

include_once '../../config/cors.php';
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!empty($data->username) && !empty($data->password)) {
        
        try {
            $query = "SELECT admin_id, admin_username, admin_password, admin_email 
                     FROM admins 
                     WHERE admin_username = :username";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $data->username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($data->password, $admin['admin_password'])) {
                    
                    // Create admin session
                    session_start();
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_username'] = $admin['admin_username'];
                    
                    http_response_code(200);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Admin login successful",
                        "data" => array(
                            "admin_id" => $admin['admin_id'],
                            "username" => $admin['admin_username'],
                            "email" => $admin['admin_email']
                        )
                    ));
                    
                } else {
                    http_response_code(401);
                    echo json_encode(array("success" => false, "message" => "Invalid credentials"));
                }
                
            } else {
                http_response_code(401);
                echo json_encode(array("success" => false, "message" => "Admin not found"));
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
        }
        
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Username and password are required"));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
