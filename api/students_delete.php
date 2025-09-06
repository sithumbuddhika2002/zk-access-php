<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

$pin = trim($_GET['pin'] ?? '');
if ($pin === '') {
    http_response_code(422);
    echo json_encode(['error' => 'PIN is required']);
    exit;
}

require_once __DIR__ . '/../db.php';

// --- Delete from DB ---
$stmt = $conn->prepare("DELETE FROM students WHERE pin = ?");
$stmt->bind_param("s", $pin);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'DB delete failed: ' . $stmt->error]);
    exit;
}

// --- Delete from device ---
$deviceMsg = 'device delete skipped';
try {
    $config = require __DIR__ . '/../config.php';
    require_once __DIR__ . '/../zk_pull.php';

    if (file_exists($config['dll'])) {
        $zk = new ZKPull($config['dll']);
        $zk->connect($config['ip'], $config['port'], $config['password']);

        // Safe public method
        $zk->deleteUserByPin($pin);

        $zk->disconnect();
        $deviceMsg = 'deleted from device';
    } else {
        error_log("DLL not found at " . $config['dll']);
    }
} catch (Throwable $e) {
    error_log("Device delete failed: " . $e->getMessage());
    $deviceMsg = 'device delete failed';
}

echo json_encode(['message' => "Deleted from DB and {$deviceMsg}."]);
