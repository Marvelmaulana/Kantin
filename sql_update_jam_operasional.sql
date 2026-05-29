-- ================================================================
-- SQL SCRIPT: Update & Validate Kantin Jam Operasional
-- Ensure semua kolom sudah dalam format yang benar
-- ================================================================

-- 1. Ensure kolom ada dan tipe data benar
ALTER TABLE kantin MODIFY COLUMN jam_buka TIME NOT NULL DEFAULT '07:00:00';
ALTER TABLE kantin MODIFY COLUMN jam_tutup TIME NOT NULL DEFAULT '15:00:00';
ALTER TABLE kantin MODIFY COLUMN status_buka ENUM('Buka','Tutup') NOT NULL DEFAULT 'Buka';
ALTER TABLE kantin MODIFY COLUMN tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'manual';

-- 2. Fix jam yang kosong atau NULL
UPDATE kantin SET jam_buka = '07:00:00' WHERE jam_buka IS NULL OR jam_buka = '' OR jam_buka = '00:00:00';
UPDATE kantin SET jam_tutup = '15:00:00' WHERE jam_tutup IS NULL OR jam_tutup = '' OR jam_tutup = '00:00:00';

-- 3. Fix status_buka yang invalid
UPDATE kantin SET status_buka = 'Buka' WHERE status_buka NOT IN ('Buka', 'Tutup') OR status_buka IS NULL;

-- 4. Fix tipe_operasi yang invalid
UPDATE kantin SET tipe_operasi = 'manual' WHERE tipe_operasi NOT IN ('manual', 'otomatis') OR tipe_operasi IS NULL;

-- 5. Verify data
SELECT 
    id_kantin,
    nama_kantin,
    jam_buka,
    jam_tutup,
    tipe_operasi,
    status_buka,
    updated_at
FROM kantin
ORDER BY id_kantin DESC;

-- 6. Optional: Set semua kantin ke mode manual (aman, tidak berubah otomatis)
-- UPDATE kantin SET tipe_operasi = 'manual', status_buka = 'Buka';

-- 7. Optional: Jika ingin enable mode otomatis, uncomment:
-- UPDATE kantin SET tipe_operasi = 'otomatis' WHERE id_kantin = 1;  -- Specify ID

-- 8. Validate: Check format jam yang tidak sesuai
SELECT id_kantin, jam_buka, jam_tutup 
FROM kantin 
WHERE (
    CHAR_LENGTH(jam_buka) <> 8 OR 
    CHAR_LENGTH(jam_tutup) <> 8 OR
    jam_buka NOT REGEXP '^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$' OR
    jam_tutup NOT REGEXP '^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$'
);

-- 9. Performance: Add index untuk queries yang sering
ALTER TABLE kantin ADD INDEX idx_tipe_status (tipe_operasi, status_buka);

-- 10. Check hasil akhir
SELECT COUNT(*) as total_kantin FROM kantin;
SELECT COUNT(*) as kantin_valid FROM kantin 
WHERE jam_buka REGEXP '^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$' 
  AND jam_tutup REGEXP '^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$'
  AND status_buka IN ('Buka', 'Tutup')
  AND tipe_operasi IN ('manual', 'otomatis');

-- ================================================================
-- SAMPLE DATA untuk testing
-- ================================================================

-- Test 1: Kantin Mode Manual - Buka
INSERT INTO kantin (id_penjual, nama_kantin, jam_buka, jam_tutup, tipe_operasi, status_buka) 
VALUES (1, 'Kantin Test Manual Buka', '07:00:00', '15:00:00', 'manual', 'Buka')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Test 2: Kantin Mode Manual - Tutup
INSERT INTO kantin (id_penjual, nama_kantin, jam_buka, jam_tutup, tipe_operasi, status_buka) 
VALUES (2, 'Kantin Test Manual Tutup', '07:00:00', '15:00:00', 'manual', 'Tutup')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Test 3: Kantin Mode Otomatis
INSERT INTO kantin (id_penjual, nama_kantin, jam_buka, jam_tutup, tipe_operasi, status_buka) 
VALUES (3, 'Kantin Test Otomatis', '07:00:00', '15:00:00', 'otomatis', 'Buka')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Test 4: Kantin Mode Otomatis - Tengah Malam
INSERT INTO kantin (id_penjual, nama_kantin, jam_buka, jam_tutup, tipe_operasi, status_buka) 
VALUES (4, 'Kantin Night Test', '20:00:00', '08:00:00', 'otomatis', 'Buka')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ================================================================
-- ROLLBACK QUERIES (jika ada masalah)
-- ================================================================

-- Rollback: Set semua ke default manual buka
-- UPDATE kantin SET tipe_operasi = 'manual', status_buka = 'Buka';

-- Rollback: Reset jam ke default
-- UPDATE kantin SET jam_buka = '07:00:00', jam_tutup = '15:00:00';

-- ================================================================
