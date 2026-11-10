# Aplikasi Peminjaman Buku - Perpustakaan Sekolah Digital

PHP native + MySQL (PDO), berjalan di XAMPP (localhost, tanpa internet).

## Cara menjalankan (XAMPP)
1. buka **XAMPP Control Panel**, klik **Start** pada **Apache** dan **MySQL**.
2. Salin folder `perpustakaan` ke `C:\xampp\htdocs\` sehingga jadi `C:\xampp\htdocs\perpustakaan`.
3. Buka `http://localhost/phpmyadmin` -> tab **Import** -> pilih `database/perpustakaan.sql` -> **Import**.
4. Buka `http://localhost/perpustakaan/`.

Jika nama folder diganti, ubah `BASE_URL` di `config.php`. Jika MySQL punya password, ubah di `config.php` (baris `new PDO`).

## Akun contoh
| Role  | Username | Password |
|-------|----------|----------|
| Admin | admin    | admin123 |
| Siswa | siswa1   | siswa123 |

Daftar sebagai admin di halaman Daftar membutuhkan kode: `ADMIN2026` (ubah di `config.php`).

## Struktur
- `index.php` login (pilih Admin/Siswa), `register.php` daftar, `logout.php`
- `admin/` dashboard, buku, anggota, transaksi (CRUD + pencarian)
- `siswa/` dashboard, peminjaman, pengembalian (+ pencarian)
- `includes/` header/footer, `assets/style.css`, `database/perpustakaan.sql`
- `config.php` koneksi, konstanta, dan fungsi bersama

## ERD
```
users (id PK, nama, nis, kelas, username UNIQUE, password, role[admin|siswa])
   1 ---- N
transaksi (id PK, user_id FK, buku_id FK, tgl_pinjam, tgl_jatuh_tempo, tgl_kembali, status, denda)
   N ---- 1
buku (id PK, kode_buku UNIQUE, judul, pengarang, penerbit, tahun, kategori, stok)
```

## Dokumentasi fungsi (config.php)
| Fungsi | Kegunaan |
|---|---|
| `require_role($role)` | Membatasi halaman hanya untuk admin / siswa, selain itu kembali ke login |
| `buat_pinjam($pdo,$user,$buku,$tgl)` | Validasi stok, batas 3 buku, buku ganda; simpan transaksi dan kurangi stok (pakai transaction) |
| `buat_kembali($pdo,$id,$user)` | Set status dikembalikan, hitung denda, tambah stok; siswa hanya bisa sendiri |
| `hitung_denda($tempo,$kembali)` | Hari terlambat x Rp1.000 |
| `flash()`, `e()`, `rupiah()`, `tgl()` | Pesan notifikasi, escape HTML, format rupiah, format tanggal |

## Keamanan yang diterapkan
Password di-hash (`password_hash`), query memakai prepared statement, output di-escape (`htmlspecialchars`), halaman dibatasi per role.

## Matriks fitur
| Fitur | Siswa | Admin |
|---|---|---|
| Daftar, Login, Pemilihan menu | Ya | Ya |
| CRUD transaksi | Ya (pinjam, kembalikan, riwayat) | Ya (penuh) |
| CRUD data buku | Tidak | Ya |
| CRUD anggota | Tidak | Ya |
| Pencarian | Ya | Ya |
