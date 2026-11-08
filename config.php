<?php
// Konfigurasi database CyberShield
$host = "localhost";
$db   = "cybershield";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die("Koneksi database gagal. Pastikan MySQL aktif dan database cybershield sudah di-import dari database.sql. Detail: " .
        htmlspecialchars($e->getMessage()));
}
