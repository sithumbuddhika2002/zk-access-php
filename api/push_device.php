<?php
header('Content-Type: application/json');

$pin = trim($_GET['pin'] ?? '');
if ($pin === '') {
    http_response_code(400);
    echo json_encode(['error' => 'pin required']);
    exit;
}

require_once __DIR__ . '/../db.php';

// Fetch student info
$sel = $conn->prepare("SELECT full_name, card_number, status FROM students WHERE pin = ?");
$sel->bind_param("s", $pin);
$sel->execute();
$sel->bind_result($full_name, $card_number, $status);

if (!$sel->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Student not found']);
    $sel->close();
    exit;
}
$sel->close();

if ($status !== 'active') {
    echo json_encode(['message' => 'Skipped: student is inactive']);
    exit;
}

require_once __DIR__ . '/../zk_pull.php';
$config = require __DIR__ . '/../config.php';

try {
    $zk = new ZKPull($config['dll']);

    if (!$zk->connect($config['ip'], $config['port'], $config['password'])) {
        // Connection failed
        $conn->query("UPDATE students SET device_status='ERROR', last_error='Connection failed' WHERE pin='".$conn->real_escape_string($pin)."'");
        echo json_encode(['error' => 'Failed to connect to device']);
        exit;
    }

    // Push student to device
    $zk->upsertUser($pin, $card_number, $full_name);
    $zk->disconnect();

    // Update DB
    $conn->query("UPDATE students SET device_status='SYNCED', last_error=NULL WHERE pin='".$conn->real_escape_string($pin)."'");

    echo json_encode(['message' => 'Pushed to device']);

} catch (Throwable $e) {
    // Update DB with error
    $err = $conn->real_escape_string($e->getMessage());
    $conn->query("UPDATE students SET device_status='ERROR', last_error='{$err}' WHERE pin='".$conn->real_escape_string($pin)."'");
    
    http_response_code(500);
    echo json_encode(['error' => 'Device push failed: '.$e->getMessage()]);
}
