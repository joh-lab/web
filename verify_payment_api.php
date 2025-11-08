<?php
// Set header to return JSON
header('Content-Type: application/json');

// Include database configuration
include 'db_config.php';

// Get incoming JSON data
$input = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => 'Invalid request.'];

// Check if all required data is present
if (isset($input['name'], $input['email'], $input['mobile'], $input['language'], $input['description'], $input['packageName'], $input['packagePrice'])) {
    
    $conn = getDbConnection();

    if ($conn) {
        // Sanitize inputs
        $name = $conn->real_escape_string($input['name']);
        $email = $conn->real_escape_string($input['email']);
        $mobile = $conn->real_escape_string($input['mobile']);
        $language = $conn->real_escape_string($input['language']);
        $description = $conn->real_escape_string($input['description']);
        $packageName = $conn->real_escape_string($input['packageName']);
        // Remove '₹' and ',' from price before casting
        $packagePrice = (float)str_replace([',', '₹'], '', $input['packagePrice']);

        // Prepare SQL to insert into 'orders'
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_mobile, song_language, song_description, package_name, package_price, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        
        $stmt->bind_param("ssssssd", $name, $email, $mobile, $language, $description, $packageName, $packagePrice);

        if ($stmt->execute()) {
            $order_id = $conn->insert_id; // Get the new order ID
            $response = [
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $order_id,
                'razorpay_key_id' => getRazorpayKeyId(), // Send key to JS
                'amount' => $packagePrice * 100, // Amount in paise
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_mobile' => $mobile
            ];
        } else {
            $response['message'] = 'Database error: ' . $stmt->error;
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $response['message'] = 'Failed to connect to database.';
    }
} else {
    $response['message'] = 'Missing required fields.';
    $response['received_data'] = $input; // For debugging
}

echo json_encode($response);
?>