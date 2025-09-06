<?php
require_once __DIR__ . '/zk_pull.php';
$config = require __DIR__ . '/config.php';

$zk = new ZKPull($config['dll']);
if ($zk->connect($config['ip'], $config['port'], $config['password'])) {
    echo "[OK] Connected to {$config['ip']}:{$config['port']}\n";
    $zk->disconnect();
} else {
    echo "[ERROR] Failed to connect! Check DLL, IP, port, firewall, or VC++ Redistributables.\n";
}
