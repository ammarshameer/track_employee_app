<?php
/**
 * Employee Login API
 * Handles employee authentication and captures login image with GPS
 */

include_once '../../config/cors.php';
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Check if required fields are provided
    if (!empty($data->employee_number) && !empty($data->password)) {
        
        try {
            // Query to get employee details
            $query = "SELECT employee_id, employee_number, first_name, last_name, password, is_active 
                     FROM employees 
                     WHERE employee_number = :employee_number AND is_active = 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':employee_number', $data->employee_number);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($data->password, $employee['password'])) {
                    
                    // Handle image upload if provided
                    $image_path = null;
                    if (!empty($data->login_image)) {
                        $image_path = saveLoginImage($data->login_image, $employee['employee_id']);
                    }
                    
                    // Record attendance
                    $attendance_query = "INSERT INTO attendance 
                                       (employee_id, login_time, login_image_path, login_latitude, login_longitude, attendance_date) 
                                       VALUES (:employee_id, NOW(), :image_path, :latitude, :longitude, CURDATE())";
                    
                    $attendance_stmt = $db->prepare($attendance_query);
                    $attendance_stmt->bindParam(':employee_id', $employee['employee_id']);
                    $attendance_stmt->bindParam(':image_path', $image_path);
                    $attendance_stmt->bindParam(':latitude', $data->latitude);
                    $attendance_stmt->bindParam(':longitude', $data->longitude);
                    
                    if ($attendance_stmt->execute()) {
                        $att_id = $db->lastInsertId();
                        $att_stmt = $db->prepare("SELECT login_time FROM attendance WHERE attendance_id = :id");
                        $att_stmt->bindParam(':id', $att_id);
                        $att_stmt->execute();
                        $att_row = $att_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Create session
                        $session_id = bin2hex(random_bytes(32));
                        $session_query = "INSERT INTO employee_sessions 
                                        (session_id, employee_id, device_info) 
                                        VALUES (:session_id, :employee_id, :device_info)";
                        
                        $session_stmt = $db->prepare($session_query);
                        $session_stmt->bindParam(':session_id', $session_id);
                        $session_stmt->bindParam(':employee_id', $employee['employee_id']);
                        $session_stmt->bindParam(':device_info', $data->device_info);
                        $session_stmt->execute();
                        
                        // Return success response
                        http_response_code(200);
                        echo json_encode(array(
                            "success" => true,
                            "message" => "Login successful",
                            "data" => array(
                                "employee_id" => $employee['employee_id'],
                                "employee_number" => $employee['employee_number'],
                                "name" => $employee['first_name'] . ' ' . $employee['last_name'],
                                "session_id" => $session_id,
                                "login_time" => $att_row ? $att_row['login_time'] : null
                            )
                        ));
                        
                    } else {
                        http_response_code(500);
                        echo json_encode(array("success" => false, "message" => "Failed to record attendance"));
                    }
                    
                } else {
                    http_response_code(401);
                    echo json_encode(array("success" => false, "message" => "Invalid credentials"));
                }
                
            } else {
                http_response_code(401);
                echo json_encode(array("success" => false, "message" => "Employee not found"));
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
        }
        
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee number and password are required"));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}

/**
 * Save login image to server
 */
function saveLoginImage($base64_image, $employee_id) {
    try {
        // Create uploads directory if it doesn't exist
        $upload_dir = '../../uploads/login_images/' . date('Y/m/d') . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Decode base64 image
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image));
        
        // Generate unique filename
        $filename = $employee_id . '_' . date('His') . '_' . uniqid() . '.jpg';
        $file_path = $upload_dir . $filename;
        
        // Save image
        if (file_put_contents($file_path, $image_data)) {
            return $file_path;
        }
        
    } catch (Exception $e) {
        error_log("Image save error: " . $e->getMessage());
    }
    
    return null;
}
?>
