<?php
/**
 * Campus Resource Sharing Platform
 * Database Connection using PHP PDO
 */

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $dbName = 'campus_resource_sharing';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';

    /**
     * Get singleton PDO connection instance
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Check if environment variables or local override exists
            $host = getenv('DB_HOST') ?: self::$host;
            $port = getenv('DB_PORT') ?: self::$port;
            $dbName = getenv('DB_NAME') ?: self::$dbName;
            $username = getenv('DB_USER') ?: self::$username;
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::$password;

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=" . self::$charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::$charset
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // In production, log error instead of exposing details
                error_log("Database Connection Failed: " . $e->getMessage());
                die("A database error occurred. Please ensure MySQL is running in XAMPP and the database is configured.");
            }
        }

        return self::$instance;
    }
}
