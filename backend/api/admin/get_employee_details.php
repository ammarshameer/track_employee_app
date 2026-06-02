<?php
/**
 * Get Employee Details API
 * Returns detailed information about a specific employee
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('GET');

$db = get_db();

$employee_id = $_GET['employee_id'] ?? null;

if (!$employee_id) {
    json_error("Employee ID is required");
}

try {
    $query = "SELECT * FROM employees WHERE employee_id = :employee_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        json_error("Employee not found", 404);
    }

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
    
    json_success(array(
        "employee" => $employee,
        "attendance_records" => $attendance_records,
        "monthly_stats" => $stats
    ));

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
