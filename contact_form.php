<?php
// Set header to return JSON
header('Content-Type: application/json');

// Include database configuration
include 'db_config.php';

// Get incoming JSON data
$input = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => 'Invalid request.'];

// Check if all required data is present
if (isset($input['name'], $input['email'], $input['subject'], $input['message'])) {
    
    $conn = getDbConnection();

    if ($conn) {
        // Sanitize inputs
        $name = $conn->real_escape_string($input['name']);
        $email = $conn->real_escape_string($input['email']);
        $subject = $conn->real_escape_string($input['subject']);
        $message = $conn->real_escape_string($input['message']);

        // Prepare SQL to insert into 'contact_inquiries'
        $stmt = $conn->prepare("INSERT INTO contact_inquiries (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)");
        
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Your message has been sent successfully!'
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