CREATE DATABASE IF NOT EXISTS perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE perpustakaan;

DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS buku;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  nis VARCHAR(20) DEFAULT NULL,
  kelas VARCHAR(20) DEFAULT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE buku (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_buku VARCHAR(20) NOT NULL UNIQUE,
  judul VARCHAR(150) NOT NULL,
  pengarang VARCHAR(100) NOT NULL,
  penerbit VARCHAR(100) DEFAULT NULL,
  tahun YEAR DEFAULT NULL,
  kategori VARCHAR(50) DEFAULT NULL,
  stok INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  buku_id INT NOT NULL,
  tgl_pinjam DATE NOT NULL,
  tgl_jatuh_tempo DATE NOT NULL,
  tgl_kembali DATE DEFAULT NULL,
  status ENUM('dipinjam','dikembalikan') NOT NULL DEFAULT 'dipinjam',
  denda INT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Akun contoh: admin / admin123  |  siswa1 / siswa123
INSERT INTO users (nama, nis, kelas, username, password, role) VALUES
('Administrator', NULL, NULL, 'admin', '$2y$10$qK8C/Yzg1.BBZW8LcvxyJuinPoiXKrVQHBpc3rbMzOK/K9TtagUJe', 'admin'),
('Budi Santoso', '2025001', 'XI RPL 1', 'siswa1', '$2y$10$XGZ/ZP5jDfYEmnMTbZAXv.o2IGZA/q/mjiQDMeJuHjhGvzFV9f77q', 'siswa');

INSERT INTO buku (kode_buku, judul, pengarang, penerbit, tahun, kategori, stok) VALUES
('BK001','Laskar Pelangi','Andrea Hirata','Bentang Pustaka',2005,'Novel',5),
('BK002','Bumi Manusia','Pramoedya Ananta Toer','Hasta Mitra',1980,'Novel',3),
('BK003','Pemrograman Web dengan PHP dan MySQL','Abdul Kadir','Andi',2019,'Teknologi',4),
('BK004','Basis Data untuk Pemula','Rosa A.S.','Informatika',2018,'Teknologi',4),
('BK005','Matematika Kelas XI','Kemendikbud','Kemendikbud',2022,'Pelajaran',10);
