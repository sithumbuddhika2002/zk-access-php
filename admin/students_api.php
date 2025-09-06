<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');


// --- DB ---
$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['ok'=>false,'error'=>'DB connection failed']);
    exit;
}

// ZK device integration
require_once __DIR__ . '/../zk_pull.php';
$config = require __DIR__ . '/../config.php';

$action = $_POST['action'] ?? '';

function connectDevice($config) {
    $zk = new ZKPull($config['dll']);
    $zk->connect($config['ip'], (int)$config['port'], $config['password'] ?? '');
    return $zk;
}

try {
    if ($action === 'create') {
        $pin  = trim($_POST['pin'] ?? '');
        $card = trim($_POST['card_number'] ?? '');
        $name = trim($_POST['full_name'] ?? '');

        if ($pin === '' || $card === '' || $name === '') {
            throw new Exception('All fields are required.');
        }

        // Check unique PIN and Card Number
        $chk = $conn->prepare("SELECT id FROM students WHERE pin=? OR card_number=? LIMIT 1");
        $chk->bind_param("ss", $pin, $card);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) throw new Exception('PIN or Card Number already exists.');
        $chk->close();

        // DB insert
        $stmt = $conn->prepare("INSERT INTO students (pin, card_number, full_name, device_status) VALUES (?, ?, ?, 'PENDING')");
        $stmt->bind_param("sss", $pin, $card, $name);
        $stmt->execute();
        $id = $stmt->insert_id;

        // Push to device
        $zk = connectDevice($config);
        try {
            $zk->addUser($pin, $card, $name);
            $conn->query("UPDATE students SET device_status='SYNCED', last_error=NULL WHERE id={$id}");
        } catch (Throwable $e) {
            $msg = $conn->real_escape_string($e->getMessage());
            $conn->query("UPDATE students SET device_status='ERROR', last_error='{$msg}' WHERE id={$id}");
            throw $e;
        } finally {
            $zk->disconnect();
        }

        echo json_encode(['ok'=>true, 'id'=>$id]);
        exit;
    }

    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $pin  = trim($_POST['pin'] ?? '');
        $card = trim($_POST['card_number'] ?? '');
        $name = trim($_POST['full_name'] ?? '');

        if ($id <= 0 || $pin === '' || $card === '' || $name === '') {
            throw new Exception('Invalid payload.');
        }

        // Enforce unique pin/card_number
        $chk = $conn->prepare("SELECT id FROM students WHERE (pin=? OR card_number=?) AND id<>? LIMIT 1");
        $chk->bind_param("ssi", $pin, $card, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) throw new Exception('PIN or Card Number already in use.');
        $chk->close();

        // DB update first
        $stmt = $conn->prepare("UPDATE students SET pin=?, card_number=?, full_name=?, device_status='PENDING', last_error=NULL WHERE id=?");
        $stmt->bind_param("sssi", $pin, $card, $name, $id);
        $stmt->execute();

        // Sync to device
        $zk = connectDevice($config);
        try {
            $zk->updateUser($pin, $card, $name);
            $conn->query("UPDATE students SET device_status='SYNCED' WHERE id={$id}");
        } catch (Throwable $e) {
            $msg = $conn->real_escape_string($e->getMessage());
            $conn->query("UPDATE students SET device_status='ERROR', last_error='{$msg}' WHERE id={$id}");
            throw $e;
        } finally {
            $zk->disconnect();
        }

        echo json_encode(['ok'=>true]);
        exit;
    }

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('Invalid ID');

    // Get student PIN from DB
    $stmt = $conn->prepare("SELECT pin FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) throw new Exception('Student not found');
    $pin = $res->fetch_assoc()['pin'];
    $stmt->close();

    // Delete from device safely
    $deviceNote = null;
    try {
        $zk = connectDevice($config);
        $zk->deleteUser($pin); // may throw
    } catch (Throwable $e) {
        $deviceNote = $e->getMessage(); // keep error but continue
    } finally {
        if (isset($zk)) $zk->disconnect();
    }

    // Delete from DB
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("DB delete failed: ".$conn->error);
    }
    $stmt->close();

    echo json_encode(['ok'=>true, 'device_note'=>$deviceNote]);
    exit;
}



    echo json_encode(['ok'=>false, 'error'=>'Unknown action']);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
?>