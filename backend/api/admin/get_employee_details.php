<?php
/**
 * Get Employee Details API
 * Returns detailed information about a specific employee
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
    
    $employee_id = $_GET['employee_id'] ?? null;
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee ID is required"));
        exit();
    }
    
    try {
        // Get employee details
        $query = "SELECT * FROM employees WHERE employee_id = :employee_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent attendance records
            $attendance_query = "SELECT * FROM attendance 
                               WHERE employee_id = :employee_id 
                               ORDER BY attendance_date DESC 
                               LIMIT 10";
            $attendance_stmt = $db->prepare($attendance_query);
            $attendance_stmt->bindParam(':employee_id', $employee_id);
            $attendance_stmt->execute();
            $attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total working days this month
            $stats_query = "SELECT 
                              COUNT(*) as total_days,
                              SUM(total_hours) as total_hours,
                              AVG(total_hours) as avg_hours
                            FROM attendance 
                            WHERE employee_id = :employee_id 
                            AND MONTH(attendance_date) = MONTH(CURDATE())
                            AND YEAR(attendance_date) = YEAR(CURDATE())";
            $stats_stmt = $db->prepare($stats_query);
            $stats_stmt->bindParam(':employee_id', $employee_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Remove password from response
            unset($employee['password']);
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "data" => array(
                    "employee" => $employee,
                    "attendance_records" => $attendance_records,
                    "monthly_stats" => $stats
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
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
