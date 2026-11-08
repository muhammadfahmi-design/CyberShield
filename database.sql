CREATE DATABASE IF NOT EXISTS cybershield
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cybershield;

CREATE TABLE IF NOT EXISTS firewall_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rule_name VARCHAR(100) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  port INT NOT NULL,
  action ENUM('ALLOW','BLOCK') NOT NULL DEFAULT 'BLOCK',
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS security_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  source_ip VARCHAR(45) NOT NULL,
  destination_port INT NOT NULL,
  activity VARCHAR(120) NOT NULL,
  action_taken ENUM('ALLOWED','BLOCKED') NOT NULL,
  details VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS blacklist_ips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(180) DEFAULT 'Ditambahkan oleh administrator',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO firewall_rules (rule_name, ip_address, port, action, status)
SELECT 'Izinkan jaringan lokal', '192.168.1.10', 80, 'ALLOW', 1
WHERE NOT EXISTS (SELECT 1 FROM firewall_rules WHERE rule_name='Izinkan jaringan lokal');

INSERT INTO firewall_rules (rule_name, ip_address, port, action, status)
SELECT 'Blokir alamat uji', '203.0.113.25', 22, 'BLOCK', 1
WHERE NOT EXISTS (SELECT 1 FROM firewall_rules WHERE rule_name='Blokir alamat uji');

INSERT IGNORE INTO blacklist_ips (ip_address, reason)
VALUES ('198.51.100.77', 'Contoh IP simulasi yang diblokir');
