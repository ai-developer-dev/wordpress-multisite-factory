<?php
// Simple health check for Railway
header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'message' => 'WordPress is running'
]);
?>