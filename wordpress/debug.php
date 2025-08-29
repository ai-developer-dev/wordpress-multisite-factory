<?php
// Comprehensive debug page for Railway deployment
header('Content-Type: text/html');
echo "<h1>🔧 WordPress Railway Debug</h1>";

// 1. Basic PHP Info
echo "<h2>📋 Environment Info</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";

// 2. Environment Variables
echo "<h2>🌍 Railway Environment Variables</h2>";
echo "<strong>PORT:</strong> " . (getenv('PORT') ?: 'Not set') . "<br>";
echo "<strong>RAILWAY_ENVIRONMENT:</strong> " . (getenv('RAILWAY_ENVIRONMENT') ?: 'Not set') . "<br>";
echo "<strong>RAILWAY_SERVICE_NAME:</strong> " . (getenv('RAILWAY_SERVICE_NAME') ?: 'Not set') . "<br>";
echo "<strong>DATABASE_URL present:</strong> " . (getenv('DATABASE_URL') ? 'Yes' : 'No') . "<br>";

if (getenv('DATABASE_URL')) {
    $db_url = getenv('DATABASE_URL');
    $db_parts = parse_url($db_url);
    echo "<strong>DB Host:</strong> " . ($db_parts['host'] ?? 'Unknown') . "<br>";
    echo "<strong>DB Port:</strong> " . ($db_parts['port'] ?? 'Unknown') . "<br>";
    echo "<strong>DB Name:</strong> " . (ltrim($db_parts['path'] ?? '', '/') ?: 'Unknown') . "<br>";
    echo "<strong>DB User:</strong> " . ($db_parts['user'] ?? 'Unknown') . "<br>";
    echo "<strong>DB Password:</strong> " . (isset($db_parts['pass']) ? '[SET]' : '[NOT SET]') . "<br>";
}

// 3. PHP Extensions
echo "<h2>🔧 PHP Extensions</h2>";
$extensions = ['mysqli', 'pdo', 'pdo_mysql', 'gd', 'curl', 'zip', 'mbstring'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<strong>$ext:</strong> $status<br>";
}

// 4. Database Connection Test
echo "<h2>🗄️ Database Connection Test</h2>";
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    try {
        $db_parts = parse_url($database_url);
        $scheme = $db_parts['scheme'] ?? 'unknown';
        $host = $db_parts['host'] . ':' . ($db_parts['port'] ?: ($scheme === 'postgres' ? 5432 : 3306));
        $dbname = ltrim($db_parts['path'], '/');
        $user = $db_parts['user'];
        $pass = $db_parts['pass'];
        
        echo "<strong>Database Type:</strong> $scheme<br>";
        echo "<strong>Attempting connection to:</strong> $host<br>";
        echo "<strong>Database:</strong> $dbname<br>";
        
        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            // PostgreSQL connection
            $dsn = "pgsql:host={$db_parts['host']};port=" . ($db_parts['port'] ?: 5432) . ";dbname=$dbname";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $stmt = $pdo->query('SELECT version() as version, NOW() as time');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "✅ <strong>PostgreSQL Connection: SUCCESS</strong><br>";
            echo "<strong>PostgreSQL Version:</strong> " . $result['version'] . "<br>";
            echo "<strong>Database Time:</strong> " . $result['time'] . "<br>";
            
            // Test table creation
            $pdo->exec("CREATE TABLE IF NOT EXISTS debug_test (id SERIAL PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("INSERT INTO debug_test (id) VALUES (1) ON CONFLICT (id) DO UPDATE SET created_at = CURRENT_TIMESTAMP");
            echo "✅ <strong>Table operations: SUCCESS</strong><br>";
            
        } else {
            // MySQL connection
            $dsn = "mysql:host={$db_parts['host']};port=" . ($db_parts['port'] ?: 3306) . ";dbname=$dbname";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $stmt = $pdo->query('SELECT VERSION() as version, NOW() as time');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "✅ <strong>MySQL Connection: SUCCESS</strong><br>";
            echo "<strong>MySQL Version:</strong> " . $result['version'] . "<br>";
            echo "<strong>Database Time:</strong> " . $result['time'] . "<br>";
            
            // Test table creation
            $pdo->exec("CREATE TABLE IF NOT EXISTS debug_test (id INT PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("INSERT INTO debug_test (id) VALUES (1) ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP");
            echo "✅ <strong>Table operations: SUCCESS</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Database Connection: FAILED</strong><br>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    }
} else {
    echo "❌ <strong>DATABASE_URL not found</strong><br>";
}

// 5. File System Test
echo "<h2>📁 File System Test</h2>";
$test_dirs = ['/var/www/html', '/var/www/html/wp-content', '/var/www/html/wp-content/uploads'];
foreach ($test_dirs as $dir) {
    if (file_exists($dir)) {
        $writable = is_writable($dir) ? '✅ Writable' : '❌ Not writable';
        echo "<strong>$dir:</strong> ✅ Exists, $writable<br>";
    } else {
        echo "<strong>$dir:</strong> ❌ Does not exist<br>";
    }
}

// 6. WordPress Files Check
echo "<h2>📄 WordPress Files Check</h2>";
$wp_files = ['wp-config.php', 'wp-load.php', 'wp-blog-header.php', 'index.php'];
foreach ($wp_files as $file) {
    $path = "/var/www/html/$file";
    $exists = file_exists($path) ? '✅' : '❌';
    echo "<strong>$file:</strong> $exists<br>";
}

// 7. Apache Configuration
echo "<h2>🌐 Server Configuration</h2>";
echo "<strong>Server Port (from ENV):</strong> " . (getenv('PORT') ?: '80') . "<br>";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "<strong>SERVER_PORT:</strong> " . ($_SERVER['SERVER_PORT'] ?? 'Not set') . "<br>";
echo "<strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "<br>";

echo "<h2>✅ Debug Complete</h2>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s T') . "</p>";
?>