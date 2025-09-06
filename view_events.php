<?php require_once __DIR__ . '/db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Recent Card Events</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;margin:0;padding:16px;background:#fff}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px solid #e5e7eb;padding:8px 10px;text-align:left}
    th{font-size:12px;text-transform:uppercase;color:#475569}
  </style>
</head>
<body>
<h2>Recent Card Events</h2>
<table>
  <thead><tr>
    <th>ID</th><th>Event Time</th><th>PIN</th><th>Door</th><th>Card</th><th>Event</th><th>In/Out</th>
  </tr></thead>
  <tbody>
    <?php
      $res = $conn->query("SELECT id,event_time,pin,door,card_number,event_code,inout_mode FROM card_events ORDER BY id DESC LIMIT 100");
      while ($r = $res->fetch_assoc()):
    ?>
      <tr>
        <td><?=htmlspecialchars($r['id'])?></td>
        <td><?=htmlspecialchars($r['event_time'])?></td>
        <td><?=htmlspecialchars($r['pin'])?></td>
        <td><?=htmlspecialchars($r['door'])?></td>
        <td><?=htmlspecialchars($r['card_number'])?></td>
        <td><?=htmlspecialchars($r['event_code'])?></td>
        <td><?=htmlspecialchars($r['inout_mode'])?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</body>
</html>
