<?php
/**
 * Delete Employee API
 * Admin can delete employees
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method(['POST', 'DELETE']);

$db = get_db();

// Get employee ID from POST data or URL
$employee_id = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = get_json_input();
    $employee_id = $data->employee_id ?? null;
} else {
    $employee_id = $_GET['employee_id'] ?? null;
}

if (!$employee_id) {
    json_error("Employee ID is required");
}

try {
    $check_query = "SELECT employee_number, first_name, last_name FROM employees WHERE employee_id = :employee_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':employee_id', $employee_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        json_error("Employee not found", 404);
    }
    
    $employee = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    $delete_query = "DELETE FROM employees WHERE employee_id = :employee_id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':employee_id', $employee_id);
    
    if (!$delete_stmt->execute()) {
        json_error("Failed to delete employee", 500);
    }

    json_success(array(
        "employee_id" => $employee_id,
        "employee_number" => $employee['employee_number'],
        "name" => $employee['first_name'] . ' ' . $employee['last_name']
    ), "Employee deleted successfully");

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
