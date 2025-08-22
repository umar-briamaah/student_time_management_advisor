<?php
/**
 * Database connection class using PDO
 */

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
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
                
                log_message('info', 'Database connection established', [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'user' => DB_USER
                ]);
                
            } catch (PDOException $e) {
                log_message('error', 'Database connection failed', [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'user' => DB_USER,
                    'error' => $e->getMessage()
                ]);
                
                if (DEBUG) {
                    throw $e;
                } else {
                    die('Database connection failed. Please check your configuration.');
                }
            }
        }
        
        return self::$pdo;
    }
    
    public static function close() {
        self::$pdo = null;
        log_message('info', 'Database connection closed');
    }
    
    public static function test() {
        try {
            $pdo = self::conn();
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();
            
            log_message('info', 'Database connection test successful');
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Database connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}