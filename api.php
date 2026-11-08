<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
function out($data, $code=200) { http_response_code($code); echo json_encode($data); exit; }
function valid_ip($ip) { return filter_var($ip, FILTER_VALIDATE_IP) !== false; }
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
 if ($action === 'stats') {
   $stats = $pdo->query("SELECT (SELECT COUNT(*) FROM security_logs) packets,
     (SELECT COUNT(*) FROM security_logs WHERE action_taken='ALLOWED') allowed_count,
     (SELECT COUNT(*) FROM security_logs WHERE action_taken='BLOCKED') blocked_count,
     (SELECT COUNT(*) FROM firewall_rules WHERE status=1) active_rules")->fetch();
   $logs = $pdo->query("SELECT * FROM security_logs ORDER BY id DESC LIMIT 12")->fetchAll();
   $rules = $pdo->query("SELECT * FROM firewall_rules ORDER BY id DESC")->fetchAll();
   $blacklist = $pdo->query("SELECT * FROM blacklist_ips ORDER BY id DESC")->fetchAll();
   $ports = $pdo->query("SELECT destination_port, COUNT(*) total FROM security_logs WHERE action_taken='BLOCKED' GROUP BY destination_port ORDER BY total DESC LIMIT 5")->fetchAll();
   $history = $pdo->query("SELECT DATE_FORMAT(event_time,'%H:%i:%s') label,
     SUM(action_taken='ALLOWED') allowed_count, SUM(action_taken='BLOCKED') blocked_count
     FROM security_logs WHERE event_time >= NOW() - INTERVAL 20 MINUTE
     GROUP BY FLOOR(UNIX_TIMESTAMP(event_time)/30) ORDER BY MIN(event_time)")->fetchAll();
   out(compact('stats','logs','rules','blacklist','ports','history'));
 }
 if ($action === 'rule_add') {
   $name = trim($input['rule_name'] ?? ''); $ip = trim($input['ip_address'] ?? '');
   $port = (int)($input['port'] ?? 0); $decision = $input['action'] ?? 'BLOCK';
   if (!$name || !valid_ip($ip) || $port<1 || $port>65535 || !in_array($decision,['ALLOW','BLOCK'],true)) out(['error'=>'Periksa nama, IP, port, dan tindakan.'],422);
   $s=$pdo->prepare("INSERT INTO firewall_rules(rule_name,ip_address,port,action) VALUES(?,?,?,?)"); $s->execute([$name,$ip,$port,$decision]);
   out(['ok'=>true,'message'=>'Aturan firewall berhasil ditambahkan.']);
 }
 if ($action === 'rule_toggle') {
   $id=(int)($input['id']??0); $s=$pdo->prepare("UPDATE firewall_rules SET status=1-status WHERE id=?"); $s->execute([$id]);
   out(['ok'=>true,'message'=>'Status aturan diperbarui.']);
 }
 if ($action === 'rule_delete') {
   $s=$pdo->prepare("DELETE FROM firewall_rules WHERE id=?"); $s->execute([(int)($input['id']??0)]);
   out(['ok'=>true,'message'=>'Aturan dihapus.']);
 }
 if ($action === 'blacklist_add') {
   $ip=trim($input['ip_address']??''); $reason=trim($input['reason']??'Aktivitas mencurigakan');
   if (!valid_ip($ip)) out(['error'=>'Format alamat IP tidak valid.'],422);
   $s=$pdo->prepare("INSERT INTO blacklist_ips(ip_address,reason) VALUES(?,?)"); $s->execute([$ip,$reason]);
   out(['ok'=>true,'message'=>'IP ditambahkan ke blacklist.']);
 }
 if ($action === 'blacklist_delete') {
   $s=$pdo->prepare("DELETE FROM blacklist_ips WHERE id=?"); $s->execute([(int)($input['id']??0)]);
   out(['ok'=>true,'message'=>'IP dihapus dari blacklist.']);
 }
 if ($action === 'simulate') {
   $mode=$input['mode']??'normal'; $count=$mode==='batch'?5:1;
   $normalIps=['192.168.1.20','192.168.1.35','10.0.0.8','192.168.1.50'];
   $testIps=['203.0.113.25','198.51.100.77','203.0.113.88','198.51.100.19'];
   $ports=[80,443,53,22,3306,8080];
   $created=[];
   for($i=0;$i<$count;$i++){
     $suspicious=($mode==='suspicious') || ($mode==='batch' && random_int(0,1)===1);
     $ip=$suspicious ? $testIps[array_rand($testIps)] : $normalIps[array_rand($normalIps)];
     $port=$suspicious ? [22,3306,8080][array_rand([22,3306,8080])] : [80,443,53][array_rand([80,443,53])];
     $black=$pdo->prepare("SELECT COUNT(*) FROM blacklist_ips WHERE ip_address=?"); $black->execute([$ip]);
     $matched=$pdo->prepare("SELECT action FROM firewall_rules WHERE status=1 AND ip_address=? AND port=? ORDER BY id DESC LIMIT 1"); $matched->execute([$ip,$port]);
     $rule=$matched->fetchColumn();
     if ($black->fetchColumn()>0) { $decision='BLOCKED'; $detail='Ditolak: IP ada di blacklist'; }
     elseif ($rule==='BLOCK') { $decision='BLOCKED'; $detail='Ditolak oleh aturan firewall'; }
     elseif ($rule==='ALLOW') { $decision='ALLOWED'; $detail='Diizinkan oleh aturan firewall'; }
     elseif ($suspicious) { $decision='BLOCKED'; $detail='Ditolak oleh kebijakan simulasi untuk event mencurigakan'; }
     else { $decision='ALLOWED'; $detail='Traffic normal diizinkan'; }
     $activity=$suspicious?'Simulasi event mencurigakan':'Traffic normal';
     $ins=$pdo->prepare("INSERT INTO security_logs(source_ip,destination_port,activity,action_taken,details) VALUES(?,?,?,?,?)");
     $ins->execute([$ip,$port,$activity,$decision,$detail]);
     $created[]=['ip'=>$ip,'port'=>$port,'decision'=>$decision,'detail'=>$detail];
   }
   out(['ok'=>true,'created'=>$created,'message'=>"$count event simulasi berhasil dicatat."]);
 }
 out(['error'=>'Aksi tidak dikenal.'],404);
} catch (PDOException $e) {
 out(['error'=>$e->getCode()==='23000'?'Data sudah ada.':$e->getMessage()],500);
}
