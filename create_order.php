<?php
/**
 * -----------------------------------------------------------------
 * CREATE ORDER ENDPOINT (Final Stable Version)
 * -----------------------------------------------------------------
 * Handles creation of local order + Razorpay order ID generation
 * -----------------------------------------------------------------
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'vendor/autoload.php';
require 'database_configuration.php';
require 'razorpay_config.php';

use Razorpay\Api\Api;

$response = ['success' => false, 'message' => 'Invalid request.'];
$debug = [];

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No JSON input received.');
    }

    // Validate required fields
    $required = ['name', 'email', 'mobile', 'language', 'description', 'packageName', 'packagePrice'];
    foreach ($required as $key) {
        if (empty($input[$key])) {
            throw new Exception("Missing required field: $key");
        }
    }

    $debug[] = '✅ All required fields received.';

    // Connect DB
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    $debug[] = '✅ Database connected.';

    // Sanitize and format input
    $name        = $conn->real_escape_string(trim($input['name']));
    $email       = $conn->real_escape_string(trim($input['email']));
    $mobile      = $conn->real_escape_string(trim($input['mobile']));
    $language    = $conn->real_escape_string(trim($input['language']));
    $description = $conn->real_escape_string(trim($input['description']));
    $packageName = $conn->real_escape_string(trim($input['packageName']));
    $packagePrice = (float) str_replace(['₹', ',', ' '], '', $input['packagePrice']);

    if ($packagePrice <= 0) {
        throw new Exception('Invalid package price.');
    }

    $debug[] = "💰 Package: {$packageName}, Price: {$packagePrice}";

    // Insert local order
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (customer_name, customer_email, customer_mobile, song_language, song_description, package_name, package_price, order_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->bind_param("ssssssd", $name, $email, $mobile, $language, $description, $packageName, $packagePrice);

    if (!$stmt->execute()) {
        throw new Exception('DB insert error: ' . $stmt->error);
    }

    $order_id = $conn->insert_id;
    $debug[] = "🆕 Local order created (ID: $order_id)";

    // Razorpay order creation
    $api = new Api(getRazorpayKeyId(), getRazorpayKeySecret());
    $orderData = [
        'receipt'         => 'order_rcpt_' . $order_id,
        'amount'          => $packagePrice * 100, // paise
        'currency'        => 'INR',
        'payment_capture' => 1
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpay_order_id = $razorpayOrder['id'];

    $debug[] = "🪙 Razorpay order created (ID: $razorpay_order_id)";

    // Update DB with Razorpay order ID
    $update = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
    $update->bind_param("si", $razorpay_order_id, $order_id);
    $update->execute();
    $update->close();

    $debug[] = "✅ Local DB updated with Razorpay order ID.";

    $stmt->close();
    $conn->close();

    // Final response
    $response = [
        'success'           => true,
        'message'           => 'Order created successfully.',
        'order_id'          => $order_id,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_key_id'   => getRazorpayKeyId(),
        'amount'            => $packagePrice * 100,
        'packageName'       => $packageName,
        'customer_name'     => $name,
        'customer_email'    => $email,
        'customer_mobile'   => $mobile
    ];

    $debug[] = '✅ Order process completed successfully.';
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    $debug[] = '❌ Exception: ' . $e->getMessage();
}

// Include debug info in localhost mode
if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
    $response['debug'] = $debug;
}

echo json_encode($response);
exit;
?>
