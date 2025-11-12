<?php
require_once __DIR__ . '/database_configuration.php';

$conn = getDbConnection();

if ($conn) {
    echo "✅ Database connected successfully!<br>";
    echo "DB Host: " . ($conn->host_info ?? 'unknown');
    closeDbConnection($conn);
} else {
    echo "❌ Database connection failed. Check logs or .env settings.";
}
?>
