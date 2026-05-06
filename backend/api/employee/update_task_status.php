<?php
/**
 * Update Task Status API (Employee)
 * Actions: start, complete, block (requires reason)
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

if (empty($data->session_id) || empty($data->task_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "session_id, task_id and action are required"));
    exit();
}

$action = strtolower(trim($data->action));
$task_id = intval($data->task_id);

try {
    $employee = require_employee_session($db, $data->session_id);

    // Load task and ensure it's assigned to this employee
    $tstmt = $db->prepare("SELECT task_id, status FROM tasks WHERE task_id = :task_id AND assigned_employee_id = :employee_id");
    $tstmt->bindParam(':task_id', $task_id);
    $tstmt->bindParam(':employee_id', $employee['employee_id']);
    $tstmt->execute();

    if ($tstmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Task not found"));
        exit();
    }

    $task = $tstmt->fetch(PDO::FETCH_ASSOC);

    $newStatus = null;
    $reason = null;

    if ($action === 'start') {
        $newStatus = 'in_progress';
    } else if ($action === 'complete') {
        $newStatus = 'completed';
    } else if ($action === 'block') {
        $newStatus = 'blocked';
        $reason = isset($data->reason) ? trim($data->reason) : '';
        if ($reason === '') {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Block reason is required"));
            exit();
        }
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Invalid action. Use start|complete|block"));
        exit();
    }

    $db->beginTransaction();

    if ($newStatus === 'in_progress') {
        $u = $db->prepare("UPDATE tasks
                           SET status = 'in_progress',
                               started_at = COALESCE(started_at, NOW()),
                               block_reason = NULL,
                               blocked_at = NULL
                           WHERE task_id = :task_id");
        $u->bindParam(':task_id', $task_id);
        $u->execute();
    } else if ($newStatus === 'completed') {
        $u = $db->prepare("UPDATE tasks
                           SET status = 'completed',
                               completed_at = NOW()
                           WHERE task_id = :task_id");
        $u->bindParam(':task_id', $task_id);
        $u->execute();
    } else if ($newStatus === 'blocked') {
        $u = $db->prepare("UPDATE tasks
                           SET status = 'blocked',
                               blocked_at = NOW(),
                               block_reason = :reason
                           WHERE task_id = :task_id");
        $u->bindParam(':task_id', $task_id);
        $u->bindParam(':reason', $reason);
        $u->execute();
    }

    $h = $db->prepare("INSERT INTO task_status_history (task_id, status, reason, changed_by_employee_id)
                       VALUES (:task_id, :status, :reason, :employee_id)");
    $h->bindParam(':task_id', $task_id);
    $h->bindParam(':status', $newStatus);
    $h->bindParam(':reason', $reason);
    $h->bindParam(':employee_id', $employee['employee_id']);
    $h->execute();

    $db->commit();

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Task updated",
        "data" => array(
            "task_id" => $task_id,
            "status" => $newStatus
        )
    ));
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

