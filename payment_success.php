<?php
// payment_success.php
require_once 'database_configuration.php';

// Retrieve info from URL
$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';

if (!$order_id || !$payment_id) {
    die('Invalid payment details.');
}

// Fetch order details
$conn = getDbConnection();
$query = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$query->bind_param("i", $order_id);
$query->execute();
$result = $query->get_result();
$order = $result->fetch_assoc();
$query->close();
$conn->close();

if (!$order) {
    die('Order not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Successful</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .card {
      max-width: 500px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-radius: 15px;
    }
    .success-icon {
      font-size: 60px;
      color: #28a745;
    }
  </style>
</head>
<body>
  <div class="card text-center p-4">
    <div class="card-body">
      <div class="success-icon mb-3">✅</div>
      <h3 class="card-title">Payment Successful!</h3>
      <p class="card-text text-muted mb-4">
        Thank you, <strong><?= htmlspecialchars($order['customer_name']) ?></strong>.<br>
        Your payment for <strong>₹<?= number_format($order['package_price'], 2) ?></strong> has been received.
      </p>

      <ul class="list-group text-start mb-3">
        <li class="list-group-item"><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?></li>
        <li class="list-group-item"><strong>Payment ID:</strong> <?= htmlspecialchars($payment_id) ?></li>
        <li class="list-group-item"><strong>Package:</strong> <?= htmlspecialchars($order['package_name']) ?></li>
      </ul>

      <a href="download_receipt.php?order_id=<?= urlencode($order_id) ?>&payment_id=<?= urlencode($payment_id) ?>" class="btn btn-success w-100">
        📄 Download Receipt
      </a>

      <a href="/" class="btn btn-link mt-3">Return to Homepage</a>
    </div>
  </div>
</body>
</html>
