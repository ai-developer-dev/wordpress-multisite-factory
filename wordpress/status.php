<?php
/**
 * Simple status check for Railway deployment
 * Always returns 200 OK with basic server info
 */

header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => time(),
    'server' => [
        'php_version' => phpversion(),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
    ],
    'railway' => [
        'port' => getenv('PORT'),
        'environment' => getenv('RAILWAY_ENVIRONMENT') ?: 'production'
    ]
];

echo json_encode($status, JSON_PRETTY_PRINT);