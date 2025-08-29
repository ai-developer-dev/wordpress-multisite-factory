<?php
echo "Hello from WordPress container!";
echo "<br>PHP Version: " . phpversion();
echo "<br>Document Root: " . $_SERVER['DOCUMENT_ROOT'];
echo "<br>Current Directory: " . __DIR__;
echo "<br>Files in current directory:";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";
?>