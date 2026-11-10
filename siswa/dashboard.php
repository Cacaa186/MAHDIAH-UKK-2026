<?php
require '../config.php';
require_role('siswa');
$uid = $_SESSION['user']['id'];
$c = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(tgl_jatuh_tempo < CURDATE()),0) telat FROM transaksi WHERE user_id=? AND status='dipinjam'");
$c->execute([$uid]); $s = $c->fetch();
$judul = 'Dashboard Siswa'; $menu_aktif = 'dashboard';
require '../includes/header.php';
?>
<div class="grid">
  <div class="stat"><b><?= (int)$s['total'] ?> / <?= MAKS_PINJAM ?></b><span>Buku sedang dipinjam</span></div>
  <div class="stat"><b><?= (int)$s['telat'] ?></b><span>Buku terlambat</span></div>
</div>
<h2>Pilih menu</h2>
<div class="grid">
  <a class="tile" href="peminjaman.php"><b>Peminjaman buku</b><span>Cari buku dan pinjam</span></a>
  <a class="tile" href="pengembalian.php"><b>Pengembalian buku</b><span>Kembalikan buku dan lihat riwayat</span></a>
</div>
<?php require '../includes/footer.php'; ?>
