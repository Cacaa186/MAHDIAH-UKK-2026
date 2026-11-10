<?php
require '../config.php';
require_role('admin');
$jml = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$judul = 'Dashboard Admin'; $menu_aktif = 'dashboard';
require '../includes/header.php';
?>
<div class="grid">
  <div class="stat"><b><?= $jml('SELECT COUNT(*) FROM buku') ?></b><span>Judul buku</span></div>
  <div class="stat"><b><?= $jml("SELECT COUNT(*) FROM users WHERE role='siswa'") ?></b><span>Anggota</span></div>
  <div class="stat"><b><?= $jml("SELECT COUNT(*) FROM transaksi WHERE status='dipinjam'") ?></b><span>Sedang dipinjam</span></div>
  <div class="stat"><b><?= $jml("SELECT COUNT(*) FROM transaksi WHERE status='dipinjam' AND tgl_jatuh_tempo < CURDATE()") ?></b><span>Terlambat</span></div>
</div>
<h2>Pilih menu</h2>
<div class="grid">
  <a class="tile" href="buku.php"><b>Kelola data buku</b><span>Tambah, ubah, hapus, dan cari buku</span></a>
  <a class="tile" href="transaksi.php"><b>Transaksi</b><span>Catat peminjaman dan pengembalian</span></a>
  <a class="tile" href="anggota.php"><b>Kelola anggota</b><span>Kelola data siswa anggota perpustakaan</span></a>
</div>
<?php require '../includes/footer.php'; ?>
