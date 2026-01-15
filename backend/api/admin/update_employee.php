<?php
/**
 * Update Employee API
 * Admin can update employee information
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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!empty($data->employee_id) && !empty($data->first_name) && !empty($data->last_name)) {
        
        try {
            // Check if employee exists
            $check_query = "SELECT employee_id FROM employees WHERE employee_id = :employee_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':employee_id', $data->employee_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(array("success" => false, "message" => "Employee not found"));
                exit();
            }
            
            // Update employee
            $update_query = "UPDATE employees SET 
                           first_name = :first_name,
                           last_name = :last_name,
                           email = :email,
                           phone = :phone,
                           address = :address,
                           date_of_birth = :date_of_birth,
                           salary = :salary,
                           department = :department,
                           position = :position,
                           is_active = :is_active,
                           updated_at = CURRENT_TIMESTAMP
                           WHERE employee_id = :employee_id";
            
            $update_stmt = $db->prepare($update_query);
            
            $employee_id = $data->employee_id;
            $first_name = $data->first_name;
            $last_name = $data->last_name;
            $email = $data->email ?? null;
            $phone = $data->phone ?? null;
            $address = $data->address ?? null;
            $date_of_birth = $data->date_of_birth ?? null;
            $salary = $data->salary ?? 0.00;
            $department = $data->department ?? null;
            $position = $data->position ?? null;
            $is_active = $data->is_active ?? 1;
            
            $update_stmt->bindParam(':employee_id', $employee_id);
            $update_stmt->bindParam(':first_name', $first_name);
            $update_stmt->bindParam(':last_name', $last_name);
            $update_stmt->bindParam(':email', $email);
            $update_stmt->bindParam(':phone', $phone);
            $update_stmt->bindParam(':address', $address);
            $update_stmt->bindParam(':date_of_birth', $date_of_birth);
            $update_stmt->bindParam(':salary', $salary);
            $update_stmt->bindParam(':department', $department);
            $update_stmt->bindParam(':position', $position);
            $update_stmt->bindParam(':is_active', $is_active);
            
            if ($update_stmt->execute()) {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Employee updated successfully",
                    "data" => array(
                        "employee_id" => $employee_id,
                        "name" => $first_name . ' ' . $last_name
                    )
                ));
                
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Failed to update employee"));
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
        }
        
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Employee ID, first name, and last name are required"));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
