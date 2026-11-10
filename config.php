<?php
// ====== KONFIGURASI ======
// Nama folder proyek di htdocs. Jika folder diganti namanya, ubah juga BASE_URL.
define('BASE_URL', '/perpustakaan');
define('LAMA_PINJAM', 7);        // hari
define('DENDA_PER_HARI', 1000);  // rupiah
define('MAKS_PINJAM', 3);        // buku aktif per siswa
define('KODE_ADMIN', 'ADMIN2026'); // kode rahasia untuk mendaftar sebagai admin

session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=perpustakaan;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('<h3>Koneksi database gagal.</h3><p>Pastikan MySQL di XAMPP sudah Start dan database <b>perpustakaan</b> sudah di-import.</p>');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($path) { header('Location: ' . BASE_URL . $path); exit; }

function require_role($role) {
    if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== $role) redirect('/index.php');
}

function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

function tgl($d) { return $d ? date('d-m-Y', strtotime($d)) : '-'; }

// Hitung denda: hari terlambat x tarif
function hitung_denda($jatuh_tempo, $tgl_kembali = null) {
    $akhir = new DateTime($tgl_kembali ?: 'today');
    $diff = (new DateTime($jatuh_tempo))->diff($akhir);
    return $diff->invert ? 0 : $diff->days * DENDA_PER_HARI;
}

// Buat transaksi peminjaman. Mengembalikan pesan error, atau null jika berhasil.
function buat_pinjam(PDO $pdo, $user_id, $buku_id, $tgl_pinjam = null) {
    $tgl_pinjam = $tgl_pinjam ?: date('Y-m-d');
    $jatuh_tempo = date('Y-m-d', strtotime($tgl_pinjam . ' +' . LAMA_PINJAM . ' days'));
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT stok FROM buku WHERE id=? FOR UPDATE');
        $st->execute([$buku_id]);
        $buku = $st->fetch();
        if (!$buku) { $pdo->rollBack(); return 'Buku tidak ditemukan.'; }
        if ($buku['stok'] < 1) { $pdo->rollBack(); return 'Stok buku habis.'; }

        $st = $pdo->prepare("SELECT COUNT(*) c, SUM(buku_id=?) sama FROM transaksi WHERE user_id=? AND status='dipinjam'");
        $st->execute([$buku_id, $user_id]);
        $r = $st->fetch();
        if ($r['sama'] > 0) { $pdo->rollBack(); return 'Buku ini masih dipinjam oleh anggota tersebut.'; }
        if ($r['c'] >= MAKS_PINJAM) { $pdo->rollBack(); return 'Batas peminjaman ' . MAKS_PINJAM . ' buku sudah tercapai.'; }

        $pdo->prepare('INSERT INTO transaksi (user_id,buku_id,tgl_pinjam,tgl_jatuh_tempo) VALUES (?,?,?,?)')
            ->execute([$user_id, $buku_id, $tgl_pinjam, $jatuh_tempo]);
        $pdo->prepare('UPDATE buku SET stok=stok-1 WHERE id=?')->execute([$buku_id]);
        $pdo->commit();
        return null;
    } catch (Exception $ex) { $pdo->rollBack(); return 'Terjadi kesalahan: ' . $ex->getMessage(); }
}

// Proses pengembalian. $user_id diisi bila yang mengembalikan adalah siswa (hanya boleh miliknya).
function buat_kembali(PDO $pdo, $transaksi_id, $user_id = null) {
    $sql = "SELECT * FROM transaksi WHERE id=? AND status='dipinjam'";
    $par = [$transaksi_id];
    if ($user_id) { $sql .= ' AND user_id=?'; $par[] = $user_id; }
    $st = $pdo->prepare($sql); $st->execute($par);
    $t = $st->fetch();
    if (!$t) return 'Data peminjaman tidak ditemukan atau sudah dikembalikan.';
    $denda = hitung_denda($t['tgl_jatuh_tempo']);
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE transaksi SET status='dikembalikan', tgl_kembali=CURDATE(), denda=? WHERE id=?")->execute([$denda, $t['id']]);
    $pdo->prepare('UPDATE buku SET stok=stok+1 WHERE id=?')->execute([$t['buku_id']]);
    $pdo->commit();
    return null;
}
