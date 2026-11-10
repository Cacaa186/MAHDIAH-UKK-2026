<?php
require '../config.php';
require_role('siswa');
$uid = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = buat_kembali($pdo, (int)$_POST['id'], $uid);
    $err ? flash($err, 'err') : flash('Buku berhasil dikembalikan.');
    redirect('/siswa/pengembalian.php');
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT t.*, b.judul FROM transaksi t JOIN buku b ON b.id=t.buku_id WHERE t.user_id=:u AND (b.judul LIKE :q OR t.status LIKE :q) ORDER BY t.id DESC');
$st->execute([':u' => $uid, ':q' => "%$q%"]);
$rows = $st->fetchAll();

$judul = 'Pengembalian Buku'; $menu_aktif = 'pengembalian';
require '../includes/header.php';
?>
<form class="search" method="get">
  <input name="q" placeholder="Cari judul atau status (dipinjam / dikembalikan)" value="<?= e($q) ?>">
  <button type="submit">Cari</button>
</form>
<div class="tablewrap"><table>
  <tr><th>Buku</th><th>Pinjam</th><th>Jatuh tempo</th><th>Kembali</th><th>Status</th><th>Denda</th><th></th></tr>
  <?php foreach ($rows as $r):
    $aktif = $r['status'] === 'dipinjam';
    $denda = $aktif ? hitung_denda($r['tgl_jatuh_tempo']) : $r['denda']; ?>
  <tr>
    <td><?= e($r['judul']) ?></td><td><?= tgl($r['tgl_pinjam']) ?></td><td><?= tgl($r['tgl_jatuh_tempo']) ?></td><td><?= tgl($r['tgl_kembali']) ?></td>
    <td><span class="badge <?= $aktif ? ($denda ? 'telat' : 'pinjam') : 'kembali' ?>"><?= $aktif ? ($denda ? 'Terlambat' : 'Dipinjam') : 'Dikembalikan' ?></span></td>
    <td><?= $denda ? rupiah($denda) : '-' ?></td>
    <td><?php if ($aktif): ?>
      <form class="inline" method="post" onsubmit="return confirm('Kembalikan buku ini?')"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sm">Kembalikan</button></form>
    <?php endif; ?></td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">Belum ada riwayat peminjaman. Pinjam buku lewat menu Peminjaman Buku.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
