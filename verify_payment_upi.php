<?php
// Set header to return JSON
header('Content-Type: application/json');

// Include database configuration
include 'db_config.php';

// Get incoming JSON data
$input = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => 'Invalid payment data.'];

// Check if we have the necessary IDs
if (isset($input['razorpay_payment_id'], $input['order_id'], $input['amount'])) {
    
    $conn = getDbConnection();

    if ($conn) {
        //
        // --- PRODUCTION NOTE ---
        // In a real production app, you would also:
        // 1. Include the Razorpay PHP SDK.
        // 2. Fetch the payment ID from Razorpay: $api->payment->fetch($input['razorpay_payment_id'])
        // 3. Verify the 'status' is 'captured'.
        // 4. Verify the 'amount' and 'currency' match your records.
        // This simple example trusts the client, which is fine for this demo.
        //
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // 1. Sanitize inputs
            $razorpay_payment_id = $conn->real_escape_string($input['razorpay_payment_id']);
            $order_id = (int)$input['order_id'];
            $amount = (float)($input['amount'] / 100); // Convert from paise back to rupees

            // 2. Insert into 'payments' table
            $stmt_payment = $conn->prepare("INSERT INTO payments (order_id, razorpay_payment_id, payment_status, payment_amount) VALUES (?, ?, 'Success', ?)");
            $stmt_payment->bind_param("isd", $order_id, $razorpay_payment_id, $amount);
            $stmt_payment->execute();
            $stmt_payment->close();

            // 3. Update 'orders' table status to 'Paid'
            $stmt_order = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE order_id = ?");
            $stmt_order->bind_param("i", $order_id);
            $stmt_order->execute();
            $stmt_order->close();
            
            // If all queries succeeded, commit the transaction
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Payment verified and order updated.'
            ];

        } catch (mysqli_sql_exception $exception) {
            // If any query failed, roll back the transaction
            $conn->rollback();
            $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
        }
        
        $conn->close();
    } else {
        $response['message'] = 'Failed to connect to database.';
    }
}

echo json_encode($response);
?>