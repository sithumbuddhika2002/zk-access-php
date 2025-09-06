<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../db.php';
require_once __DIR__.'/../zk_pull.php';
$config = require __DIR__.'/../config.php';

// Fetch pending students
$res = $conn->query("SELECT pin, card_number, full_name FROM students WHERE device_status='PENDING' AND status='active'");
if (!$res) {
    die("Query failed: " . $conn->error);
}

echo "Found ".$res->num_rows." pending students\n";

while($row = $res->fetch_assoc()){
    echo "Processing ".$row['full_name']." (PIN: ".$row['pin'].")\n";
    try {
        $zk = new ZKPull($config['dll']);
        
        // Attempt to connect
        if (!$zk->connect($config['ip'], $config['port'], $config['password'])) {
            echo "Failed to connect to device for ".$row['full_name']."\n";
            // Update status as ERROR
            $conn->query("UPDATE students SET device_status='ERROR', last_error='Connection failed' WHERE pin='".$conn->real_escape_string($row['pin'])."'");
            continue;
        }
        echo "Connected to device\n";

        // Push user to device
        $zk->upsertUser($row['pin'], $row['card_number'], $row['full_name']);
        echo "User pushed: ".$row['full_name']."\n";

        // Disconnect
        $zk->disconnect();

        // Update DB
        $conn->query("UPDATE students SET device_status='SYNCED', last_error=NULL WHERE pin='".$conn->real_escape_string($row['pin'])."'");

    } catch(Throwable $e){
        $err = $conn->real_escape_string($e->getMessage());
        $conn->query("UPDATE students SET device_status='ERROR', last_error='{$err}' WHERE pin='".$conn->real_escape_string($row['pin'])."'");
        echo "Failed ".$row['full_name']." : ".$e->getMessage()."\n";
    }
}

echo "Push process completed.\n";
