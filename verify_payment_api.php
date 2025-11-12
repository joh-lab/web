<?php
/**
 * create_order.php
 * -----------------
 * Creates an order in the database + Razorpay (TEST MODE)
 * Returns JSON for frontend checkout.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting (safe for localhost)
if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}
error_reporting(E_ALL);

// Required dependencies
require_once 'vendor/autoload.php';
require_once 'db_config.php';
require_once 'razorpay_config.php';

use Razorpay\Api\Api;

// Default response
$response = ['success' => false, 'message' => 'Invalid request.'];

try {
    // Decode JSON from frontend
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input.');
    }

    // Validate all required fields
    $required = ['name', 'email', 'mobile', 'language', 'description', 'packageName', 'packagePrice'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Database connection
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    // Sanitize inputs
    $name        = $conn->real_escape_string(trim($input['name']));
    $email       = $conn->real_escape_string(trim($input['email']));
    $mobile      = $conn->real_escape_string(trim($input['mobile']));
    $language    = $conn->real_escape_string(trim($input['language']));
    $description = $conn->real_escape_string(trim($input['description']));
    $packageName = $conn->real_escape_string(trim($input['packageName']));
    $packagePrice = floatval(str_replace(['₹', ','], '', $input['packagePrice']));

    if ($packagePrice <= 0) {
        throw new Exception('Invalid package price.');
    }

    // Insert new order into local DB
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (customer_name, customer_email, customer_mobile, song_language, song_description, package_name, package_price, order_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->bind_param("ssssssd", $name, $email, $mobile, $language, $description, $packageName, $packagePrice);

    if (!$stmt->execute()) {
        throw new Exception('Database insert error: ' . $stmt->error);
    }

    $order_id = $conn->insert_id;

    // -------------------------------
    // 🔗 Create Razorpay Order (Test)
    // -------------------------------
    $keyId = getRazorpayKeyId();
    $keySecret = getRazorpayKeySecret();

    $api = new Api($keyId, $keySecret);

    $orderData = [
        'receipt' => 'order_rcpt_' . $order_id,
        'amount' => intval($packagePrice * 100), // paise
        'currency' => 'INR',
        'payment_capture' => 1 // auto-capture
    ];

    $razorpayOrder = $api->order->create($orderData);

    if (!isset($razorpayOrder['id'])) {
        throw new Exception('Failed to create Razorpay order.');
    }

    $razorpay_order_id = $razorpayOrder['id'];

    // Save Razorpay Order ID in DB
    $update = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
    $update->bind_param("si", $razorpay_order_id, $order_id);
    $update->execute();
    $update->close();

    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Order created successfully.',
        'order_id' => $order_id,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_key_id' => $keyId, // needed for JS checkout
        'amount' => intval($packagePrice * 100),
        'packageName' => $packageName,
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_mobile' => $mobile
    ];

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

// Output JSON
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
