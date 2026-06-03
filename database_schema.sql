-- ============================================================
-- Database Schema untuk Aplikasi Kantin
-- Database: db_kantin
-- Created: 2026-06-03
-- ============================================================

-- DROP DATABASE IF EXISTS db_kantin;
CREATE DATABASE IF NOT EXISTS db_kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_kantin;

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
    tipe_pengguna ENUM('siswa','guru') NULL,
    nip VARCHAR(20) UNIQUE NULL,
    kelas ENUM('10','11','12') NULL,
    id_kantin INT NULL,
    nama_kantin VARCHAR(150) NULL,
    foto_profil VARCHAR(255) NULL,
    bahasa VARCHAR(10) NOT NULL DEFAULT 'id',
    reset_token VARCHAR(100) NULL,
    reset_expired DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_user_kelas CHECK (
        (role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas IN ('10','11','12')) OR
        (role = 'pembeli' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'penjual' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'admin' AND tipe_pengguna IS NULL AND kelas IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: kantin
-- ============================================================
CREATE TABLE IF NOT EXISTS kantin (
    id_kantin INT AUTO_INCREMENT PRIMARY KEY,
    nama_kantin VARCHAR(150) NOT NULL,
    deskripsi TEXT NULL,
    logo VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    jam_buka TIME NULL DEFAULT '07:00:00',
    jam_tutup TIME NULL DEFAULT '15:00:00',
    status_buka ENUM('Buka','Tutup') NULL DEFAULT 'Buka',
    tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'otomatis',
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    total_rating INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: menu
-- ============================================================
CREATE TABLE IF NOT EXISTS menu (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    id_kantin INT NOT NULL,
    nama_menu VARCHAR(150) NOT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    foto VARCHAR(255) NULL,
    foto_menu VARCHAR(255) NULL,
    kategori VARCHAR(50) NOT NULL DEFAULT 'Makanan',
    deskripsi TEXT NULL,
    opsi_pilihan TEXT NULL,
    stok INT NOT NULL DEFAULT 0,
    status ENUM('Tersedia','Habis') NOT NULL DEFAULT 'Tersedia',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_kantin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: keranjang
-- ============================================================
CREATE TABLE IF NOT EXISTS keranjang (
    id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_menu INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    catatan TEXT NULL,
    opsi_pilihan VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_user),
    INDEX (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: favorit
-- ============================================================
CREATE TABLE IF NOT EXISTS favorit (
    id_favorit INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_menu INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorit (id_user, id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: pesanan
-- ============================================================
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
    kode_pesanan VARCHAR(50) NULL,
    id_user INT NOT NULL,
    id_kantin INT NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    pajak INT NOT NULL DEFAULT 0,
    metode_pembayaran VARCHAR(30) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    catatan TEXT NULL,
    bukti_pembayaran VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_user),
    INDEX (id_kantin),
    INDEX (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: detail_pesanan
-- ============================================================
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_menu INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    nama_menu VARCHAR(150) NULL,
    nama_kantin VARCHAR(150) NULL,
    catatan TEXT NULL,
    opsi_pilihan VARCHAR(120) NULL,
    INDEX (id_pesanan),
    INDEX (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: ulasan
-- ============================================================
CREATE TABLE IF NOT EXISTS ulasan (
    id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_menu INT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    komentar TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ulasan (id_user, id_menu),
    INDEX (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: rating_menu
-- ============================================================
CREATE TABLE IF NOT EXISTS rating_menu (
    id_rating INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_menu INT NOT NULL,
    nilai_rating TINYINT NOT NULL DEFAULT 5,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (id_user, id_menu),
    INDEX (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: transaksi
-- ============================================================
CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_kantin INT NULL,
    id_pesanan INT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_pajak DECIMAL(12,2) NOT NULL DEFAULT 1000,
    metode_pembayaran VARCHAR(50) NULL DEFAULT 'Cash',
    status VARCHAR(30) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: detail_transaksi
-- ============================================================
CREATE TABLE IF NOT EXISTS detail_transaksi (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_menu INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    INDEX (id_transaksi),
    INDEX (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: admin_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_logs (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_user),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: password_resets
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id_reset INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expired_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (token),
    INDEX (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: chat_conversations
-- ============================================================
CREATE TABLE IF NOT EXISTS chat_conversations (
    id_conversation INT AUTO_INCREMENT PRIMARY KEY,
    id_seller INT NOT NULL,
    id_buyer INT NOT NULL,
    id_kantin INT NOT NULL,
    id_order INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_message TEXT NULL,
    last_message_at DATETIME NULL,
    UNIQUE KEY unique_convo (id_seller, id_buyer, id_kantin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: chat_messages
-- ============================================================
CREATE TABLE IF NOT EXISTS chat_messages (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_conversation INT NOT NULL,
    id_sender INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (id_conversation),
    INDEX idx_sender (id_sender),
    INDEX idx_created (created_at),
    FOREIGN KEY (id_conversation) REFERENCES chat_conversations(id_conversation) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL
-- ============================================================

-- Insert admin user
INSERT IGNORE INTO users (username, email, password, role) VALUES
('admin', 'admin@kantin.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC33z.8qvwhUiJ2iF2m', 'admin');

-- Insert pembeli user
INSERT IGNORE INTO users (username, email, password, role, tipe_pengguna, kelas) VALUES
('buyer', 'buyer@kantin.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4GlOi', 'pembeli', 'siswa', '10');

-- Insert penjual user
INSERT IGNORE INTO users (username, email, password, role, tipe_pengguna) VALUES
('kantin_demo', 'penjual@kantin.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4GlOi', 'penjual', 'guru');

-- Insert kantin demo
INSERT IGNORE INTO kantin (nama_kantin, deskripsi, jam_buka, jam_tutup, status_buka, tipe_operasi) VALUES
('Kantin Demo', 'Kantin contoh untuk mencoba aplikasi.', '07:00:00', '23:59:00', 'Buka', 'otomatis');

-- Update penjual dengan id_kantin
UPDATE users SET id_kantin = 1, nama_kantin = 'Kantin Demo' WHERE username = 'kantin_demo' AND id_kantin IS NULL;

-- Insert menu demo
INSERT IGNORE INTO menu (id_kantin, nama_menu, harga, kategori, deskripsi, stok, status) VALUES
(1, 'Nasi Goreng Demo', 15000, 'Makanan', 'Menu contoh siap pesan.', 20, 'Tersedia'),
(1, 'Es Teh Demo', 5000, 'Minuman', 'Minuman contoh.', 30, 'Tersedia'),
(1, 'Roti Bakar Demo', 8000, 'Makanan', 'Roti bakar lezat.', 15, 'Tersedia');
