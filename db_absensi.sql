-- absensi_system.sql

-- Buat database (ganti nama sesuai kebutuhan)
CREATE DATABASE IF NOT EXISTS absensi_db;
USE absensi_db;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    level ENUM('admin', 'siswa') NOT NULL,
    kelas VARCHAR(20) DEFAULT NULL
);

-- Tabel absen
CREATE TABLE IF NOT EXISTS absen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NOT NULL,
    status ENUM('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);

-- Data contoh users
INSERT INTO users (username, password, level, kelas) VALUES
('admin1', SHA2('adminpass123', 256), 'admin', 'X IPA 1'),
('siswa1', SHA2('password1', 256), 'siswa', 'X IPA 1'),
('siswa2', SHA2('password2', 256), 'siswa', 'X IPS 2');

-- Data contoh absen
INSERT INTO absen (username, tanggal, keterangan, status) VALUES
('siswa1', CURDATE(), 'Sakit, tidak masuk', 'Pending'),
('siswa2', CURDATE(), 'Izin ada urusan keluarga', 'Disetujui');
