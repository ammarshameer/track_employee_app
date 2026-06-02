<?php
/**
 * Database Configuration for Employee Tracking System
 *
 * Credentials are read from environment variables with safe defaults
 * for local XAMPP development. In production, set DB_HOST, DB_NAME,
 * DB_USERNAME, and DB_PASSWORD in your environment.
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = getenv('DB_NAME')     ?: 'emp_track_db';
        $this->username = getenv('DB_USERNAME')  ?: 'root';
        $this->password = getenv('DB_PASSWORD')  ?: '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            // Do not expose connection details to clients
        }

        return $this->conn;
    }
}
?>
