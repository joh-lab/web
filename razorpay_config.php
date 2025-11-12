<?php
/**
 * -------------------------------------------------------------
 * Razorpay Configuration Loader
 * -------------------------------------------------------------
 * Securely loads Razorpay credentials from the .env file using
 * vlucas/phpdotenv and provides helper functions for access.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// -------------------------------------------------------------
// 1. Load environment variables if not already set
// -------------------------------------------------------------
if (!getenv('RAZORPAY_KEY_ID') || !getenv('RAZORPAY_KEY_SECRET')) {
    try {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad(); // won't throw an error if .env is missing
    } catch (Exception $e) {
        // Log load errors silently — don’t break JSON responses
        error_log("Dotenv load error: " . $e->getMessage());
    }
}

// -------------------------------------------------------------
// 2. Helper functions to safely retrieve Razorpay credentials
// -------------------------------------------------------------

/**
 * Get Razorpay Key ID
 * @return string|null
 */
function getRazorpayKeyId() {
    $key = $_ENV['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID') ?? null;
    if (empty($key)) {
        error_log("Missing RAZORPAY_KEY_ID in .env");
    }
    return $key;
}

/**
 * Get Razorpay Key Secret
 * @return string|null
 */
function getRazorpayKeySecret() {
    $secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET') ?? null;
    if (empty($secret)) {
        error_log("Missing RAZORPAY_KEY_SECRET in .env");
    }
    return $secret;
}
