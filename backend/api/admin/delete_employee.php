<?php
/**
 * Delete Employee API
 * Admin can delete employees
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
    
    // Get employee ID from POST data or URL
    $employee_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        $employee_id = $data->employee_id ?? null;
    } else {
        $employee_id = $_GET['employee_id'] ?? null;
    }
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee ID is required"));
        exit();
    }
    
    try {
        // First check if employee exists
        $check_query = "SELECT employee_number, first_name, last_name FROM employees WHERE employee_id = :employee_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':employee_id', $employee_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(array("success" => false, "message" => "Employee not found"));
            exit();
        }
        
        $employee = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete employee (CASCADE will handle related records)
        $delete_query = "DELETE FROM employees WHERE employee_id = :employee_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':employee_id', $employee_id);
        
        if ($delete_stmt->execute()) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Employee deleted successfully",
                "data" => array(
                    "employee_id" => $employee_id,
                    "employee_number" => $employee['employee_number'],
                    "name" => $employee['first_name'] . ' ' . $employee['last_name']
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Failed to delete employee"));
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
