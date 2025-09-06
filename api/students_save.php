<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error'=>'Invalid JSON']); exit;
}

$student_id = trim($input['student_id'] ?? '');
$full_name  = trim($input['full_name'] ?? '');
$pin        = trim($input['pin'] ?? '');
$card       = trim($input['card_number'] ?? '');
$status     = in_array(($input['status'] ?? 'active'), ['active','inactive']) ? $input['status'] : 'active';

if (!$student_id || !$full_name || !$pin || !$card) {
    echo json_encode(['error'=>'All fields are required']); exit;
}

require_once __DIR__.'/../db.php';

// Insert or update
$stmt = $conn->prepare("
    INSERT INTO students (student_id, full_name, pin, card_number, status, device_status)
    VALUES (?, ?, ?, ?, ?, 'PENDING')
    ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), card_number=VALUES(card_number), status=VALUES(status), device_status='PENDING'
");
$stmt->bind_param("sssss", $student_id, $full_name, $pin, $card, $status);
$stmt->execute();
$stmt->close();

echo json_encode(['message'=>"Saved to DB. Device push will be processed separately."]);
