<?php
/**
 * WordPress Health Check Endpoint for Railway
 * This file provides a comprehensive health check for Railway deployment monitoring.
 */

// Prevent direct access from browsers
if (!defined('ABSPATH')) {
    // Simple health check without WordPress loading
    header('Content-Type: application/json');
    
    $health_data = [
        'status' => 'ok',
        'timestamp' => time(),
        'version' => '1.0.0',
        'environment' => getenv('RAILWAY_ENVIRONMENT') ?: 'development',
        'server' => [
            'php_version' => phpversion(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ],
        'extensions' => [
            'gd' => extension_loaded('gd'),
            'mysqli' => extension_loaded('mysqli'),
            'zip' => extension_loaded('zip'),
            'curl' => extension_loaded('curl'),
            'mbstring' => extension_loaded('mbstring'),
            'opcache' => extension_loaded('opcache')
        ]
    ];
    
    // Check database connection if DATABASE_URL is available
    if (getenv('DATABASE_URL')) {
        $database_url = parse_url(getenv('DATABASE_URL'));
        try {
            $pdo = new PDO(
                "mysql:host={$database_url['host']};port=" . ($database_url['port'] ?: 3306) . ";dbname=" . ltrim($database_url['path'], '/'),
                $database_url['user'],
                $database_url['pass'],
                [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
            $stmt = $pdo->query('SELECT VERSION() as version');
            $db_version = $stmt->fetch(PDO::FETCH_ASSOC);
            $health_data['database'] = [
                'status' => 'connected',
                'version' => $db_version['version']
            ];
        } catch (PDOException $e) {
            $health_data['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
            $health_data['status'] = 'degraded';
            http_response_code(503);
        }
    } else {
        $health_data['database'] = [
            'status' => 'not_configured',
            'message' => 'DATABASE_URL not set'
        ];
    }
    
    // Check file system permissions
    $uploads_dir = '/var/www/html/wp-content/uploads';
    $health_data['filesystem'] = [
        'uploads_writable' => is_writable($uploads_dir),
        'uploads_exists' => file_exists($uploads_dir)
    ];
    
    // Check if required directories exist
    $required_dirs = [
        '/var/www/html/wp-content/plugins',
        '/var/www/html/wp-content/themes',
        '/var/www/html/wp-content/uploads'
    ];
    
    foreach ($required_dirs as $dir) {
        if (!file_exists($dir)) {
            $health_data['status'] = 'degraded';
            $health_data['filesystem']['missing_directories'][] = $dir;
        }
    }
    
    echo json_encode($health_data, JSON_PRETTY_PRINT);
    exit;
}

// If WordPress is loaded, provide more detailed health check
header('Content-Type: application/json');

$health_data = [
    'status' => 'ok',
    'timestamp' => time(),
    'wordpress' => [
        'version' => get_bloginfo('version'),
        'multisite' => is_multisite(),
        'site_count' => is_multisite() ? get_blog_count() : 1
    ],
    'plugins' => [
        'site_factory' => is_plugin_active('site-factory/site-factory.php')
    ],
    'server' => [
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage(true),
        'memory_limit' => ini_get('memory_limit')
    ]
];

echo json_encode($health_data, JSON_PRETTY_PRINT);
