<?php
// Simple test page
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Railway Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🚀 WordPress on Railway - Test Page</h1>
    
    <div class="success">
        ✅ <strong>Apache is working!</strong><br>
        ✅ <strong>PHP is working!</strong><br>
        ✅ <strong>File serving is working!</strong>
    </div>
    
    <h2>Quick Info:</h2>
    <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
    <p><strong>Port:</strong> <?php echo getenv('PORT') ?: '80'; ?></p>
    
    <h2>Links:</h2>
    <p><a href="/debug.php">🔧 Full Debug Page</a></p>
    <p><a href="/health.php">❤️ Health Check</a></p>
    <p><a href="/">🏠 WordPress Home (may not work yet)</a></p>
    
    <hr>
    <p><em>If you can see this page, your server is running correctly!</em></p>
</body>
</html>