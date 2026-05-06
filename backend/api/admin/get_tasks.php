<?php
/**
 * Get Tasks API (Admin)
 * Lists tasks with assigned employee info.
 * Optional query params: status, employee_number
 */

include_once '../../config/cors.php';
include_once '../../config/database.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Admin authentication required"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
    exit();
}

$status = isset($_GET['status']) ? trim($_GET['status']) : null;
$employee_number = isset($_GET['employee_number']) ? trim($_GET['employee_number']) : null;

try {
    $where = array();
    $params = array();

    if ($status) {
        $where[] = "t.status = :status";
        $params[':status'] = $status;
    }
    if ($employee_number) {
        $where[] = "e.employee_number = :employee_number";
        $params[':employee_number'] = $employee_number;
    }

    $whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

    $q = "SELECT 
            t.task_id, t.title, t.description, t.status, t.due_date,
            t.started_at, t.completed_at, t.blocked_at, t.block_reason,
            t.created_at, t.updated_at,
            e.employee_id, e.employee_number, e.first_name, e.last_name
          FROM tasks t
          JOIN employees e ON t.assigned_employee_id = e.employee_id
          $whereSql
          ORDER BY t.created_at DESC";

    $stmt = $db->prepare($q);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => $rows,
        "total" => count($rows)
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

