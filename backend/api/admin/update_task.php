<?php
/**
 * Update Task API (Admin)
 * Allows admin to edit title/description/assignee/due_date.
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

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
    exit();
}

if (empty($data->task_id) || empty($data->title) || empty($data->assigned_employee_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "task_id, title and assigned_employee_id are required"));
    exit();
}

try {
    $task_id = intval($data->task_id);
    $title = trim($data->title);
    $description = isset($data->description) ? trim($data->description) : null;
    $assigned_employee_id = intval($data->assigned_employee_id);
    $due_date = isset($data->due_date) && trim($data->due_date) !== '' ? trim($data->due_date) : null;
    $admin_id = intval($_SESSION['admin_id']);

    // Ensure task exists
    $t = $db->prepare("SELECT task_id FROM tasks WHERE task_id = :task_id");
    $t->bindParam(':task_id', $task_id);
    $t->execute();
    if ($t->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Task not found"));
        exit();
    }

    // Ensure employee exists
    $e = $db->prepare("SELECT employee_id FROM employees WHERE employee_id = :id");
    $e->bindParam(':id', $assigned_employee_id);
    $e->execute();
    if ($e->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Employee not found"));
        exit();
    }

    $q = "UPDATE tasks
          SET title = :title,
              description = :description,
              assigned_employee_id = :assigned_employee_id,
              due_date = :due_date,
              updated_at = NOW()
          WHERE task_id = :task_id";
    $stmt = $db->prepare($q);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':assigned_employee_id', $assigned_employee_id);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->execute();

    // Record admin edit in history (no status change)
    $h = $db->prepare("INSERT INTO task_status_history (task_id, status, reason, changed_by_admin_id)
                       SELECT :task_id, status, 'Task edited by admin', :admin_id
                       FROM tasks WHERE task_id = :task_id");
    $h->bindParam(':task_id', $task_id);
    $h->bindParam(':admin_id', $admin_id);
    $h->execute();

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Task updated",
        "data" => array("task_id" => $task_id)
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

