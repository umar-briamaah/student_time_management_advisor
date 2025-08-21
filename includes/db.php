<?php
require_once __DIR__ . '/config.php';

class DB {
    private static $pdo = null;

    public static function conn() {
        if (self::$pdo === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $opts = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
                    PDO::ATTR_TIMEOUT => 10,
                    PDO::ATTR_PERSISTENT => false
                ];
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new Exception('Database connection failed. Please try again later.');
            }
        }
        return self::$pdo;
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $pdo = self::conn();
            $pdo->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}