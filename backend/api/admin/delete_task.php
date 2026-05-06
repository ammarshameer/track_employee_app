<?php
/**
 * Delete Task API (Admin)
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

if (empty($data->task_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "task_id is required"));
    exit();
}

try {
    $task_id = intval($data->task_id);

    $stmt = $db->prepare("DELETE FROM tasks WHERE task_id = :task_id");
    $stmt->bindParam(':task_id', $task_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Task not found"));
        exit();
    }

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Task deleted"
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
}

