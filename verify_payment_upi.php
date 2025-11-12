<?php
/**
 * ---------------------------------------------------------------
 * VERIFY PAYMENT (Razorpay + Database Integration)
 * ---------------------------------------------------------------
 * This endpoint:
 * ✅ Verifies payment signature from Razorpay.
 * ✅ Fetches payment details from Razorpay API.
 * ✅ Updates `orders` and `payments` tables accordingly.
 * ✅ Works in TEST MODE with your Razorpay test keys.
 * ---------------------------------------------------------------
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/razorpay_config.php';

use Razorpay\Api\Api;

$response = ['success' => false, 'message' => 'Invalid request data.'];

try {
    // Step 1️⃣: Decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON received.');
    }

    // Step 2️⃣: Validate required fields
    $required = ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature', 'order_id'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing field: $field");
        }
    }

    // Step 3️⃣: Connect to DB
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    // Step 4️⃣: Initialize Razorpay API
    $api = new Api(getRazorpayKeyId(), getRazorpayKeySecret());

    // Step 5️⃣: Verify the Razorpay signature
    $attributes = [
        'razorpay_order_id'   => $input['razorpay_order_id'],
        'razorpay_payment_id' => $input['razorpay_payment_id'],
        'razorpay_signature'  => $input['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // Step 6️⃣: Fetch payment details from Razorpay (for amount, method, etc.)
    $payment = $api->payment->fetch($input['razorpay_payment_id']);

    $status  = $payment['status'];         // Expected: "captured"
    $amount  = $payment['amount'] / 100;   // Convert paise to rupees
    $method  = $payment['method'] ?? 'N/A';

    if ($status !== 'captured') {
        throw new Exception("Payment not captured (status: $status)");
    }

    // Step 7️⃣: Record the payment and mark the order as paid
    $conn->begin_transaction();

    $order_id = (int)$input['order_id'];
    $razorpay_order_id   = $conn->real_escape_string($input['razorpay_order_id']);
    $razorpay_payment_id = $conn->real_escape_string($input['razorpay_payment_id']);
    $payment_method      = $conn->real_escape_string($method);

    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (payment_id, razorpay_order_id, amount, status, payment_method)
        VALUES (?, ?, ?, 'captured', ?)
    ");
    $stmt->bind_param("ssds", $razorpay_payment_id, $razorpay_order_id, $amount, $payment_method);
    $stmt->execute();
    $stmt->close();

    // Update order as Paid
    $update = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE id = ?");
    $update->bind_param("i", $order_id);
    $update->execute();
    $update->close();

    $conn->commit();

    $response = [
        'success' => true,
        'message' => '✅ Payment verified and order updated successfully.',
        'payment_id' => $razorpay_payment_id,
        'order_id' => $order_id,
        'status' => $status,
        'amount' => $amount,
        'method' => $method
    ];

} catch (Exception $e) {
    if (isset($conn) && $conn->errno === 0) {
        $conn->rollback();
    }
    $response['message'] = '❌ Error: ' . $e->getMessage();
    error_log('Payment verification failed: ' . $e->getMessage());
}

if (isset($conn)) $conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
