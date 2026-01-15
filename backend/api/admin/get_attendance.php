<?php
/**
 * Get Attendance Records API
 * Returns attendance data for specified date and optional employee
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
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $employee_number = $_GET['employee_number'] ?? '';
    
    try {
        $query = "SELECT a.*, e.employee_number, e.first_name, e.last_name 
                  FROM attendance a 
                  JOIN employees e ON a.employee_id = e.employee_id 
                  WHERE a.attendance_date = :date";
        
        $params = [':date' => $date];
        
        if (!empty($employee_number)) {
            $query .= " AND e.employee_number = :employee_number";
            $params[':employee_number'] = $employee_number;
        }
        
        $query .= " ORDER BY a.login_time DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        foreach ($attendance as &$record) {
            $record['name'] = $record['first_name'] . ' ' . $record['last_name'];
            $record['login_time_formatted'] = $record['login_time'] ? date('Y-m-d H:i:s', strtotime($record['login_time'])) : 'N/A';
            $record['logout_time_formatted'] = $record['logout_time'] ? date('Y-m-d H:i:s', strtotime($record['logout_time'])) : 'Still logged in';
            $record['duration_hms'] = isset($record['total_seconds']) ? gmdate('H:i:s', (int)$record['total_seconds']) : '';
            $record['total_hours_formatted'] = number_format($record['total_hours'], 2);
        }
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "data" => $attendance,
            "date" => $date,
            "total_records" => count($attendance)
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
