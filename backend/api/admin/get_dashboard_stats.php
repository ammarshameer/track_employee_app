<?php
/**
 * Get Dashboard Statistics API
 * Returns statistics for admin dashboard
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('GET');

$db = get_db();

try {
    $stats = array();
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE is_active = 1");
    $stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as active FROM attendance WHERE attendance_date = CURDATE()");
    $stats['active_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    $stmt = $db->query("SELECT COUNT(*) as updates FROM gps_tracking WHERE tracking_date = CURDATE()");
    $stats['tracking_updates'] = $stmt->fetch(PDO::FETCH_ASSOC)['updates'];
    
    $stmt = $db->query("SELECT AVG(total_hours) as avg_hours FROM attendance WHERE attendance_date = CURDATE() AND total_hours > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['avg_work_hours'] = round($result['avg_hours'] ?? 0, 1);
    
    $stmt = $db->query("SELECT COUNT(*) as present FROM attendance WHERE attendance_date = CURDATE() AND status = 'present'");
    $stats['present_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['present'];
    
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
    
    json_success($stats);
    
} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
