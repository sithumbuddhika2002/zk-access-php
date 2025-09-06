<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$res = $conn->query("SELECT id, student_id, full_name, pin, card_number, status, updated_at FROM students ORDER BY id DESC");
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }

echo json_encode(['rows' => $rows]);
