<?php
class Database {
    private $host = "localhost";
    private $db_name = "database_name"; // Replace with your actual database name
    private $username = "databsae_username"; // Replace with your actual username
    private $password = "your_password_here"; // Replace with your actual password
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>