<?php
/**
 * Get Task Details API (Admin)
 * Returns task info + status history.
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

$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "task_id is required"));
    exit();
}

try {
    $tq = "SELECT 
            t.task_id, t.title, t.description, t.status, t.due_date,
            t.started_at, t.completed_at, t.blocked_at, t.block_reason,
            t.created_at, t.updated_at,
            e.employee_id, e.employee_number, e.first_name, e.last_name
          FROM tasks t
          JOIN employees e ON t.assigned_employee_id = e.employee_id
          WHERE t.task_id = :task_id";
    $t = $db->prepare($tq);
    $t->bindParam(':task_id', $task_id);
    $t->execute();

    if ($t->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Task not found"));
        exit();
    }

    $task = $t->fetch(PDO::FETCH_ASSOC);

    $hq = "SELECT h.history_id, h.status, h.reason, h.changed_at,
                  h.changed_by_employee_id, h.changed_by_admin_id
           FROM task_status_history h
           WHERE h.task_id = :task_id
           ORDER BY h.changed_at ASC";
    $h = $db->prepare($hq);
    $h->bindParam(':task_id', $task_id);
    $h->execute();
    $history = $h->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "task" => $task,
            "history" => $history
        )
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

