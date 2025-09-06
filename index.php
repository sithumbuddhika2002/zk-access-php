<?php
// Set timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$rows = [];

// Fetch last 20 events with student names
$sql = "
    SELECT ce.id, ce.event_time, ce.card_number, ce.door, ce.event_code, ce.inout_mode,
           s.full_name
    FROM card_events ce
    LEFT JOIN students s ON ce.card_number = s.card_number
    ORDER BY ce.id DESC
    LIMIT 20
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// AJAX request
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'last_refresh' => date('Y-m-d H:i:s')
    ]);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZKAccess Dashboard</title>
    <style>
        :root {
            --primary: #1e3a8a;
            --secondary: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 2rem;
            color: var(--text);
        }
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        .last-refresh {
            font-size: 0.9rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .last-refresh::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--secondary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        .table-wrapper { overflow-x: auto; border-radius: 12px; }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }
        thead th {
            background: var(--secondary);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        tbody tr {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        tbody td {
            padding: 1.2rem 1.5rem;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
        }
        .status { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; }
        .status-in { background: var(--success); color: white; }
        .status-out { background: var(--danger); color: white; }
        .new-row { animation: slideIn 0.8s ease-out; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); background: #e0f2fe; }
            to { opacity: 1; transform: translateY(0); background: var(--card-bg); }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="header">
        <h2>Live Card Swipe Dashboard</h2>
        <span class="last-refresh">Last Refresh: <span id="lastRefreshTime"><?= date('Y-m-d H:i:s') ?></span></span>
    </div>
    <div class="table-wrapper">
        <table id="eventsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Card Number</th>
                    <th>Name</th>
                    <th>Door</th>
                    <th>Event</th>
                    <th>In/Out</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['event_time']) ?></td>
                    <td><?= htmlspecialchars($row['card_number']) ?></td>
                    <td><?= htmlspecialchars($row['full_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($row['door']) ?></td>
                    <td><?= htmlspecialchars($row['event_code']) ?></td>
                    <td>
                        <span class="status status-<?= $row['inout_mode'] == '1' ? 'in' : 'out' ?>">
                            <?= $row['inout_mode'] == '1' ? 'In' : 'Out' ?>
                        </span>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<script>
let lastIds = new Set(<?= json_encode(array_column($rows, 'id')) ?>);

function updateTable() {
    fetch('?ajax=1')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#eventsTable tbody');
            const lastRefreshSpan = document.querySelector('#lastRefreshTime');
            const newLastIds = new Set();
            const fragment = document.createDocumentFragment();

            lastRefreshSpan.textContent = data.last_refresh;

            data.rows.forEach(row => {
                newLastIds.add(row.id);
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${htmlspecialchars(row.id)}</td>
                    <td>${htmlspecialchars(row.event_time)}</td>
                    <td>${htmlspecialchars(row.card_number)}</td>
                    <td>${htmlspecialchars(row.full_name || 'Unknown')}</td>
                    <td>${htmlspecialchars(row.door)}</td>
                    <td>${htmlspecialchars(row.event_code)}</td>
                    <td><span class="status status-${row.inout_mode == '1' ? 'in' : 'out'}">
                        ${htmlspecialchars(row.inout_mode == '1' ? 'In' : 'Out')}
                    </span></td>
                `;
                if (!lastIds.has(row.id)) tr.classList.add('new-row');
                fragment.appendChild(tr);
            });

            tbody.innerHTML = '';
            tbody.appendChild(fragment);
            lastIds = newLastIds;
        })
        .catch(err => console.error('Error:', err));
}

function htmlspecialchars(str) {
    return str ? str.toString()
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;') : '';
}

setInterval(updateTable, 3000);
</script>
</body>
</html>
