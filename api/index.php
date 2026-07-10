<?php
/**
 * Vercel Front Controller / Entry Point
 * Transport Management System (TMS)
 */

// 1. Set the working directory to the project root
// This is critical so that relative require/include paths inside project files function correctly
chdir(__DIR__ . '/..');

// 2. Parse request URI path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// 3. Default route maps to index.php
if (empty($uri) || $uri === 'index.php') {
    $file = 'index.php';
} else {
    $file = $uri;
}

// 4. Validate file exists and is a PHP file
if (file_exists($file) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    // Override PHP environment variables to preserve compatibility with basename($_SERVER['PHP_SELF'])
    $_SERVER['SCRIPT_NAME'] = '/' . $file;
    $_SERVER['PHP_SELF'] = '/' . $file;
    
    // Include the requested script
    require $file;
    exit();
} else {
    // Return standard 404 response
    http_response_code(404);
    echo "404 - Page Not Found";
    exit();
}
?>
