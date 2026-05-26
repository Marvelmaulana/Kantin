# 🚀 QUICK START GUIDE - SISTEM KANTIN KITA

## ⚡ Persiapan (5 Menit)

### 1. Cek Database Columns
```sql
-- Buka phpMyAdmin atau command line MySQL
-- Pastikan tabel `users` memiliki kolom:
-- - role (ENUM: admin, penjual, pembeli) ✓
-- - tipe_pengguna (ENUM: siswa, guru) ✓
-- - kelas (ENUM: 10, 11, 12) ✓

DESCRIBE users;
-- Atau
SHOW COLUMNS FROM users;
```

**Expected Output:**
```
| Field              | Type                           |
|-------------------|--------------------------------|
| id_user            | int(10) unsigned auto_increment|
| username           | varchar(100)                  |
| email              | varchar(150)                  |
| password           | varchar(255)                  |
| role               | enum('admin','penjual','pembeli')|
| tipe_pengguna      | enum('siswa','guru')          |
| kelas              | enum('10','11','12')          |
| ... (lainnya)      | ...                           |
```

### 2. File Structure Check
```
kantin/
├── includes/
│   └── student_helpers.php ✓ (pastikan ada)
├── pages/
│   └── syarat_ketentuan.php ✓ (pastikan ada)
└── app/admin/
    ├── manajemen_siswa.php ✓
    ├── proses_naikkan_kelas.php ✓
    ├── proses_naikkan_semua_kelas.php ✓
    ├── proses_hapus_siswa_kelas_12.php ✓
    └── syarat_ketentuan.php ✓
```

### 3. Set Permissions (Linux/Mac)
```bash
cd /path/to/kantin
chmod 755 pages/
chmod 644 pages/*.php

# Atau jika error saat simpan T&K:
chmod 777 pages/
```

---

## 🎯 Step-by-Step Implementation

### STEP 1: Test Registrasi (5 menit)

1. **Buka halaman registrasi**
   ```
   http://localhost/kantin/app/auth/daftar.php
   ```

2. **Daftar User 1: Siswa Kelas 10**
   - Tipe: Siswa
   - Kelas: Kelas X (10)
   - Username: siswa_kelas10
   - Email: siswa10@gmail.com
   - Password: SiswaKelas10@123

3. **Daftar User 2: Siswa Kelas 11**
   - Tipe: Siswa
   - Kelas: Kelas XI (11)
   - Username: siswa_kelas11
   - Email: siswa11@gmail.com
   - Password: SiswaKelas11@123

4. **Daftar User 3: Guru**
   - Tipe: Guru
   - Username: guru_adi
   - Email: guru_adi@gmail.com
   - Password: GuruAdi@12345

5. **Verify di Database**
   ```sql
   SELECT id_user, username, tipe_pengguna, kelas FROM users WHERE tipe_pengguna IN ('siswa', 'guru');
   ```

---

### STEP 2: Test Profile (3 menit)

1. **Login sebagai siswa_kelas10**
   ```
   Username: siswa_kelas10
   Password: SiswaKelas10@123
   ```

2. **Buka Profile**
   ```
   http://localhost/kantin/app/pembeli/profil.php
   ```
   
3. **Verify:**
   - ✅ Nama user tampil: siswa_kelas10
   - ✅ Badge Kelas tampil: "📚 Kelas X"
   - ✅ Email tampil: siswa10@gmail.com

4. **Klik "Edit Profil"**
   ```
   http://localhost/kantin/app/pembeli/edit_profil.php
   ```

5. **Test Ubah Kelas:**
   - Ubah dari Kelas X → Kelas XI
   - Klik "Simpan Perubahan"
   - Verify kelas berubah di profil

6. **Test dengan Guru:**
   - Logout dan login sebagai guru_adi
   - Buka profil
   - ✅ Tidak ada badge kelas
   - Edit profil: tidak ada field kelas

---

### STEP 3: Test Admin Manajemen (5 menit)

1. **Login sebagai Admin**
   ```
   (Gunakan akun admin yang sudah ada)
   ```

2. **Buka Admin Dashboard**
   ```
   http://localhost/kantin/app/admin/dashboard_admin.php
   ```

3. **Cari link "Manajemen Siswa"** atau buka langsung:
   ```
   http://localhost/kantin/app/admin/manajemen_siswa.php
   ```

4. **Verify statistik tampil:**
   - Total Siswa: 2
   - Kelas X: 1 siswa
   - Kelas XI: 1 siswa
   - Kelas XII: 0 siswa

5. **Test Filter Kelas:**
   - Pilih "Kelas X" dari dropdown
   - Tabel filter hanya menampilkan siswa_kelas10

6. **Test Naikkan Siswa Individual:**
   - Klik tombol "Naikkan" untuk siswa_kelas10
   - Confirm dialog: "Apakah Anda yakin?"
   - Klik OK
   - Verify: siswa_kelas10 naik ke Kelas XI

---

### STEP 4: Test Kenaikan Otomatis (3 menit)

**Persiapan:** Pastikan ada siswa di tiap kelas
```
- Kelas X: 1 siswa
- Kelas XI: 2 siswa (bisa dari STEP 3)
- Kelas XII: 1 siswa (buat baru atau ubah yang ada)
```

1. **Kembali ke Manajemen Siswa**
   ```
   http://localhost/kantin/app/admin/manajemen_siswa.php
   ```

2. **Klik tombol "Kenaikan Kelas Otomatis"**

3. **Baca Confirmation Dialog:**
   ```
   Apakah Anda yakin? Proses ini akan:
   - Menghapus semua siswa kelas 12
   - Naikkan siswa kelas 11 → 12
   - Naikkan siswa kelas 10 → 11
   ```

4. **Klik OK**

5. **Verify Success Message:**
   ```
   Kenaikan kelas otomatis berhasil!
   - Siswa kelas 12 dihapus: 1
   - Siswa kelas 11 → 12: 2
   - Siswa kelas 10 → 11: 1
   ```

6. **Check di Database:**
   ```sql
   SELECT id_user, username, kelas FROM users WHERE tipe_pengguna='siswa';
   ```
   
   Expected hasil:
   ```
   - siswa_kelas10 → kelas 11
   - siswa_kelas11 → kelas 12
   - Siswa kelas 12 sebelumnya → DIHAPUS
   ```

---

### STEP 5: Test Syarat & Ketentuan (3 menit)

1. **Login sebagai Admin**

2. **Buka Manajemen Siswa**

3. **Klik tombol "Syarat & Ketentuan"**
   ```
   http://localhost/kantin/app/admin/syarat_ketentuan.php
   ```

4. **Edit Konten:**
   - Hapus semua teks lama
   - Paste konten T&K baru:
   ```
   # Syarat dan Ketentuan Kantin Kita
   
   1. Semua pengguna wajib mematuhi peraturan sekolah.
   2. Transaksi harus dilakukan dengan jujur dan transparan.
   3. Admin berhak menghapus akun yang melanggar aturan.
   ```

5. **Klik "Simpan Perubahan"**

6. **Verify Success Message:**
   ```
   ✓ Syarat dan ketentuan berhasil diperbarui
   ```

7. **View Public Page:**
   ```
   http://localhost/kantin/pages/syarat_ketentuan.php
   ```
   - ✅ Tampil konten yang baru disimpan

8. **Test dari Link Registrasi:**
   - Buka registrasi: daftar.php
   - Klik link "Syarat & Ketentuan"
   - ✅ Halaman membuka di tab baru
   - ✅ Konten sama dengan yang disimpan admin

---

## 🔍 Verification Checklist

### Database
- [ ] Kolom `role`, `tipe_pengguna`, `kelas` ada di tabel users
- [ ] Kolom `kelas` bertipe ENUM('10','11','12')
- [ ] Constraint CHECK berjalan dengan baik

### File Structure
- [ ] `includes/student_helpers.php` ada dan bisa di-include
- [ ] `pages/syarat_ketentuan.php` ada
- [ ] Semua file di folder `app/admin/` ada
- [ ] Folder `pages/` memiliki permission 755

### Registrasi
- [ ] Pilihan Siswa/Guru tampil
- [ ] Field Kelas muncul hanya untuk Siswa
- [ ] Validasi form berjalan (client-side)
- [ ] Submit registrasi bekerja (server-side)
- [ ] Database insert berhasil

### Profile
- [ ] Siswa bisa lihat badge Kelas 📚 di profil
- [ ] Guru tidak tampil badge kelas
- [ ] Edit profil untuk Siswa ada field Kelas
- [ ] Edit profil untuk Guru tidak ada field Kelas
- [ ] Update kelas berhasil di database

### Admin Manajemen Siswa
- [ ] Statistik per kelas tampil
- [ ] Filter kelas berjalan
- [ ] Tombol "Naikkan" berjalan
- [ ] Tombol "Hapus" berjalan
- [ ] Kelas updated di database

### Kenaikan Otomatis
- [ ] Tombol "Kenaikan Kelas Otomatis" ada
- [ ] Confirmation dialog menampilkan warning
- [ ] Proses berjalan dengan transaction
- [ ] Siswa kelas 12 dihapus
- [ ] Siswa kelas 11 naik ke 12
- [ ] Siswa kelas 10 naik ke 11
- [ ] Success message menampilkan detail

### T&K
- [ ] Admin bisa edit T&K
- [ ] File tersimpan ke `pages/syarat_ketentuan.txt`
- [ ] Halaman public menampilkan konten
- [ ] Link di registrasi berfungsi
- [ ] Perubahan konten muncul di public page

---

## 🐛 Troubleshooting

### "File not found" Error
```php
// Problem: Include path salah
// Solution: Pastikan path relatif benar
include(__DIR__ . '/../../includes/student_helpers.php');
```

### "Undefined variable: is_siswa"
```php
// Problem: Variable belum didefinisikan
// Solution: Tambah ini di awal file:
$is_siswa = isset($user['tipe_pengguna']) && $user['tipe_pengguna'] === 'siswa';
```

### Kelas tidak muncul di profil
```php
// Debug: Check data di database
SELECT * FROM users WHERE id_user = X;
// Pastikan: tipe_pengguna = 'siswa' DAN kelas NOT NULL
```

### Transaction error saat kenaikan otomatis
```sql
-- Check engine database
SHOW TABLE STATUS WHERE Name='users';
-- Harus: Engine = InnoDB (bukan MyISAM)

-- Fix: Alter table ke InnoDB
ALTER TABLE users ENGINE=InnoDB;
```

### Permission denied saat simpan T&K
```bash
# Cek folder permission
ls -la pages/

# Fix: Ubah permission
chmod 755 pages/
chmod 644 pages/*.php
chmod 644 pages/*.txt
```

---

## 📱 URL Quick Reference

| Halaman | URL |
|---------|-----|
| Registrasi | `/kantin/app/auth/daftar.php` |
| Login | `/kantin/app/auth/login.php` |
| Profile Pembeli | `/kantin/app/pembeli/profil.php` |
| Edit Profile | `/kantin/app/pembeli/edit_profil.php` |
| Admin Dashboard | `/kantin/app/admin/dashboard_admin.php` |
| **Manajemen Siswa** | **`/kantin/app/admin/manajemen_siswa.php`** |
| **Edit T&K (Admin)** | **`/kantin/app/admin/syarat_ketentuan.php`** |
| **Lihat T&K (Public)** | **`/kantin/pages/syarat_ketentuan.php`** |

---

## 💡 Tips & Tricks

### Registrasi Batch Test Users
```
Buat beberapa user dengan script:
- siswa_x10_1, siswa_x10_2, siswa_x10_3
- siswa_x11_1, siswa_x11_2
- siswa_x12_1, siswa_x12_2, siswa_x12_3
```

### Test Role Based Access
```
1. Login sebagai user biasa
2. Coba akses: /kantin/app/admin/manajemen_siswa.php
3. Should redirect ke login (CSRF protection)
```

### Test Database Backup
```bash
# Sebelum test kenaikan otomatis:
mysqldump db_kantin > backup_before_promotion.sql
```

### View Log File
```
Jika ada error registrasi:
- Cek file: kantin/logs/registration_error.log
- Cek file: kantin/logs/registration_success.log
```

---

## ⏱️ Timeline Estimate

| Task | Waktu |
|------|-------|
| Persiapan Database | 5 menit |
| Test Registrasi | 5 menit |
| Test Profile | 3 menit |
| Test Admin Panel | 5 menit |
| Test Kenaikan Otomatis | 3 menit |
| Test T&K | 3 menit |
| **TOTAL** | **~25 menit** |

---

## ✅ Success Criteria

Sistem dapat dianggap **SIAP PRODUCTION** jika:

- ✅ Semua test cases PASS
- ✅ Tidak ada error di browser console
- ✅ Database sudah backup
- ✅ File permission sudah benar
- ✅ Admin sudah familiar dengan interface
- ✅ User siswa bisa registrasi dengan kelas
- ✅ Guru bisa registrasi tanpa kelas
- ✅ Kenaikan otomatis berjalan dengan benar
- ✅ T&K bisa di-edit dan tampil

---

**Selamat! 🎉 Sistem Kantin Kita sudah siap digunakan.**

Jika ada pertanyaan atau error, check dokumentasi lengkap di: `DOKUMENTASI_REVISI_SISTEM.md`
