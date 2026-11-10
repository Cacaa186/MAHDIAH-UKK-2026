<?php
// $judul dan $menu_aktif diisi oleh halaman pemanggil
$u = $_SESSION['user'] ?? null;
$role = $u['role'] ?? null;
$nav = [];
if ($role === 'admin') {
    $nav = ['dashboard' => ['Dashboard', '/admin/dashboard.php'], 'buku' => ['Data Buku', '/admin/buku.php'],
            'anggota' => ['Anggota', '/admin/anggota.php'], 'transaksi' => ['Transaksi', '/admin/transaksi.php']];
} elseif ($role === 'siswa') {
    $nav = ['dashboard' => ['Dashboard', '/siswa/dashboard.php'], 'peminjaman' => ['Peminjaman Buku', '/siswa/peminjaman.php'],
            'pengembalian' => ['Pengembalian Buku', '/siswa/pengembalian.php']];
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Perpustakaan') ?> - Perpustakaan Sekolah Digital</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<?php if ($u): ?>
<div class="layout">
  <aside class="side">
    <div class="brand">Perpustakaan<small>Sekolah Digital</small></div>
    <nav>
      <?php foreach ($nav as $k => [$label, $url]): ?>
        <a href="<?= BASE_URL . $url ?>" class="<?= ($menu_aktif ?? '') === $k ? 'on' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <b><?= e($u['nama']) ?></b>
      <span><?= $role === 'admin' ? 'Admin' : 'Siswa' ?></span>
      <a href="<?= BASE_URL ?>/logout.php">Keluar</a>
    </div>
  </aside>
  <main class="main">
    <h1><?= e($judul ?? '') ?></h1>
    <?php if ($f = flash()): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
<?php endif; ?>
