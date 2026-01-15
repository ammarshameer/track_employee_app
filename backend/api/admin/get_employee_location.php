<?php
/**
 * Get Employee Location API
 * Admin can view GPS location of employee by employee ID and date
 */

include_once '../../config/cors.php';
include_once '../../config/database.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Admin authentication required"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $employee_number = $_GET['employee_number'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    if (!empty($employee_number)) {
        
        try {
            // Get employee details
            $employee_query = "SELECT employee_id, employee_number, first_name, last_name 
                             FROM employees 
                             WHERE employee_number = :employee_number";
            
            $employee_stmt = $db->prepare($employee_query);
            $employee_stmt->bindParam(':employee_number', $employee_number);
            $employee_stmt->execute();
            
            if ($employee_stmt->rowCount() > 0) {
                $employee = $employee_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get GPS tracking data for the specified date
                $gps_query = "SELECT latitude, longitude, accuracy, speed, altitude, timestamp 
                            FROM gps_tracking 
                            WHERE employee_id = :employee_id AND tracking_date = :date 
                            ORDER BY timestamp ASC";
                
                $gps_stmt = $db->prepare($gps_query);
                $gps_stmt->bindParam(':employee_id', $employee['employee_id']);
                $gps_stmt->bindParam(':date', $date);
                $gps_stmt->execute();
                
                $gps_data = $gps_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get attendance data for the day
                $attendance_query = "SELECT login_time, logout_time, login_latitude, login_longitude, 
                                   logout_latitude, logout_longitude, total_hours, total_seconds, work_duration, status 
                                   FROM attendance 
                                   WHERE employee_id = :employee_id AND attendance_date = :date";
                
                $attendance_stmt = $db->prepare($attendance_query);
                $attendance_stmt->bindParam(':employee_id', $employee['employee_id']);
                $attendance_stmt->bindParam(':date', $date);
                $attendance_stmt->execute();
                
                $attendance_data = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "data" => array(
                        "employee" => $employee,
                        "date" => $date,
                        "attendance" => $attendance_data,
                        "gps_tracking" => $gps_data,
                        "total_locations" => count($gps_data)
                    )
                ));
                
            } else {
                http_response_code(404);
                echo json_encode(array("success" => false, "message" => "Employee not found"));
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
        }
        
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee number is required"));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
