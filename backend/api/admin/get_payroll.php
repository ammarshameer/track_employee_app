<?php
/**
 * Get Payroll API
 * Aggregates attendance hours over a date range and computes amount using employee salary as hourly rate
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
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $employee_number = $_GET['employee_number'] ?? '';

    try {
        // Base query: sum total_hours for each employee within range
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
            $hourly_rate = floatval($row['salary'] ?? 0); // interpret salary as hourly rate
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

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $result,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_employees' => count($result)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
} else {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
}
?>