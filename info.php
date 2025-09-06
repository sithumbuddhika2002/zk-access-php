<?php
// Run: C:\php32\php.exe -d ffi.enable=1 info.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/zk_pull.php';
$config = require __DIR__ . '/config.php';

echo "[INFO] Loading Pull SDK: {$config['dll']}\n";
$zk = new ZKPull($config['dll']);

echo "[INFO] Connecting to {$config['ip']}:{$config['port']} ...\n";
$zk->connect($config['ip'], $config['port'], $config['password']);
echo "[OK] Connected.\n";

// Read a few parameters
$params = $zk->getDeviceParam(['DeviceID','~SerialNumber','LockCount','FirmwareVersion'], $config['param_buffer']);
echo "[DEVICE] {$params}\n";

$zk->disconnect();
echo "[DONE]\n";
