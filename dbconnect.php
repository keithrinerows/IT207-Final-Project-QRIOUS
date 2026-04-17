<?php
$host = "sql207.byethost8.com";
$user = "b8_41189529";
$pass = "keithrine01"; 
$dbname = "b8_41189529_Online_Quiz";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: Set charset to utf8 for better character support
mysqli_set_charset($conn, "utf8");

// echo "Connected successfully";
// IMPORTANT: Use environment variables for credentials in production.
?>
