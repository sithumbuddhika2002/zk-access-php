<?php
// Set timezone
date_default_timezone_set('Asia/Colombo');

$servername = "localhost";
$username   = "root";
$password   = "Sithum@0213";
$dbname     = "zk_access";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where  = "";
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where = "WHERE ce.card_number LIKE '%$safe%' 
              OR s.full_name LIKE '%$safe%'
              OR ce.door LIKE '%$safe%'";
}

// Handle pagination
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Count total rows
$countSql = "SELECT COUNT(*) as total FROM card_events ce 
             LEFT JOIN students s ON ce.card_number = s.card_number 
             $where";
$totalResult = $conn->query($countSql);
$totalRows = $totalResult ? (int)$totalResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $perPage);

// Fetch data
$sql = "
    SELECT ce.id, ce.event_time, ce.card_number, ce.door, ce.event_code, ce.inout_mode,
           s.full_name
    FROM card_events ce
    LEFT JOIN students s ON ce.card_number = s.card_number
    $where
    ORDER BY ce.id DESC
    LIMIT $perPage OFFSET $offset
";
$result = $conn->query($sql);

$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
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
    body { font-family: 'Inter', sans-serif; background: var(--background); margin: 0; padding: 2rem; color: var(--text); }
    .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 2rem; background: var(--card-bg); border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    h2 { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
    .search-box input { padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 8px; width: 280px; }
    .last-refresh { font-size: 0.9rem; color: #64748b; margin-left: 1rem; }
    .table-wrapper { overflow-x: auto; border-radius: 12px; }
    table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
    thead th { background: var(--secondary); color: white; padding: 1rem 1.5rem; text-align: left; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
    tbody tr { background: var(--card-bg); border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    tbody td { padding: 1rem 1.5rem; font-size: 0.95rem; border-bottom: 1px solid var(--border); }
    .status { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.8rem; }
    .status-in { background: var(--success); color: white; }
    .status-out { background: var(--danger); color: white; }
    .pagination { margin-top: 1.5rem; text-align: center; }
    .pagination a { padding: 0.5rem 0.9rem; margin: 0 0.2rem; border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--primary); }
    .pagination a.active { background: var(--secondary); color: white; font-weight: bold; }
</style>
</head>
<body>
<div class="dashboard-container">
    <div class="header">
        <h2>Live Card Swipe Dashboard</h2>
        <div class="last-refresh" id="lastRefresh">
            Last updated: <?= date("Y-m-d H:i:s") ?> (Sri Lanka)
        </div>
        <form method="get" class="search-box">
            <input type="text" name="search" placeholder="Search name, card, door..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
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
            <?php if ($rows) { foreach ($rows as $row) { ?>
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
            <?php }} else { ?>
                <tr><td colspan="7">No records found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>">« Prev</a>
        <?php endif; ?>

        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="<?= $i==$page ? 'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next »</a>
        <?php endif; ?>
    </div>
</div>

<script>
function refreshDashboard() {
    fetch(window.location.href, { cache: "no-store" })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");

            // Replace table body
            document.querySelector("tbody").innerHTML =
                doc.querySelector("tbody").innerHTML;

            // Update last refresh time
            document.getElementById("lastRefresh").innerHTML =
                doc.querySelector("#lastRefresh").innerHTML;
        })
        .catch(err => console.error("Refresh error:", err));
}

// Refresh every 1 second
setInterval(refreshDashboard, 1000);
</script>
</body>
</html>
