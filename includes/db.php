<?php
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}

class Database {
    private $conn = null;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5
                ];
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
                $this->conn->query("SELECT 1");
            } catch (PDOException $e) {
                $error = "Database connection failed: " . $e->getMessage();
                error_log($error);
                die($error);
            }
        }
        return $this->conn;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    public function count($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}

$db = new Database();