<?php
/**
 * Add Employee API
 * Admin can add new employees
 */

include_once '../../config/helpers.php';

require_admin_auth();
require_method('POST');

$db = get_db();
$data = get_json_input();

if (empty($data->first_name) || empty($data->last_name) || empty($data->employee_number)) {
    json_error("First name, last name, and employee number are required");
}

try {
    // Check if employee number already exists
    $check_query = "SELECT employee_id FROM employees WHERE employee_number = :employee_number";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':employee_number', $data->employee_number);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        json_error("Employee number already exists");
    }
    
    $default_password = $data->employee_number;
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
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
    
    if (!$insert_stmt->execute()) {
        json_error("Failed to add employee", 500);
    }

    $employee_id = $db->lastInsertId();
    
    json_success(array(
        "employee_id" => $employee_id,
        "employee_number" => $employee_number,
        "name" => $first_name . ' ' . $last_name,
        "default_password" => $default_password
    ), "Employee added successfully", 201);

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
