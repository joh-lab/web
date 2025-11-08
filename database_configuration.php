<?php
/*
 * -----------------------------------------------------------------
 * DATABASE CONFIGURATION
 * -----------------------------------------------------------------
 *
 * PLEASE EDIT THE DETAILS BELOW TO MATCH YOUR DATABASE CREDENTIALS
 *
 */

define('DB_HOST', 'localhost');      
define('DB_USER', 'root');   // Your database username
define('DB_PASS', ''); // Your database password
define('DB_NAME', 'forevertunes_db');  // Your database name

/**
 * Creates a new database connection.
 * @return mysqli|null A mysqli connection object or null on failure.
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // Don't echo errors publicly in a production environment
        // error_log("Connection failed: " . $conn->connect_error);
        return null;
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Returns a Razorpay Key ID.
 * Replace with your actual Test or Live Key ID.
 *
 * @return string Your Razorpay Key ID
 */
function getRazorpayKeyId() {
    //
    // --- IMPORTANT ---
    //
    // REPLACE 'YOUR_KEY_ID' WITH YOUR ACTUAL RAZORPAY KEY ID
    //
    return 'rzp_test_Rc2fPJspYgFE7t';
}

/**
 * Returns a Razorpay Key Secret.
 * Replace with your actual Test or Live Key Secret.
 *
 * @return string Your Razorpay Key Secret
 */
function getRazorpayKeySecret() {
    //
    // --- IMPORTANT ---
    //
    // REPLACE 'YOUR_KEY_SECRET' WITH YOUR ACTUAL RAZORPAY KEY SECRET
    //
    return 'JH3P170i23UUblXzVhciLeXY';
}

?>