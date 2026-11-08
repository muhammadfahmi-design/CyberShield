CYBERSHIELD - PETUNJUK INSTALASI
================================

Aplikasi ini adalah dashboard web simulasi keamanan jaringan untuk tugas UTS.
Aplikasi tidak menangkap paket jaringan sungguhan, tidak mengubah firewall OS,
dan tidak menjalankan serangan. Semua event dibuat secara lokal sebagai data simulasi.

KEBUTUHAN
- XAMPP (Apache dan MySQL)
- Browser (Chrome/Edge/Firefox)

CARA MENJALANKAN
1. Ekstrak folder CyberShield ke:
   C:\xampp\htdocs\CyberShield
2. Buka XAMPP Control Panel, klik Start pada Apache dan MySQL.
3. Buka http://localhost/phpmyadmin
4. Pilih tab Import, pilih file database.sql dari folder CyberShield, lalu klik Import/Go.
   Database cybershield dan tiga tabel akan dibuat otomatis.
5. Buka browser: http://localhost/CyberShield

FITUR YANG SUDAH BERFUNGSI
- Dashboard menghitung paket/event, koneksi diizinkan, event diblokir, dan aturan aktif.
- Data dashboard, tabel, dan grafik diperbarui otomatis setiap 4 detik.
- Tambah, aktif/nonaktifkan, dan hapus aturan firewall.
- Simulasi traffic normal, event mencurigakan, atau 5 event sekaligus.
- Keputusan simulasi memeriksa blacklist dan aturan firewall aktif.
- Log keamanan tersimpan di MySQL.
- Tambah dan hapus IP blacklist.
- Validasi alamat IP, port, dan penggunaan prepared statements.

PENGUJIAN CEPAT
1. Buka Dashboard.
2. Klik "Simulasikan traffic normal"; jumlah paket dan koneksi diizinkan bertambah.
3. Klik "Simulasikan event mencurigakan"; jumlah paket dan ancaman diblokir bertambah.
4. Buka Log Keamanan untuk melihat catatan event.
5. Tambah IP contoh 203.0.113.88 ke blacklist, lalu jalankan simulasi mencurigakan beberapa kali.
6. Coba tambah aturan IP 192.0.2.10 port 80 tindakan BLOCK, lalu aktif/nonaktifkan.
7. Perhatikan dashboard berubah otomatis.

CATATAN
- Kredensial database default XAMPP: user root, password kosong.
- Jika MySQL memakai password, ubah variabel $pass di config.php.
- Gunakan hanya untuk demo lokal dan data simulasi.
