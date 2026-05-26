-- Fix struktur tabel users untuk bug registrasi
-- Issue: kolom id_kantin NOT NULL tanpa default, username/email nullable

-- 1. Hapus kolom problematic
ALTER TABLE users DROP COLUMN IF EXISTS id_kantin;
ALTER TABLE users DROP COLUMN IF EXISTS nama_kantin;

-- 2. Set kolom menjadi NOT NULL dengan constraint yang benar
ALTER TABLE users MODIFY COLUMN username VARCHAR(100) NOT NULL UNIQUE;
ALTER TABLE users MODIFY COLUMN email VARCHAR(150) NOT NULL UNIQUE;
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;
ALTER TABLE users MODIFY COLUMN role ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli';
ALTER TABLE users MODIFY COLUMN bahasa VARCHAR(10) NOT NULL DEFAULT 'id';
