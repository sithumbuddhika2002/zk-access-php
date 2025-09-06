<?php
// simple session guard (expand later if you add auth)
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>ZKTeco Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <style>
    *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial}
    .layout{display:flex;min-height:100vh}
    .sidebar{width:240px;background:#0f172a;color:#e2e8f0;padding:16px;position:sticky;top:0;height:100vh}
    .brand{font-weight:700;font-size:18px;margin-bottom:12px}
    .nav a{display:block;padding:10px 12px;color:#e2e8f0;text-decoration:none;border-radius:10px;margin-bottom:6px}
    .nav a.active,.nav a:hover{background:#1f2937}
    .content{flex:1;background:#f8fafc;padding:20px}
    .card{background:#fff;border-radius:14px;box-shadow:0 6px 18px rgba(15,23,42,.06);padding:18px}
    iframe{width:100%;height:calc(100vh - 60px);border:0;border-radius:12px;background:#fff}
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">ZKTeco Admin</div>
    <div class="nav">
      <a href="students.php" target="main" class="active">➕ Add / Manage Students</a>
      <a href="live_logs.php" target="main">🪪 Recent Card Events</a>
      <!-- add more links later -->
    </div>
  </aside>
  <main class="content">
    <div class="card">
      <iframe name="main" src="students.php"></iframe>
    </div>
  </main>
</div>
<script>
  const links = document.querySelectorAll('.nav a');
  links.forEach(a => a.addEventListener('click', e => {
    links.forEach(x=>x.classList.remove('active'));
    e.currentTarget.classList.add('active');
  }));
</script>
</body>
</html>
