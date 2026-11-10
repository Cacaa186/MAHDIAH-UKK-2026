<?php
require 'config.php';
if (!empty($_SESSION['user'])) redirect($_SESSION['user']['role'] === 'admin' ? '/admin/dashboard.php' : '/siswa/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'siswa';
    $st = $pdo->prepare('SELECT * FROM users WHERE username=? AND role=?');
    $st->execute([trim($_POST['username'] ?? ''), $role]);
    $user = $st->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        redirect($role === 'admin' ? '/admin/dashboard.php' : '/siswa/dashboard.php');
    }
    $error = 'Username, password, atau jenis login salah. Periksa lagi lalu coba masuk.';
}
$judul = 'Masuk';
require 'includes/header.php';
?>
<div class="auth">
  <div class="card">
    <h1>Masuk</h1>
    <p class="sub">Perpustakaan Sekolah Digital</p>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <label for="role">Login sebagai</label>
      <select name="role" id="role">
        <option value="siswa" <?= ($_POST['role'] ?? '') === 'siswa' ? 'selected' : '' ?>>Siswa</option>
        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
      <label for="username">Username</label>
      <input id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
      <button type="submit">Masuk</button>
    </form>
    <div class="foot">Belum jadi anggota? <a href="<?= BASE_URL ?>/register.php">Daftar anggota</a></div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
