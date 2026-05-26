-- Database schema for Kantin app
-- Users table with role-specific kelas attribute and tipe_pengguna
CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
    tipe_pengguna ENUM('siswa','guru') NULL,
    nip VARCHAR(20) UNIQUE NULL,
    kelas ENUM('10','11','12') NULL,
    bahasa VARCHAR(10) NOT NULL DEFAULT 'id',
    foto_profil VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_user_kelas CHECK (
        (role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas IN ('10','11','12')) OR
        (role = 'pembeli' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'penjual' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'admin' AND tipe_pengguna IS NULL AND kelas IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kantin table related to penjual user
CREATE TABLE IF NOT EXISTS kantin (
    id_kantin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penjual INT UNSIGNED NOT NULL,
    nama_kantin VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    alamat VARCHAR(255),
    jam_buka TIME NOT NULL DEFAULT '07:00:00',
    jam_tutup TIME NOT NULL DEFAULT '15:00:00',
    tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'manual',
    status_buka ENUM('Buka','Tutup') NOT NULL DEFAULT 'Tutup',
    rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_ulasan INT UNSIGNED NOT NULL DEFAULT 0,
    logo VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penjual) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactional query to promote students and delete kelas 12
START TRANSACTION;

DELETE FROM users
WHERE role = 'pembeli'
  AND kelas = '12';

UPDATE users
SET kelas = '12'
WHERE role = 'pembeli'
  AND kelas = '11';

UPDATE users
SET kelas = '11'
WHERE role = 'pembeli'
  AND kelas = '10';

COMMIT;
