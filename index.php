<?php
require __DIR__ . '/config.php';
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$rules = $pdo->query("SELECT * FROM firewall_rules ORDER BY id DESC")->fetchAll();
$logs = $pdo->query("SELECT * FROM security_logs ORDER BY id DESC LIMIT 12")->fetchAll();
$blacklist = $pdo->query("SELECT * FROM blacklist_ips ORDER BY id DESC")->fetchAll();
$stats = $pdo->query("
 SELECT
 (SELECT COUNT(*) FROM security_logs) AS packets,
 (SELECT COUNT(*) FROM security_logs WHERE action_taken='ALLOWED') AS allowed_count,
 (SELECT COUNT(*) FROM security_logs WHERE action_taken='BLOCKED') AS blocked_count,
 (SELECT COUNT(*) FROM firewall_rules WHERE status=1) AS active_rules
")->fetch();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CyberShield | Network Security</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<aside class="sidebar">
  <div class="brand"><span class="brand-icon">⬡</span><div><b>CyberShield</b><small>NETWORK DEFENSE</small></div></div>
  <div class="nav-label">MENU UTAMA</div>
  <a class="nav active" href="#dashboard">▦ <span>Dashboard</span></a>
  <a class="nav" href="#firewall">◈ <span>Aturan Firewall</span></a>
  <a class="nav" href="#simulation">⌁ <span>Simulasi Traffic</span></a>
  <a class="nav" href="#logs">▤ <span>Log Keamanan</span></a>
  <a class="nav" href="#blacklist">⊘ <span>Blacklist IP</span></a>
  <div class="side-status"><i></i><span>Sistem simulasi aktif</span></div>
  <small class="side-foot">CYBERSHIELD v1.0<br>LOCAL DEMO MODE</small>
</aside>
<main class="main">
<header class="topbar"><div><span class="crumb">NETWORK /</span> <b>OVERVIEW</b></div><div class="top-right"><span class="live"><i></i> LIVE SIMULATION</span><span id="clock">--:--:--</span></div></header>
<section id="dashboard" class="section">
  <div class="welcome"><div><p class="eyebrow">SECURITY OPERATIONS CENTER</p><h1>Dashboard Monitoring</h1><p class="muted">Pantau aktivitas jaringan dan status perlindungan secara langsung.</p></div><span class="date-chip">● PROTECTED ENVIRONMENT</span></div>
  <div class="stats">
    <div class="stat-card"><div class="stat-top"><span>Total paket diperiksa</span><span class="stat-icon blue">⇅</span></div><strong id="packets"><?= number_format($stats['packets']) ?></strong><small>Seluruh event simulasi</small></div>
    <div class="stat-card"><div class="stat-top"><span>Koneksi diizinkan</span><span class="stat-icon green">✓</span></div><strong id="allowed"><?= number_format($stats['allowed_count']) ?></strong><small class="green-text">Lalu lintas aman</small></div>
    <div class="stat-card"><div class="stat-top"><span>Ancaman diblokir</span><span class="stat-icon red">⛨</span></div><strong id="blocked"><?= number_format($stats['blocked_count']) ?></strong><small class="red-text">Event ditolak sistem</small></div>
    <div class="stat-card"><div class="stat-top"><span>Aturan firewall aktif</span><span class="stat-icon purple">⌘</span></div><strong id="activeRules"><?= number_format($stats['active_rules']) ?></strong><small>Rule yang sedang berjalan</small></div>
  </div>
  <div class="grid-two">
    <div class="panel"><div class="panel-head"><div><h2>Volume Traffic Waktu Nyata</h2><p class="muted">Jumlah event jaringan per interval</p></div><span class="tag green-tag">● AUTO UPDATE</span></div><div class="chart-wrap"><canvas id="trafficChart" height="155"></canvas></div><div class="legend"><span><i class="dot blue-dot"></i> Diizinkan</span><span><i class="dot red-dot"></i> Diblokir</span></div></div>
    <div class="panel"><div class="panel-head"><div><h2>Port Paling Sering Diblokir</h2><p class="muted">Berdasarkan log simulasi</p></div></div><div id="portStats" class="port-list"><div class="empty">Belum ada data blokir.</div></div></div>
  </div>
  <div class="panel resource-panel"><div class="panel-head"><div><h2>Status Sistem</h2><p class="muted">Status layanan aplikasi lokal</p></div><span class="tag green-tag">NORMAL</span></div><div class="resource"><span>Database MySQL</span><div class="meter"><i style="width:100%"></i></div><b>ONLINE</b></div><div class="resource"><span>Firewall simulator</span><div class="meter"><i style="width:92%"></i></div><b>ACTIVE</b></div><div class="resource"><span>Event monitor</span><div class="meter"><i style="width:100%"></i></div><b>RUNNING</b></div></div>
</section>

<section id="firewall" class="section">
 <div class="section-title"><div><p class="eyebrow">ACCESS CONTROL</p><h1>Manajemen Aturan Firewall</h1><p class="muted">Atur keputusan ALLOW atau BLOCK pada simulasi jaringan.</p></div></div>
 <div class="panel">
  <form class="form-grid" id="ruleForm">
   <label>Nama aturan<input name="rule_name" required maxlength="100" placeholder="Contoh: Blokir akses SSH"></label>
   <label>Alamat IP<input name="ip_address" required placeholder="Contoh: 203.0.113.10"></label>
   <label>Port tujuan<input name="port" type="number" min="1" max="65535" required placeholder="22"></label>
   <label>Tindakan<select name="action"><option value="BLOCK">BLOCK</option><option value="ALLOW">ALLOW</option></select></label>
   <button class="btn primary" type="submit">＋ Tambah aturan</button>
  </form>
  <div class="table-wrap"><table><thead><tr><th>Nama aturan</th><th>Alamat IP</th><th>Port</th><th>Aksi</th><th>Status</th><th>Kelola</th></tr></thead><tbody id="rulesBody">
  <?php foreach($rules as $r): ?><tr><td><?=h($r['rule_name'])?></td><td class="mono"><?=h($r['ip_address'])?></td><td><?=h($r['port'])?></td><td><span class="pill <?=strtolower($r['action'])?>"><?=h($r['action'])?></span></td><td><?= $r['status'] ? '<span class="status-on">Aktif</span>' : '<span class="status-off">Nonaktif</span>' ?></td><td><button class="btn small" onclick="toggleRule(<?=$r['id']?>)">Aktif/Nonaktif</button> <button class="btn small danger" onclick="deleteRule(<?=$r['id']?>)">Hapus</button></td></tr><?php endforeach; ?>
  </tbody></table></div>
 </div>
</section>

<section id="simulation" class="section">
 <div class="section-title"><div><p class="eyebrow">SAFE TEST ENVIRONMENT</p><h1>Simulasi Traffic & Serangan</h1><p class="muted">Event buatan untuk menguji aturan firewall. Tidak mengirim paket ke jaringan sungguhan.</p></div></div>
 <div class="panel sim-panel"><div><div class="sim-symbol">⌁</div><h2>Network Traffic Simulator</h2><p class="muted">Buat event normal atau event uji mencurigakan, lalu lihat keputusan firewall dan catatan log.</p></div><div class="sim-actions"><button class="btn primary" onclick="simulate('normal')">▶ Simulasikan traffic normal</button><button class="btn danger" onclick="simulate('suspicious')">⚑ Simulasikan event mencurigakan</button><button class="btn" onclick="simulate('batch')">＋ Jalankan 5 event otomatis</button></div><div id="simMessage" class="sim-message">Menunggu simulasi...</div></div>
</section>

<section id="logs" class="section">
 <div class="section-title"><div><p class="eyebrow">AUDIT TRAIL</p><h1>Log Keamanan</h1><p class="muted">Riwayat event simulasi dan tindakan sistem.</p></div><button class="btn" onclick="loadData()">↻ Perbarui</button></div>
 <div class="panel table-wrap"><table><thead><tr><th>Waktu</th><th>IP sumber</th><th>Port tujuan</th><th>Aktivitas</th><th>Keputusan</th><th>Detail</th></tr></thead><tbody id="logsBody">
 <?php foreach($logs as $l): ?><tr><td class="mono"><?=h($l['event_time'])?></td><td class="mono"><?=h($l['source_ip'])?></td><td><?=h($l['destination_port'])?></td><td><?=h($l['activity'])?></td><td><span class="pill <?=strtolower($l['action_taken'])?>"><?=h($l['action_taken'])?></span></td><td><?=h($l['details'])?></td></tr><?php endforeach; ?>
 </tbody></table></div>
</section>

<section id="blacklist" class="section">
 <div class="section-title"><div><p class="eyebrow">IP REPUTATION</p><h1>Blacklist IP</h1><p class="muted">Daftar alamat IP yang otomatis ditolak oleh simulator.</p></div></div>
 <div class="panel">
  <form id="blacklistForm" class="form-grid blacklist-form"><label>Alamat IP<input name="ip_address" required placeholder="203.0.113.20"></label><label>Alasan<input name="reason" maxlength="180" placeholder="Aktivitas mencurigakan"></label><button class="btn primary" type="submit">＋ Tambah ke blacklist</button></form>
  <div class="table-wrap"><table><thead><tr><th>Alamat IP</th><th>Alasan</th><th>Ditambahkan</th><th>Aksi</th></tr></thead><tbody id="blacklistBody">
  <?php foreach($blacklist as $b): ?><tr><td class="mono"><?=h($b['ip_address'])?></td><td><?=h($b['reason'])?></td><td><?=h($b['created_at'])?></td><td><button class="btn small danger" onclick="removeBlacklist(<?=$b['id']?>)">Hapus</button></td></tr><?php endforeach; ?>
  </tbody></table></div>
 </div>
</section>
<footer>CyberShield • Project UTS Keamanan Jaringan • Mode simulasi lokal</footer>
</main>
<div id="toast" class="toast"></div>
<script src="app.js"></script>
</body></html>
