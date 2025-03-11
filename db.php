<?php
// Database configuration
define('DB_SERVER', '.\pcamerica');      // Server name or IP
define('DB_USERNAME', 'sa'); // Database username
define('DB_PASSWORD', 'pcAmer1ca'); // Database password
define('DB_NAME', 'Test');     // Database name

// Connect to MSSQL database using PDO
try {
    $conn = new PDO("sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

