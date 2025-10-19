<?php
// Database configuration
$hostName   = "localhost";
$dbUser     = "root";
$dbPassword = "";
$dbName     = "login_register";

// Create connection
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Optional: set charset to avoid encoding issues
mysqli_set_charset($conn, "utf8");
?>
