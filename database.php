<?php
date_default_timezone_set('Asia/Manila'); // GLOBAL FIX

// Prevent browser caching to ensure real-time updates and prevent stale data
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
}

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "scc_database";

    public function connect() {
        try {
            return new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
}

// SINGLE global function
if (!function_exists('get_db')) {
    function get_db() {
        static $conn;
        if (!$conn) {
            $db = new Database();
            $conn = $db->connect();
        }
        return $conn;
    }
}

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'eggvelasco@gmail.com'); 
define('SMTP_PASS', 'xgbx sljs uuqn fwxm');
define('SMTP_PORT', 587);

?>