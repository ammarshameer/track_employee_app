<?php
/**
 * Employee Login API
 * Handles employee authentication with GPS
 */

include_once '../../config/helpers.php';

require_method('POST');

$db = get_db();
$data = get_json_input();

if (empty($data->employee_number) || empty($data->password)) {
    json_error("Employee number and password are required");
}

try {
    $query = "SELECT employee_id, employee_number, first_name, last_name, password, is_active 
             FROM employees 
             WHERE employee_number = :employee_number AND is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_number', $data->employee_number);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        json_error("Employee not found", 401);
    }

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($data->password, $employee['password'])) {
        json_error("Invalid credentials", 401);
    }
    
    // Record attendance
    $attendance_query = "INSERT INTO attendance 
                       (employee_id, login_time, login_latitude, login_longitude, attendance_date) 
                       VALUES (:employee_id, NOW(), :latitude, :longitude, CURDATE())";
    
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->bindParam(':employee_id', $employee['employee_id']);
    $attendance_stmt->bindParam(':latitude', $data->latitude);
    $attendance_stmt->bindParam(':longitude', $data->longitude);
    
    if (!$attendance_stmt->execute()) {
        json_error("Failed to record attendance", 500);
    }

    $att_id = $db->lastInsertId();
    $att_stmt = $db->prepare("SELECT login_time FROM attendance WHERE attendance_id = :id");
    $att_stmt->bindParam(':id', $att_id);
    $att_stmt->execute();
    $att_row = $att_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create session
    $session_id = bin2hex(random_bytes(32));
    $session_query = "INSERT INTO employee_sessions 
                    (session_id, employee_id, device_info) 
                    VALUES (:session_id, :employee_id, :device_info)";
    
    $session_stmt = $db->prepare($session_query);
    $session_stmt->bindParam(':session_id', $session_id);
    $session_stmt->bindParam(':employee_id', $employee['employee_id']);
    $session_stmt->bindParam(':device_info', $data->device_info);
    $session_stmt->execute();
    
    json_success(array(
        "employee_id" => $employee['employee_id'],
        "employee_number" => $employee['employee_number'],
        "name" => $employee['first_name'] . ' ' . $employee['last_name'],
        "session_id" => $session_id,
        "login_time" => $att_row ? $att_row['login_time'] : null
    ), "Login successful");

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
