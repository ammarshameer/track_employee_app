<?php
/**
 * Shared helper functions for API endpoints.
 *
 * Centralises the boilerplate that was previously duplicated in every
 * API file: CORS/database bootstrap, JSON responses, admin auth, HTTP
 * method checks, and request-body parsing.
 */

include_once __DIR__ . '/cors.php';
include_once __DIR__ . '/database.php';

// ── JSON response helpers ────────────────────────────────────────────

/**
 * Send a JSON response and terminate.
 */
function json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

/**
 * Send a JSON error response and terminate.
 */
function json_error(string $message, int $status = 400): void {
    json_response(array("success" => false, "message" => $message), $status);
}

/**
 * Send a JSON success response and terminate.
 */
function json_success($data, string $message = '', int $status = 200): void {
    $payload = array("success" => true);
    if ($message !== '') {
        $payload["message"] = $message;
    }
    $payload["data"] = $data;
    json_response($payload, $status);
}

// ── Request helpers ──────────────────────────────────────────────────

/**
 * Ensure the request uses the expected HTTP method.
 * Responds with 405 and terminates if the check fails.
 *
 * @param string|array $allowed  A single method string or an array of
 *                                allowed methods.
 */
function require_method($allowed): void {
    if (is_string($allowed)) {
        $allowed = [$allowed];
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed, true)) {
        json_error("Method not allowed", 405);
    }
}

/**
 * Parse and return the JSON request body.
 */
function get_json_input(): object {
    return json_decode(file_get_contents("php://input")) ?? new \stdClass();
}

// ── Auth helpers ─────────────────────────────────────────────────────

/**
 * Start a session (if not already started) and verify the admin is
 * logged in.  Responds with 401 and terminates on failure.
 */
function require_admin_auth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_id'])) {
        json_error("Admin authentication required", 401);
    }
}

// ── Database helper ──────────────────────────────────────────────────

/**
 * Return a ready-to-use PDO connection.
 */
function get_db(): PDO {
    $database = new Database();
    return $database->getConnection();
}
?>
