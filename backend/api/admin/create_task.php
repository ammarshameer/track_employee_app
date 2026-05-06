<?php
/**
 * Create Task API (Admin)
 * Creates a task and assigns it to an employee.
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

if (empty($data->title) || empty($data->assigned_employee_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "title and assigned_employee_id are required"));
    exit();
}

try {
    $title = trim($data->title);
    $description = isset($data->description) ? trim($data->description) : null;
    $assigned_employee_id = intval($data->assigned_employee_id);
    $due_date = isset($data->due_date) && trim($data->due_date) !== '' ? trim($data->due_date) : null;
    $admin_id = intval($_SESSION['admin_id']);

    // Ensure employee exists
    $e = $db->prepare("SELECT employee_id FROM employees WHERE employee_id = :id");
    $e->bindParam(':id', $assigned_employee_id);
    $e->execute();
    if ($e->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Employee not found"));
        exit();
    }

    $db->beginTransaction();

    $q = "INSERT INTO tasks (title, description, assigned_employee_id, created_by_admin_id, status, due_date)
          VALUES (:title, :description, :assigned_employee_id, :created_by_admin_id, 'pending', :due_date)";
    $stmt = $db->prepare($q);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':assigned_employee_id', $assigned_employee_id);
    $stmt->bindParam(':created_by_admin_id', $admin_id);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->execute();

    $task_id = intval($db->lastInsertId());

    $h = $db->prepare("INSERT INTO task_status_history (task_id, status, reason, changed_by_admin_id)
                       VALUES (:task_id, 'pending', NULL, :admin_id)");
    $h->bindParam(':task_id', $task_id);
    $h->bindParam(':admin_id', $admin_id);
    $h->execute();

    $db->commit();

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Task created",
        "data" => array(
            "task_id" => $task_id
        )
    ));
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

