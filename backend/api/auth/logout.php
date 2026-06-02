<?php
/**
 * Employee Logout API
 * Handles employee logout and captures logout image with GPS
 */

include_once '../../config/helpers.php';

require_method('POST');

$db = get_db();
$data = get_json_input();

if (empty($data->session_id)) {
    json_error("Session ID is required");
}

try {
    // Verify session and get employee details
    $session_query = "SELECT es.employee_id, e.employee_number, e.first_name, e.last_name 
                    FROM employee_sessions es
                    JOIN employees e ON es.employee_id = e.employee_id
                    WHERE es.session_id = :session_id AND es.is_active = 1";
    
    $session_stmt = $db->prepare($session_query);
    $session_stmt->bindParam(':session_id', $data->session_id);
    $session_stmt->execute();
    
    if ($session_stmt->rowCount() == 0) {
        json_error("Invalid session", 401);
    }

    $employee = $session_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle logout image if provided
    $image_path = null;
    if (!empty($data->logout_image)) {
        $image_path = saveLogoutImage($data->logout_image, $employee['employee_id']);
    }
    
    // Update attendance record with logout information
    $attendance_query = "UPDATE attendance 
                       SET logout_time = NOW(), 
                           logout_image_path = :image_path, 
                           logout_latitude = :latitude, 
                           logout_longitude = :longitude,
                           total_hours = TIMESTAMPDIFF(MINUTE, login_time, NOW()) / 60,
                           total_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW()),
                           work_duration = SEC_TO_TIME(TIMESTAMPDIFF(SECOND, login_time, NOW()))
                       WHERE employee_id = :employee_id 
                       AND logout_time IS NULL
                       ORDER BY login_time DESC 
                       LIMIT 1";
    
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->bindParam(':image_path', $image_path);
    $attendance_stmt->bindParam(':latitude', $data->latitude);
    $attendance_stmt->bindParam(':longitude', $data->longitude);
    $attendance_stmt->bindParam(':employee_id', $employee['employee_id']);
    
    if (!$attendance_stmt->execute()) {
        json_error("Failed to update attendance", 500);
    }
    
    // Deactivate session
    $deactivate_query = "UPDATE employee_sessions 
                       SET is_active = 0 
                       WHERE session_id = :session_id";
    
    $deactivate_stmt = $db->prepare($deactivate_query);
    $deactivate_stmt->bindParam(':session_id', $data->session_id);
    $deactivate_stmt->execute();
    
    $hours_query = "SELECT login_time, logout_time, total_hours, total_seconds, work_duration FROM attendance 
                  WHERE employee_id = :employee_id 
                  ORDER BY logout_time DESC 
                  LIMIT 1";
    
    $hours_stmt = $db->prepare($hours_query);
    $hours_stmt->bindParam(':employee_id', $employee['employee_id']);
    $hours_stmt->execute();
    $hours_result = $hours_stmt->fetch(PDO::FETCH_ASSOC);
    
    json_success(array(
        "employee_number" => $employee['employee_number'],
        "name" => $employee['first_name'] . ' ' . $employee['last_name'],
        "total_hours" => $hours_result['total_hours'] ?? 0,
        "login_time" => $hours_result['login_time'] ?? null,
        "logout_time" => $hours_result['logout_time'] ?? null,
        "duration_hms" => $hours_result['work_duration'] ?? null
    ), "Logout successful");

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}

/**
 * Save logout image to server
 */
function saveLogoutImage($base64_image, $employee_id) {
    try {
        $upload_dir = '../../uploads/logout_images/' . date('Y/m/d') . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image));
        $filename = $employee_id . '_' . date('His') . '_' . uniqid() . '.jpg';
        $file_path = $upload_dir . $filename;
        
        if (file_put_contents($file_path, $image_data)) {
            return $file_path;
        }
    } catch (Exception $e) {
        error_log("Image save error: " . $e->getMessage());
    }
    return null;
}
?>
