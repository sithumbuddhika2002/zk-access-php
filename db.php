<?php
$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
