<?php
/**
 * -----------------------------------------------------------------
 * VERIFY PAYMENT ENDPOINT (Final Version)
 * -----------------------------------------------------------------
 * ✅ Verifies Razorpay signature
 * ✅ Updates `orders` table
 * ✅ Inserts into `payments` table
 * ✅ Inserts user into `users` table (if new)
 * ✅ Returns JSON with redirect URL (for JS window.location)
 * -----------------------------------------------------------------
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'vendor/autoload.php';
require_once 'database_configuration.php';
require_once 'razorpay_config.php';

use Razorpay\Api\Api;

$response = ['success' => false, 'message' => 'Invalid request.'];
$debug = [];

try {
    // --- Parse JSON input ---
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // --- Validate required fields ---
    $required = ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'order_id'];
    foreach ($required as $key) {
        if (empty($data[$key])) throw new Exception("Missing required field: $key");
    }

    $debug[] = '✅ Required fields received.';

    $razorpay_order_id = $data['razorpay_order_id'];
    $razorpay_payment_id = $data['razorpay_payment_id'];
    $razorpay_signature = $data['razorpay_signature'];
    $local_order_id = (int) $data['order_id'];

    // --- Razorpay Signature Verification ---
    $api = new Api(getRazorpayKeyId(), getRazorpayKeySecret());
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ]);

    $debug[] = '✅ Razorpay payment signature verified successfully.';

    // --- Connect DB ---
    $conn = getDbConnection();
    if (!$conn) throw new Exception('Database connection failed.');

    // --- Fetch order details ---
    $orderQuery = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $orderQuery->bind_param("i", $local_order_id);
    $orderQuery->execute();
    $orderResult = $orderQuery->get_result();
    $order = $orderResult->fetch_assoc();
    $orderQuery->close();

    if (!$order) {
        throw new Exception("Order not found for ID: $local_order_id");
    }

    // --- 1️⃣ Update order status to PAID ---
    $update = $conn->prepare("
        UPDATE orders 
        SET razorpay_payment_id = ?, order_status = 'Paid', updated_at = NOW()
        WHERE id = ? AND razorpay_order_id = ?
    ");
    $update->bind_param("sis", $razorpay_payment_id, $local_order_id, $razorpay_order_id);
    if (!$update->execute()) {
        throw new Exception('Failed to update order status: ' . $update->error);
    }
    $update->close();
    $debug[] = "✅ Order #$local_order_id marked as PAID.";

    // --- 2️⃣ Insert or update user record ---
    $user_email = $order['customer_email'];
    $user_name  = $order['customer_name'];

    // Check if user already exists
    $userCheck = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userCheck->bind_param("s", $user_email);
    $userCheck->execute();
    $userCheck->store_result();

    if ($userCheck->num_rows === 0) {
        $userInsert = $conn->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
        $userInsert->bind_param("ss", $user_name, $user_email);
        if (!$userInsert->execute()) {
            throw new Exception('Failed to insert new user: ' . $userInsert->error);
        }
        $userInsert->close();
        $debug[] = "👤 New user inserted: $user_name <$user_email>";
    } else {
        $debug[] = "👤 Existing user found: $user_email";
    }
    $userCheck->close();

    // --- 3️⃣ Insert into payments table ---
    $paymentInsert = $conn->prepare("
        INSERT INTO payments (payment_id, razorpay_order_id, amount, status, payment_method, created_at)
        VALUES (?, ?, ?, 'Captured', 'Razorpay', NOW())
    ");
    $paymentInsert->bind_param("ssd", $razorpay_payment_id, $razorpay_order_id, $order['package_price']);

    if (!$paymentInsert->execute()) {
        throw new Exception('Failed to insert payment record: ' . $paymentInsert->error);
    }
    $paymentInsert->close();

    $debug[] = "💳 Payment recorded successfully in payments table.";

    $conn->close();

    // --- 4️⃣ Success Response with redirect URL ---
    $redirect_url = "payment_success.php?order_id={$local_order_id}&payment_id={$razorpay_payment_id}";

    $response = [
        'success' => true,
        'message' => 'Payment verified successfully.',
        'order_id' => $local_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'redirect_url' => $redirect_url
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error: ' . $e->getMessage();
    $debug[] = '❌ ' . $e->getMessage();
}

// --- Debug Info (Local Only) ---
if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
    $response['debug'] = $debug;
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
