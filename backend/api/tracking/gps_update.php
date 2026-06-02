<?php
/**
 * GPS Tracking Update API
 * Receives GPS coordinates from mobile app every 5 minutes
 */

include_once '../../config/helpers.php';

require_method('POST');

$db = get_db();
$data = get_json_input();

if (empty($data->session_id) || !isset($data->latitude) || !isset($data->longitude)) {
    json_error("Session ID, latitude and longitude are required");
}

try {
    // Verify active session
    $session_query = "SELECT employee_id FROM employee_sessions 
                    WHERE session_id = :session_id AND is_active = 1";
    
    $session_stmt = $db->prepare($session_query);
    $session_stmt->bindParam(':session_id', $data->session_id);
    $session_stmt->execute();
    
    if ($session_stmt->rowCount() == 0) {
        json_error("Invalid or expired session", 401);
    }

    $session = $session_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Insert GPS tracking data
    $tracking_query = "INSERT INTO gps_tracking 
                     (employee_id, latitude, longitude, accuracy, speed, altitude, tracking_date) 
                     VALUES (:employee_id, :latitude, :longitude, :accuracy, :speed, :altitude, CURDATE())";
    
    $tracking_stmt = $db->prepare($tracking_query);
    $employee_id = $session['employee_id'];
    $latitude = $data->latitude;
    $longitude = $data->longitude;
    $accuracy = $data->accuracy ?? null;
    $speed = $data->speed ?? null;
    $altitude = $data->altitude ?? null;
    
    $tracking_stmt->bindParam(':employee_id', $employee_id);
    $tracking_stmt->bindParam(':latitude', $latitude);
    $tracking_stmt->bindParam(':longitude', $longitude);
    $tracking_stmt->bindParam(':accuracy', $accuracy);
    $tracking_stmt->bindParam(':speed', $speed);
    $tracking_stmt->bindParam(':altitude', $altitude);
    
    if (!$tracking_stmt->execute()) {
        json_error("Failed to save GPS data", 500);
    }
    
    // Update session last activity
    $update_session = "UPDATE employee_sessions 
                     SET last_activity = NOW() 
                     WHERE session_id = :session_id";
    
    $update_stmt = $db->prepare($update_session);
    $update_stmt->bindParam(':session_id', $data->session_id);
    $update_stmt->execute();
    
    json_response(array(
        "success" => true,
        "message" => "GPS location updated successfully",
        "timestamp" => date('Y-m-d H:i:s')
    ));

} catch (PDOException $e) {
    json_error("Database error: " . $e->getMessage(), 500);
}
?>
