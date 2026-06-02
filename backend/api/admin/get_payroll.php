<?php
/**
 * Get Payroll API
 * Aggregates attendance hours over a date range and computes amount using employee salary as hourly rate
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('GET');

$db = get_db();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$employee_number = $_GET['employee_number'] ?? '';

try {
    $query = "SELECT e.employee_id, e.employee_number, e.first_name, e.last_name, e.salary,
                      COALESCE(SUM(a.total_hours), 0) as total_hours
               FROM employees e
               LEFT JOIN attendance a ON a.employee_id = e.employee_id
                    AND a.attendance_date BETWEEN :start_date AND :end_date
               WHERE e.is_active = 1";

    $params = [
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ];

    if (!empty($employee_number)) {
        $query .= " AND e.employee_number = :employee_number";
        $params[':employee_number'] = $employee_number;
    }

    $query .= " GROUP BY e.employee_id, e.employee_number, e.first_name, e.last_name, e.salary
                ORDER BY e.employee_number ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $row) {
        $hourly_rate = floatval($row['salary'] ?? 0);
        $hours = floatval($row['total_hours'] ?? 0);
        $amount = round($hourly_rate * $hours, 2);
        $result[] = [
            'employee_number' => $row['employee_number'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'total_hours' => round($hours, 2),
            'hourly_rate' => round($hourly_rate, 2),
            'amount' => number_format($amount, 2)
        ];
    }

    json_response([
        'success' => true,
        'data' => $result,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'total_employees' => count($result)
    ]);
} catch (PDOException $e) {
    json_error('Database error: ' . $e->getMessage(), 500);
}
?>
