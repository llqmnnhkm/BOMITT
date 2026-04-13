<?php
$servername = "localhost";
$username = "root"; // default XAMPP MySQL user
$password = "";     // default is empty
$database = "bomit4"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>