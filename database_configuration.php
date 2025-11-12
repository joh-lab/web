<?php
/*
 * -----------------------------------------------------------------
 * DATABASE CONFIGURATION 
 * -----------------------------------------------------------------
 * Securely loads credentials from .env file using phpdotenv.
 * Works seamlessly with both local XAMPP and production servers.
 * -----------------------------------------------------------------
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// ---------------------------------------------------------------
// 🧩 Load Environment Variables
// ---------------------------------------------------------------
if (!getenv('DB_HOST')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad(); // safeLoad() avoids fatal errors if .env missing
}

// ---------------------------------------------------------------
// ⚙️ Database Connection Function
// ---------------------------------------------------------------
/**
 * Establish a secure MySQL connection.
 *
 * @return mysqli|null  Returns a mysqli connection object or null on failure.
 */
function getDbConnection() {
    $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? '127.0.0.1';
    $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
    $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'forevertunes_db';

    // ✅ Verify mysqli is available before creating connection
    if (!class_exists('mysqli')) {
        error_log("❌ PHP MySQLi extension not enabled. Enable 'extension=mysqli' in php.ini.");
        return null;
    }

    // Create connection
    $conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    // Check connection errors
    if ($conn->connect_errno) {
        error_log("❌ Database connection failed ({$conn->connect_errno}): {$conn->connect_error}");
        return null;
    }

    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        error_log("⚠️ Failed to set charset: " . $conn->error);
    }

    return $conn;
}

// ---------------------------------------------------------------
// 🧹 Safely Close Connection
// ---------------------------------------------------------------
/**
 * Safely close a MySQL connection if open.
 *
 * @param mysqli|null $conn
 */
function closeDbConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
