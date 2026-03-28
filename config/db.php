<?php
/**
 * Database Configuration
 * University Result Management System
 * 
 * Uses PDO for secure database connections with prepared statements.
 * Update the credentials below to match your local setup.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'ResultManagement');
define('DB_USER', 'root');
define('DB_PASS', '');         // Default XAMPP/WAMP password is empty
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * 
 * @return PDO Database connection instance
 * @throws PDOException If connection fails
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error and show a generic message
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

/**
 * Start session if not already started
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Get the base URL of the application
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Navigate to project root
    $basePath = str_replace('\\', '/', $path);
    if (strpos($basePath, '/config') !== false) {
        $basePath = dirname($basePath);
    } elseif (strpos($basePath, '/admin') !== false || strpos($basePath, '/student') !== false || strpos($basePath, '/exports') !== false) {
        $basePath = dirname($basePath);
    }
    
    return $protocol . '://' . $host . rtrim($basePath, '/');
}
