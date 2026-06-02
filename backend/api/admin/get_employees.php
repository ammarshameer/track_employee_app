<?php
/**
 * Get All Employees API
 * Returns list of all employees for admin interface
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('GET');

$db = get_db();

try {
    $query = "SELECT employee_id, employee_number, first_name, last_name, email, phone, 
                     department, position, salary, hire_date, is_active, created_at
              FROM employees 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(array(
        "success" => true,
        "data" => $employees,
        "total" => count($employees)
    ));
    
} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
