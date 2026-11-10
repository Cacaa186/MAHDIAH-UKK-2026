<?php
require 'config.php';
$error = '';
$v = ['nama' => '', 'nis' => '', 'kelas' => '', 'username' => '', 'role' => 'siswa'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $v['role'] = $v['role'] === 'admin' ? 'admin' : 'siswa';
    $pass = $_POST['password'] ?? '';
    if ($v['nama'] === '' || $v['username'] === '' || $pass === '') $error = 'Nama, username, dan password wajib diisi.';
    elseif (strlen($pass) < 6) $error = 'Password minimal 6 karakter.';
    elseif ($v['role'] === 'siswa' && $v['nis'] === '') $error = 'NIS wajib diisi untuk siswa.';
    elseif ($v['role'] === 'admin' && ($_POST['kode_admin'] ?? '') !== KODE_ADMIN) $error = 'Kode admin salah.';
    else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE username=?');
        $cek->execute([$v['username']]);
        if ($cek->fetch()) $error = 'Username sudah dipakai. Pilih username lain.';
        else {
            $pdo->prepare('INSERT INTO users (nama,nis,kelas,username,password,role) VALUES (?,?,?,?,?,?)')
                ->execute([$v['nama'], $v['nis'] ?: null, $v['kelas'] ?: null, $v['username'], password_hash($pass, PASSWORD_DEFAULT), $v['role']]);
            flash('Pendaftaran berhasil. Silakan masuk.');
            redirect('/index.php');
        }
    }
}
$judul = 'Daftar';
require 'includes/header.php';
if ($f = flash()) echo '<div class="auth"><div class="alert ' . $f[1] . '">' . e($f[0]) . '</div></div>';
?>
<div class="auth">
  <div class="card">
    <h1>Daftar anggota</h1>
    <p class="sub">Isi data berikut untuk membuat akun.</p>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <label for="role">Daftar sebagai</label>
      <select name="role" id="role" onchange="document.getElementById('kd').style.display=this.value==='admin'?'block':'none'">
        <option value="siswa" <?= $v['role'] === 'siswa' ? 'selected' : '' ?>>Siswa</option>
        <option value="admin" <?= $v['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
      <div id="kd" style="display:<?= $v['role'] === 'admin' ? 'block' : 'none' ?>">
        <label for="kode_admin">Kode admin</label>
        <input id="kode_admin" name="kode_admin" type="password">
      </div>
      <label for="nama">Nama lengkap</label>
      <input id="nama" name="nama" required value="<?= e($v['nama']) ?>">
      <div class="row">
        <div><label for="nis">NIS</label><input id="nis" name="nis" value="<?= e($v['nis']) ?>"></div>
        <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" value="<?= e($v['kelas']) ?>"></div>
      </div>
      <label for="username">Username</label>
      <input id="username" name="username" required value="<?= e($v['username']) ?>">
      <label for="password">Password (min. 6 karakter)</label>
      <input id="password" type="password" name="password" required>
      <button type="submit">Daftar</button>
    </form>
    <div class="foot">Sudah punya akun? <a href="<?= BASE_URL ?>/index.php">Masuk</a></div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
