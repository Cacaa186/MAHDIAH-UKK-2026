<?php
require '../config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'pinjam') {
        $err = buat_pinjam($pdo, (int)$_POST['user_id'], (int)$_POST['buku_id'], $_POST['tgl_pinjam'] ?: null);
        $err ? flash($err, 'err') : flash('Peminjaman dicatat.');
    } elseif ($aksi === 'kembali') {
        $err = buat_kembali($pdo, (int)$_POST['id']);
        $err ? flash($err, 'err') : flash('Pengembalian dicatat.');
    } elseif ($aksi === 'ubah') {
        $pdo->prepare("UPDATE transaksi SET tgl_jatuh_tempo=? WHERE id=? AND status='dipinjam'")->execute([$_POST['tgl_jatuh_tempo'], (int)$_POST['id']]);
        flash('Tanggal jatuh tempo diperbarui.');
    } elseif ($aksi === 'hapus') {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM transaksi WHERE id=?'); $st->execute([(int)$_POST['id']]);
        if ($t = $st->fetch()) {
            if ($t['status'] === 'dipinjam') $pdo->prepare('UPDATE buku SET stok=stok+1 WHERE id=?')->execute([$t['buku_id']]);
            $pdo->prepare('DELETE FROM transaksi WHERE id=?')->execute([$t['id']]);
        }
        $pdo->commit();
        flash('Transaksi dihapus.');
    }
    redirect('/admin/transaksi.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM transaksi WHERE id=? AND status='dipinjam'"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch(); }
$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT t.*, u.nama, u.nis, b.judul FROM transaksi t JOIN users u ON u.id=t.user_id JOIN buku b ON b.id=t.buku_id
    WHERE u.nama LIKE :q OR u.nis LIKE :q OR b.judul LIKE :q OR t.status LIKE :q ORDER BY t.id DESC");
$st->execute([':q' => "%$q%"]);
$rows = $st->fetchAll();
$anggota = $pdo->query("SELECT id,nama,nis FROM users WHERE role='siswa' ORDER BY nama")->fetchAll();
$bukus = $pdo->query('SELECT id,judul,stok FROM buku WHERE stok>0 ORDER BY judul')->fetchAll();

$judul = 'Transaksi'; $menu_aktif = 'transaksi';
require '../includes/header.php';
?>
<div class="card">
<?php if ($edit): ?>
  <h2 style="margin-top:0">Ubah jatuh tempo</h2>
  <form method="post">
    <input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <div class="row"><div><label>Tanggal jatuh tempo</label><input type="date" name="tgl_jatuh_tempo" required value="<?= e($edit['tgl_jatuh_tempo']) ?>"></div></div>
    <p><button type="submit">Simpan perubahan</button> <a class="btn ghost" href="transaksi.php">Batal</a></p>
  </form>
<?php else: ?>
  <h2 style="margin-top:0">Catat peminjaman</h2>
  <form method="post">
    <input type="hidden" name="aksi" value="pinjam">
    <div class="row">
      <div><label>Anggota</label>
        <select name="user_id" required><option value="">Pilih anggota</option>
          <?php foreach ($anggota as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['nama']) ?> (<?= e($a['nis']) ?>)</option><?php endforeach; ?>
        </select></div>
      <div><label>Buku</label>
        <select name="buku_id" required><option value="">Pilih buku</option>
          <?php foreach ($bukus as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['judul']) ?> (stok <?= $b['stok'] ?>)</option><?php endforeach; ?>
        </select></div>
      <div><label>Tanggal pinjam</label><input type="date" name="tgl_pinjam" value="<?= date('Y-m-d') ?>" required></div>
    </div>
    <p><button type="submit">Catat peminjaman</button> <small>Jatuh tempo otomatis <?= LAMA_PINJAM ?> hari, denda <?= rupiah(DENDA_PER_HARI) ?>/hari.</small></p>
  </form>
<?php endif; ?>
</div>

<form class="search" method="get">
  <input name="q" placeholder="Cari nama, NIS, judul, atau status" value="<?= e($q) ?>">
  <button type="submit">Cari</button>
</form>
<div class="tablewrap"><table>
  <tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh tempo</th><th>Kembali</th><th>Status</th><th>Denda</th><th>Aksi</th></tr>
  <?php foreach ($rows as $r):
    $aktif = $r['status'] === 'dipinjam';
    $telat = $aktif && hitung_denda($r['tgl_jatuh_tempo']) > 0;
    $denda = $aktif ? hitung_denda($r['tgl_jatuh_tempo']) : $r['denda']; ?>
  <tr>
    <td><?= e($r['nama']) ?><br><small><?= e($r['nis']) ?></small></td><td><?= e($r['judul']) ?></td>
    <td><?= tgl($r['tgl_pinjam']) ?></td><td><?= tgl($r['tgl_jatuh_tempo']) ?></td><td><?= tgl($r['tgl_kembali']) ?></td>
    <td><span class="badge <?= $telat ? 'telat' : ($aktif ? 'pinjam' : 'kembali') ?>"><?= $telat ? 'Terlambat' : ($aktif ? 'Dipinjam' : 'Dikembalikan') ?></span></td>
    <td><?= $denda ? rupiah($denda) : '-' ?></td>
    <td>
      <?php if ($aktif): ?>
        <form class="inline" method="post"><input type="hidden" name="aksi" value="kembali"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sm">Kembalikan</button></form>
        <a class="btn sm ghost" href="?edit=<?= $r['id'] ?>">Ubah</a>
      <?php endif; ?>
      <form class="inline" method="post" onsubmit="return confirm('Hapus transaksi ini?')"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sm danger">Hapus</button></form>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty">Belum ada transaksi<?= $q ? ' yang cocok dengan pencarian "' . e($q) . '"' : '. Catat peminjaman pertama lewat form di atas' ?>.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
