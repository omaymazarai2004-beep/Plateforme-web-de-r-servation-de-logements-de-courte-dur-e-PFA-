<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'logements_db');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public static function prepare($sql) {
        return self::getInstance()->getConnection()->prepare($sql);
    }
    
    public static function execute($sql, $params = []) {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function lastInsertId() {
        return self::getInstance()->getConnection()->lastInsertId();
    }
}
?>