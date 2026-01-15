<?php
/**
 * Get Detailed Attendance Information API
 * Returns detailed attendance information for a specific employee and date
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
    
    if (empty($employee_number)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee number is required"));
        exit();
    }
    
    try {
        // Get employee info
        $emp_query = "SELECT employee_id, employee_number, first_name, last_name, department, position 
                      FROM employees WHERE employee_number = :employee_number";
        $emp_stmt = $db->prepare($emp_query);
        $emp_stmt->bindParam(':employee_number', $employee_number);
        $emp_stmt->execute();
        
        if ($emp_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(array("success" => false, "message" => "Employee not found"));
            exit();
        }
        
        $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get attendance record for the specific date
        $att_query = "SELECT * FROM attendance 
                      WHERE employee_id = :employee_id AND attendance_date = :date";
        $att_stmt = $db->prepare($att_query);
        $att_stmt->bindParam(':employee_id', $employee['employee_id']);
        $att_stmt->bindParam(':date', $date);
        $att_stmt->execute();
        
        $attendance = $att_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get GPS tracking data for the day
        $gps_query = "SELECT latitude, longitude, timestamp 
                      FROM gps_tracking 
                      WHERE employee_id = :employee_id AND tracking_date = :date 
                      ORDER BY timestamp ASC";
        $gps_stmt = $db->prepare($gps_query);
        $gps_stmt->bindParam(':employee_id', $employee['employee_id']);
        $gps_stmt->bindParam(':date', $date);
        $gps_stmt->execute();
        
        $gps_data = $gps_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format attendance data
        if ($attendance) {
            $attendance['login_time_formatted'] = $attendance['login_time'] ? date('H:i:s', strtotime($attendance['login_time'])) : 'N/A';
            $attendance['logout_time_formatted'] = $attendance['logout_time'] ? date('H:i:s', strtotime($attendance['logout_time'])) : 'Still logged in';
            $attendance['total_hours_formatted'] = number_format($attendance['total_hours'], 2);
            
            // Calculate work duration if still logged in
            if ($attendance['login_time'] && !$attendance['logout_time']) {
                $login_time = new DateTime($attendance['login_time']);
                $current_time = new DateTime();
                $duration = $current_time->diff($login_time);
                $attendance['current_duration'] = $duration->format('%H:%I:%S');
            }
        }
        
        // Format GPS data
        foreach ($gps_data as &$gps) {
            $gps['time_formatted'] = date('H:i:s', strtotime($gps['timestamp']));
        }
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "data" => array(
                "employee" => $employee,
                "attendance" => $attendance,
                "gps_tracking" => $gps_data,
                "date" => $date,
                "total_gps_points" => count($gps_data)
            )
        ));
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
