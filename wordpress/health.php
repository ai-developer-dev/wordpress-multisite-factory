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
        'environment' => getenv('RAILWAY_ENVIRONMENT') ?: 'production',
        'railway' => [
            'port' => getenv('PORT'),
            'static_url' => getenv('RAILWAY_STATIC_URL'),
            'deployment_id' => getenv('RAILWAY_DEPLOYMENT_ID'),
            'service_id' => getenv('RAILWAY_SERVICE_ID')
        ],
        'server' => [
            'php_version' => phpversion(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
        ],
        'extensions' => [
            'gd' => extension_loaded('gd'),
            'mysqli' => extension_loaded('mysqli'),
            'zip' => extension_loaded('zip'),
            'curl' => extension_loaded('curl'),
            'mbstring' => extension_loaded('mbstring'),
            'opcache' => extension_loaded('opcache'),
            'exif' => extension_loaded('exif'),
            'intl' => extension_loaded('intl')
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
                'version' => $db_version['version'],
                'host' => $database_url['host']
            ];
        } catch (PDOException $e) {
            $health_data['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error_code' => $e->getCode()
            ];
            $health_data['status'] = 'degraded';
            // Don't return 503 for database issues - Railway needs app to be "healthy" for basic checks
        }
    } else {
        // Try fallback connection with individual env vars
        $db_host = getenv('WORDPRESS_DB_HOST');
        $db_name = getenv('WORDPRESS_DB_NAME');
        $db_user = getenv('WORDPRESS_DB_USER');
        $db_pass = getenv('WORDPRESS_DB_PASSWORD');
        
        if ($db_host && $db_name && $db_user !== false) {
            try {
                $pdo = new PDO(
                    "mysql:host={$db_host};dbname={$db_name}",
                    $db_user,
                    $db_pass,
                    [
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]
                );
                $stmt = $pdo->query('SELECT VERSION() as version');
                $db_version = $stmt->fetch(PDO::FETCH_ASSOC);
                $health_data['database'] = [
                    'status' => 'connected',
                    'version' => $db_version['version'],
                    'connection_type' => 'individual_vars'
                ];
            } catch (PDOException $e) {
                $health_data['database'] = [
                    'status' => 'error',
                    'message' => 'Database connection failed (fallback)',
                    'error_code' => $e->getCode()
                ];
                $health_data['status'] = 'degraded';
                // Don't return 503 for database issues - Railway needs app to be "healthy" for basic checks
            }
        } else {
            $health_data['database'] = [
                'status' => 'not_configured',
                'message' => 'No database configuration found'
            ];
        }
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
            $health_data['filesystem']['missing_directories'][] = $dir;
        }
    }
    
    // Always return 200 OK for basic health check - Railway needs this
    // Database issues are reported but don't fail the health check
    if ($health_data['status'] === 'degraded') {
        $health_data['note'] = 'Service is running but database connection failed';
    }
    
    http_response_code(200);
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
