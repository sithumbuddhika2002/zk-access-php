<?php
$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM card_events ORDER BY id DESC LIMIT 20");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['rows' => $rows, 'last_refresh' => date('Y-m-d H:i:s')]);
    exit;
}
?>
