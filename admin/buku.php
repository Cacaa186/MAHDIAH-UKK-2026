<?php
require '../config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'hapus') {
            $pdo->prepare('DELETE FROM buku WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Buku dihapus.');
        } else {
            $d = [trim($_POST['kode_buku']), trim($_POST['judul']), trim($_POST['pengarang']), trim($_POST['penerbit']),
                  (int)$_POST['tahun'] ?: null, trim($_POST['kategori']), max(0, (int)$_POST['stok'])];
            if ($d[0] === '' || $d[1] === '' || $d[2] === '') { flash('Kode, judul, dan pengarang wajib diisi.', 'err'); }
            elseif ($aksi === 'ubah') {
                $pdo->prepare('UPDATE buku SET kode_buku=?,judul=?,pengarang=?,penerbit=?,tahun=?,kategori=?,stok=? WHERE id=?')
                    ->execute([...$d, (int)$_POST['id']]);
                flash('Data buku diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO buku (kode_buku,judul,pengarang,penerbit,tahun,kategori,stok) VALUES (?,?,?,?,?,?,?)')->execute($d);
                flash('Buku ditambahkan.');
            }
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() == 23000
            ? ($aksi === 'hapus' ? 'Buku tidak bisa dihapus karena punya riwayat transaksi.' : 'Kode buku sudah dipakai.')
            : 'Kesalahan database: ' . $ex->getMessage(), 'err');
    }
    redirect('/admin/buku.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare('SELECT * FROM buku WHERE id=?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch(); }
$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM buku WHERE kode_buku LIKE :q OR judul LIKE :q OR pengarang LIKE :q OR kategori LIKE :q ORDER BY judul');
$st->execute([':q' => "%$q%"]);
$rows = $st->fetchAll();

$judul = 'Data Buku'; $menu_aktif = 'buku';
require '../includes/header.php';
?>
<div class="card">
  <h2 style="margin-top:0"><?= $edit ? 'Ubah buku' : 'Tambah buku' ?></h2>
  <form method="post">
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="row">
      <div><label>Kode buku</label><input name="kode_buku" required value="<?= e($edit['kode_buku'] ?? '') ?>"></div>
      <div><label>Judul</label><input name="judul" required value="<?= e($edit['judul'] ?? '') ?>"></div>
      <div><label>Pengarang</label><input name="pengarang" required value="<?= e($edit['pengarang'] ?? '') ?>"></div>
      <div><label>Penerbit</label><input name="penerbit" value="<?= e($edit['penerbit'] ?? '') ?>"></div>
      <div><label>Tahun</label><input name="tahun" type="number" min="1900" max="2100" value="<?= e($edit['tahun'] ?? '') ?>"></div>
      <div><label>Kategori</label><input name="kategori" value="<?= e($edit['kategori'] ?? '') ?>"></div>
      <div><label>Stok</label><input name="stok" type="number" min="0" required value="<?= e($edit['stok'] ?? 1) ?>"></div>
    </div>
    <p><button type="submit"><?= $edit ? 'Simpan perubahan' : 'Tambah buku' ?></button>
    <?php if ($edit): ?> <a class="btn ghost" href="buku.php">Batal</a><?php endif; ?></p>
  </form>
</div>

<form class="search" method="get">
  <input name="q" placeholder="Cari kode, judul, pengarang, kategori" value="<?= e($q) ?>">
  <button type="submit">Cari</button>
</form>
<div class="tablewrap"><table>
  <tr><th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Stok</th><th>Aksi</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['kode_buku']) ?></td><td><?= e($r['judul']) ?></td><td><?= e($r['pengarang']) ?></td>
    <td><?= e($r['kategori']) ?></td><td><?= $r['stok'] ?></td>
    <td>
      <a class="btn sm ghost" href="?edit=<?= $r['id'] ?>">Ubah</a>
      <form class="inline" method="post" onsubmit="return confirm('Hapus buku ini?')">
        <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="sm danger">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada buku yang cocok<?= $q ? ' dengan pencarian "' . e($q) . '"' : '. Tambahkan buku lewat form di atas' ?>.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
