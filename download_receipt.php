<?php
require_once 'database_configuration.php';

$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';

if (!$order_id || !$payment_id) {
    die('Invalid parameters.');
}

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

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=receipt_order_$order_id.html");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Payment Receipt</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h2 { text-align: center; }
    .receipt { border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
    .info { margin: 10px 0; }
    .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #555; }
  </style>
</head>
<body>
  <div class="receipt">
    <h2>Payment Receipt</h2>
    <p class="info"><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p class="info"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
    <p class="info"><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?></p>
    <p class="info"><strong>Payment ID:</strong> <?= htmlspecialchars($payment_id) ?></p>
    <p class="info"><strong>Package:</strong> <?= htmlspecialchars($order['package_name']) ?></p>
    <p class="info"><strong>Amount Paid:</strong> ₹<?= number_format($order['package_price'], 2) ?></p>
    <p class="info"><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['updated_at'])) ?></p>

    <div class="footer">
      ✅ Payment received via Razorpay.<br>
      Thank you for your purchase!
    </div>
  </div>
</body>
</html>
