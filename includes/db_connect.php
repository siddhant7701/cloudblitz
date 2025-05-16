<?php
// Database configuration
$host = 'localhost';
$dbname = 'cloudblitz_edu';
$username = 'root';
$password = '';

// Connect to database using PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, display error message
    die("Connection failed: " . $e->getMessage());
}
?>
