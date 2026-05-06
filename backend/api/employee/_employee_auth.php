<?php
/**
 * Shared auth helper for employee mobile APIs.
 * Validates session_id and returns employee row.
 */

function require_employee_session(PDO $db, string $session_id): array {
    $q = "SELECT es.employee_id, e.employee_number, e.first_name, e.last_name
          FROM employee_sessions es
          JOIN employees e ON es.employee_id = e.employee_id
          WHERE es.session_id = :session_id AND es.is_active = 1";
    $stmt = $db->prepare($q);
    $stmt->bindParam(':session_id', $session_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Invalid or expired session"));
        exit();
    }

    // Touch session last_activity
    try {
        $u = $db->prepare("UPDATE employee_sessions SET last_activity = NOW() WHERE session_id = :session_id");
        $u->bindParam(':session_id', $session_id);
        $u->execute();
    } catch (Exception $e) {
        // ignore
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

