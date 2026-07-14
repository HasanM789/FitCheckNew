<?php
// Database server configuration credentials
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "fitcheck_db";

// Establish a connection to the MySQL database
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check if the connection has any structural errors
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Start a secure PHP Session globally for shopping carts and user login tracking
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>