<?php
require_once __DIR__ . '/config.php';

class DB {
    private static $pdo = null;

    public static function conn() {
        if (self::$pdo === null) {
            // PostgreSQL connection string
            $dsn = 'pgsql:host=' . DB_HOST . ';port=5432;dbname=' . DB_NAME . ';sslmode=require';
            
            $opts = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];
            
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
                
                // Set timezone
                self::$pdo->exec("SET timezone = 'UTC'");
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
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
            $stmt = $pdo->query('SELECT 1');
            return $stmt->fetch() === ['?column?' => 1];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get database info
     */
    public static function getInfo() {
        try {
            $pdo = self::conn();
            $stmt = $pdo->query('SELECT version()');
            $version = $stmt->fetch();
            return [
                'type' => 'PostgreSQL',
                'version' => $version['version'] ?? 'Unknown',
                'connected' => true
            ];
        } catch (Exception $e) {
            return [
                'type' => 'PostgreSQL',
                'version' => 'Unknown',
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Close database connection
     */
    public static function close() {
        if (self::$pdo !== null) {
            self::$pdo = null;
        }
    }
}

/**
 * Helper functions for backward compatibility
 */

function db_conn() {
    return DB::conn();
}

function db_test() {
    return DB::testConnection();
}

function db_info() {
    return DB::getInfo();
}
