<?php
require '../config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'hapus') {
            $pdo->prepare("DELETE FROM users WHERE id=? AND role='siswa'")->execute([(int)$_POST['id']]);
            flash('Anggota dihapus beserta riwayat transaksinya.');
        } else {
            $nama = trim($_POST['nama']); $nis = trim($_POST['nis']); $kelas = trim($_POST['kelas']);
            $user = trim($_POST['username']); $pass = $_POST['password'] ?? '';
            if ($nama === '' || $nis === '' || $user === '') flash('Nama, NIS, dan username wajib diisi.', 'err');
            elseif ($aksi === 'ubah') {
                $pdo->prepare("UPDATE users SET nama=?,nis=?,kelas=?,username=? WHERE id=? AND role='siswa'")->execute([$nama, $nis, $kelas ?: null, $user, (int)$_POST['id']]);
                if ($pass !== '') $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$_POST['id']]);
                flash('Data anggota diperbarui.');
            } elseif (strlen($pass) < 6) flash('Password minimal 6 karakter.', 'err');
            else {
                $pdo->prepare("INSERT INTO users (nama,nis,kelas,username,password,role) VALUES (?,?,?,?,?,'siswa')")
                    ->execute([$nama, $nis, $kelas ?: null, $user, password_hash($pass, PASSWORD_DEFAULT)]);
                flash('Anggota ditambahkan.');
            }
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() == 23000 ? 'Username sudah dipakai.' : 'Kesalahan database: ' . $ex->getMessage(), 'err');
    }
    redirect('/admin/anggota.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='siswa'"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch(); }
$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT * FROM users WHERE role='siswa' AND (nama LIKE :q OR nis LIKE :q OR kelas LIKE :q OR username LIKE :q) ORDER BY nama");
$st->execute([':q' => "%$q%"]);
$rows = $st->fetchAll();

$judul = 'Kelola Anggota'; $menu_aktif = 'anggota';
require '../includes/header.php';
?>
<div class="card">
  <h2 style="margin-top:0"><?= $edit ? 'Ubah anggota' : 'Tambah anggota' ?></h2>
  <form method="post">
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="row">
      <div><label>Nama</label><input name="nama" required value="<?= e($edit['nama'] ?? '') ?>"></div>
      <div><label>NIS</label><input name="nis" required value="<?= e($edit['nis'] ?? '') ?>"></div>
      <div><label>Kelas</label><input name="kelas" value="<?= e($edit['kelas'] ?? '') ?>"></div>
      <div><label>Username</label><input name="username" required value="<?= e($edit['username'] ?? '') ?>"></div>
      <div><label>Password<?= $edit ? ' (kosongkan jika tidak diubah)' : '' ?></label><input name="password" type="password" <?= $edit ? '' : 'required' ?>></div>
    </div>
    <p><button type="submit"><?= $edit ? 'Simpan perubahan' : 'Tambah anggota' ?></button>
    <?php if ($edit): ?> <a class="btn ghost" href="anggota.php">Batal</a><?php endif; ?></p>
  </form>
</div>

<form class="search" method="get">
  <input name="q" placeholder="Cari nama, NIS, kelas, username" value="<?= e($q) ?>">
  <button type="submit">Cari</button>
</form>
<div class="tablewrap"><table>
  <tr><th>Nama</th><th>NIS</th><th>Kelas</th><th>Username</th><th>Aksi</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['nama']) ?></td><td><?= e($r['nis']) ?></td><td><?= e($r['kelas']) ?></td><td><?= e($r['username']) ?></td>
    <td>
      <a class="btn sm ghost" href="?edit=<?= $r['id'] ?>">Ubah</a>
      <form class="inline" method="post" onsubmit="return confirm('Hapus anggota ini beserta riwayat transaksinya?')">
        <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="sm danger">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada anggota yang cocok<?= $q ? ' dengan pencarian "' . e($q) . '"' : '. Tambahkan anggota lewat form di atas' ?>.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
