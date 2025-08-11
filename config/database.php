<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'ecommerce';
    private $username = 'root';
    private $password = '';
    private $pdo;

    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function prepare($sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            error_log("SQL prepare failed: " . $e->getMessage());
            throw new Exception("Database error occurred.");
        }
    }

    public function execute($stmt, $params = []) {
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("SQL execute failed: " . $e->getMessage());
            throw new Exception("Database operation failed.");
        }
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}
?>