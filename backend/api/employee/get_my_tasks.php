<?php
/**
 * Get My Tasks API (Employee)
 * Returns tasks assigned to the logged-in employee.
 */

include_once '../../config/cors.php';
include_once '../../config/database.php';
include_once '_employee_auth.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
    exit();
}

if (empty($data->session_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Session ID is required"));
    exit();
}

try {
    $employee = require_employee_session($db, $data->session_id);

    $q = "SELECT t.task_id, t.title, t.description,
                 t.status AS raw_status,
                 CASE 
                   WHEN t.status <> 'completed' AND t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN 'expired'
                   ELSE t.status
                 END AS status,
                 t.due_date,
                 t.started_at, t.completed_at, t.blocked_at, t.block_reason,
                 t.created_at, t.updated_at
          FROM tasks t
          WHERE t.assigned_employee_id = :employee_id
          ORDER BY 
            CASE t.status
              WHEN 'in_progress' THEN 1
              WHEN 'blocked' THEN 2
              WHEN 'pending' THEN 3
              WHEN 'completed' THEN 4
              ELSE 5
            END,
            t.updated_at DESC";

    $stmt = $db->prepare($q);
    $stmt->bindParam(':employee_id', $employee['employee_id']);
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

