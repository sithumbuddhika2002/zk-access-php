<?php
require_once "zk_pull.php";

// DB connection
$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("[DB ERROR] Connection failed: " . $conn->connect_error);
}

$dll = __DIR__ . "\\sdk\\plcommpro.dll";

echo "[INFO] Starting C3-200 real-time listener...\n";

$zk = new ZKPull($dll);
$zk->connect("192.168.1.201", 4370);

// Force device to send real-time logs
$zk->setDeviceParam([
    "LogStamp"   => "1",
    "OpLogStamp" => "1",
    "PhotoStamp" => "1",
]);

echo "[OK] Connected to 192.168.1.201:4370\n";
echo "[RUNNING] Waiting for card swipes...\n";

while (true) {
    $log = $zk->getRTLog();
    if (!empty($log)) {
        $lines = explode("\n", trim($log));
        foreach ($lines as $line) {
            $parts = explode(",", $line);

            // C3-200 RTLog format: Time, PIN, Verified, DoorID, EventType, InOut, Reserved
            $time     = $parts[0] ?? '';
            $pin      = $parts[1] ?? '';
            $verified = $parts[2] ?? '';
            $door     = $parts[3] ?? '';
            $event    = $parts[4] ?? '';
            $inout    = $parts[5] ?? '';

            // treat "Verified" as card number
            $card_number = $verified;

            if ($card_number != '0' && $card_number != '') {
                echo "[CARD EVENT] Time=$time | Card=$card_number | Door=$door | Event=$event | InOut=$inout\n";

                // 🔹 Check last punch for this card
                $last = $conn->query("SELECT inout_mode FROM card_events WHERE card_number='$card_number' ORDER BY id DESC LIMIT 1");
                $last_inout = null;
                if ($last && $last->num_rows > 0) {
                    $last_inout = $last->fetch_assoc()['inout_mode'];
                }

                // 🔹 Prevent duplicate IN or duplicate OUT
                if ($last_inout === $inout) {
                    echo "[SKIP] Card $card_number tried to punch $inout again (last=$last_inout). Ignored.\n";
                    continue;
                }

                // Insert into MySQL
                $stmt = $conn->prepare("INSERT INTO card_events (event_time, pin, door, card_number, event_code, inout_mode) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissi", $time, $pin, $door, $card_number, $event, $inout);
                $stmt->execute();
            }
        }
    }
    usleep(200000); // 200ms delay
}
