<?php
/**
 * Get Dashboard Statistics API
 * Returns statistics for admin dashboard
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
    
    try {
        $stats = array();
        
        // Total employees
        $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE is_active = 1");
        $stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active employees today (logged in today)
        $stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as active FROM attendance WHERE attendance_date = CURDATE()");
        $stats['active_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // GPS tracking updates today
        $stmt = $db->query("SELECT COUNT(*) as updates FROM gps_tracking WHERE tracking_date = CURDATE()");
        $stats['tracking_updates'] = $stmt->fetch(PDO::FETCH_ASSOC)['updates'];
        
        // Average work hours today
        $stmt = $db->query("SELECT AVG(total_hours) as avg_hours FROM attendance WHERE attendance_date = CURDATE() AND total_hours > 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['avg_work_hours'] = round($result['avg_hours'] ?? 0, 1);
        
        // Present today
        $stmt = $db->query("SELECT COUNT(*) as present FROM attendance WHERE attendance_date = CURDATE() AND status = 'present'");
        $stats['present_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['present'];
        
        // Absent today (employees who haven't logged in)
        $stmt = $db->query("
            SELECT COUNT(*) as absent 
            FROM employees e 
            WHERE e.is_active = 1 
            AND e.employee_id NOT IN (
                SELECT DISTINCT employee_id 
                FROM attendance 
                WHERE attendance_date = CURDATE()
            )
        ");
        $stats['absent_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['absent'];
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "data" => $stats
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
