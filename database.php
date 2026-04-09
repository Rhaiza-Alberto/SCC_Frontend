 <?php
date_default_timezone_set('Asia/Manila'); // ✅ GLOBAL FIX

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

// ✅ SINGLE global function
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
?>