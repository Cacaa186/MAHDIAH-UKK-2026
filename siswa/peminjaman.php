<?php
require '../config.php';
require_role('siswa');
$uid = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = buat_pinjam($pdo, $uid, (int)$_POST['buku_id']);
    $err ? flash($err, 'err') : flash('Peminjaman berhasil. Kembalikan paling lambat ' . tgl(date('Y-m-d', strtotime('+' . LAMA_PINJAM . ' days'))) . '.');
    redirect('/siswa/peminjaman.php');
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM buku WHERE judul LIKE :q OR pengarang LIKE :q OR kategori LIKE :q OR kode_buku LIKE :q ORDER BY judul');
$st->execute([':q' => "%$q%"]);
$rows = $st->fetchAll();

$judul = 'Peminjaman Buku'; $menu_aktif = 'peminjaman';
require '../includes/header.php';
?>
<form class="search" method="get">
  <input name="q" placeholder="Cari judul, pengarang, kategori" value="<?= e($q) ?>">
  <button type="submit">Cari</button>
</form>
<div class="tablewrap"><table>
  <tr><th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Stok</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['kode_buku']) ?></td><td><?= e($r['judul']) ?></td><td><?= e($r['pengarang']) ?></td><td><?= e($r['kategori']) ?></td><td><?= $r['stok'] ?></td>
    <td><?php if ($r['stok'] > 0): ?>
      <form class="inline" method="post"><input type="hidden" name="buku_id" value="<?= $r['id'] ?>"><button class="sm">Pinjam</button></form>
    <?php else: ?><span class="badge telat">Habis</span><?php endif; ?></td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada buku yang cocok<?= $q ? ' dengan pencarian "' . e($q) . '"' : '' ?>. Coba kata kunci lain.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
