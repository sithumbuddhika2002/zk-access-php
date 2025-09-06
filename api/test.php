<?php
require_once __DIR__ . '/../zk_pull.php';

$dll = __DIR__ . "\\..\\sdk\\plcommpro.dll";
$zk  = new ZKPull($dll);

// Try without password param first
if ($zk->connect("192.168.1.201", 4370)) {
    echo "Connected!\n";
    $zk->upsertUser(2, '7542020', 'BUDDHIKA CAS');
    echo "User pushed!\n";
    $zk->disconnect();
} else {
    echo "Failed to connect!\n";
}
