<?php
/**
 * Add Employee API
 * Admin can add new employees
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
    
    if (!empty($data->first_name) && !empty($data->last_name) && !empty($data->employee_number)) {
        
        try {
            // Check if employee number already exists
            $check_query = "SELECT employee_id FROM employees WHERE employee_number = :employee_number";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':employee_number', $data->employee_number);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "Employee number already exists"));
                exit();
            }
            
            // Generate default password (same as employee number for simplicity)
            $default_password = $data->employee_number;
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            
            // Insert new employee
            $insert_query = "INSERT INTO employees 
                           (employee_number, first_name, last_name, email, phone, address, 
                            date_of_birth, hire_date, salary, department, position, password) 
                           VALUES 
                           (:employee_number, :first_name, :last_name, :email, :phone, :address, 
                            :date_of_birth, :hire_date, :salary, :department, :position, :password)";
            
            $insert_stmt = $db->prepare($insert_query);
            
            $employee_number = $data->employee_number;
            $first_name = $data->first_name;
            $last_name = $data->last_name;
            $email = $data->email ?? null;
            $phone = $data->phone ?? null;
            $address = $data->address ?? null;
            $date_of_birth = $data->date_of_birth ?? null;
            $hire_date = $data->hire_date ?? date('Y-m-d');
            $salary = $data->salary ?? 0.00;
            $department = $data->department ?? 'Field Operations';
            $position = $data->position ?? 'Field Worker';
            
            $insert_stmt->bindParam(':employee_number', $employee_number);
            $insert_stmt->bindParam(':first_name', $first_name);
            $insert_stmt->bindParam(':last_name', $last_name);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':phone', $phone);
            $insert_stmt->bindParam(':address', $address);
            $insert_stmt->bindParam(':date_of_birth', $date_of_birth);
            $insert_stmt->bindParam(':hire_date', $hire_date);
            $insert_stmt->bindParam(':salary', $salary);
            $insert_stmt->bindParam(':department', $department);
            $insert_stmt->bindParam(':position', $position);
            $insert_stmt->bindParam(':password', $hashed_password);
            
            if ($insert_stmt->execute()) {
                $employee_id = $db->lastInsertId();
                
                http_response_code(201);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Employee added successfully",
                    "data" => array(
                        "employee_id" => $employee_id,
                        "employee_number" => $employee_number,
                        "name" => $first_name . ' ' . $last_name,
                        "default_password" => $default_password
                    )
                ));
                
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Failed to add employee"));
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()));
        }
        
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "First name, last name, and employee number are required"));
    }
    
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>
