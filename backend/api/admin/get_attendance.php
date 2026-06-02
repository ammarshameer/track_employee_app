<?php
/**
 * Get Attendance Records API
 * Returns attendance data for specified date and optional employee
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('GET');

$db = get_db();

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
    
    json_response(array(
        "success" => true,
        "data" => $attendance,
        "date" => $date,
        "total_records" => count($attendance)
    ));
    
} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
