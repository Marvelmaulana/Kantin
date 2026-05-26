# 📋 Dokumentasi Revisi Login Pembeli & Penjual

## ✅ Catatan Penting yang Sudah Diterapkan

### 1. **Database Schema**
- ✅ Tambah field `tipe_pengguna ENUM('siswa','guru')` di tabel `users`
- ✅ Tambah field `nip VARCHAR(20) UNIQUE` untuk nomor identitas guru penjual
- ✅ Tambah field `kelas ENUM('10','11','12')` untuk siswa pembeli
- ✅ Update constraint: `chk_user_kelas` untuk validasi data berdasarkan role dan tipe

**Lokasi:** 
- `database_schema.sql` (struktur lengkap)
- `config/config.php` (auto-create saat setup)

---

### 2. **Form Login - UI/UX**

#### **Pembeli (Siswa & Guru)**
- ✅ Pilih Tipe: Radio button "Siswa" atau "Guru"
- ✅ Jika **Siswa**: 
  - Muncul dropdown Kelas (X, XI, XII)
  - Info display: "Anda login sebagai Siswa — Pilih kelas Anda di bawah"
  - Input: Username/Email + Password
- ✅ Jika **Guru**:
  - Dropdown Kelas tersembunyi
  - Info display: "Anda login sebagai Guru — Tidak perlu memilih kelas"
  - Input: Username/Email + Password

#### **Penjual (Guru)**
- ✅ Info display: "Anda login sebagai Guru Penjual — Verifikasi dengan Username + NIP"
- ✅ Input: Username + Nomor Identitas (NIP)
- ✅ **Tanpa password** - verifikasi hanya via username + NIP

**Lokasi:** `app/auth/login.php`

---

### 3. **Proses Login - Backend Logic**

#### **Pembeli Login**
```
1. Ambil user_input (username/email), password, tipe_pengguna, kelas
2. Cari user dengan username/email dan role='pembeli'
3. Verifikasi password
4. Validasi tipe_pengguna sesuai dengan yang di-submit
5. Jika tipe='siswa':
   - Pastikan user punya kelas di database
   - Pastikan kelas yang dipilih sesuai dengan database
6. Set session: id_user, username, role='pembeli', tipe_pengguna, kelas
7. Redirect ke loading.php
```

#### **Penjual Login**
```
1. Ambil penjual_username, penjual_nip
2. Cari user dengan username + nip, role='penjual', tipe_pengguna='guru'
3. Tidak perlu verifikasi password
4. Pastikan user punya id_kantin di tabel kantin
5. Set session: id_user, username, role='penjual', tipe_pengguna='guru'
6. Redirect ke loading.php
```

**Lokasi:** `app/auth/proses.php`

---

### 4. **Data Fields yang Ditampilkan**

#### **Saat Login Pembeli - Siswa:**
- Input: Username/Email
- Input: Password
- Dropdown: Kelas (X, XI, XII)
- Info: "Anda login sebagai **Siswa** — Pilih kelas Anda di bawah"

#### **Saat Login Pembeli - Guru:**
- Input: Username/Email
- Input: Password
- Info: "Anda login sebagai **Guru** — Tidak perlu memilih kelas"

#### **Saat Login Penjual:**
- Input: Username
- Input: Nomor Identitas (NIP)
- Info: "Anda login sebagai **Guru Penjual** — Verifikasi dengan Username + NIP"

---

## 🔧 Langkah Implementasi / Migrasi

### **Untuk Database Baru (Fresh Install):**
1. Database akan otomatis ter-setup dengan struktur terbaru saat diakses pertama kali (dari `config/config.php`)

### **Untuk Database Existing:**
1. **Jalankan migration script:**
   ```
   http://localhost/kantin/migrate_add_user_fields.php
   ```
   Script ini akan:
   - Menambah field `tipe_pengguna`, `nip`, `kelas` jika belum ada
   - Mengupdate pembeli users → `tipe_pengguna = 'siswa'` (default)
   - Mengupdate penjual users → `tipe_pengguna = 'guru'`

2. **Atau manual di phpMyAdmin:**
   ```sql
   ALTER TABLE users ADD COLUMN tipe_pengguna ENUM('siswa','guru') NULL AFTER role;
   ALTER TABLE users ADD COLUMN nip VARCHAR(20) UNIQUE NULL AFTER tipe_pengguna;
   ALTER TABLE users ADD COLUMN kelas ENUM('10','11','12') NULL AFTER nip;
   
   UPDATE users SET tipe_pengguna = 'siswa' WHERE role = 'pembeli' AND tipe_pengguna IS NULL;
   UPDATE users SET tipe_pengguna = 'guru' WHERE role = 'penjual' AND tipe_pengguna IS NULL;
   ```

---

## 📝 Testing Data / Sample Users

### **1. Siswa Pembeli (Kelas 10)**
```sql
INSERT INTO users (username, email, password, role, tipe_pengguna, kelas) 
VALUES (
  'siswa_kelas10',
  'siswa@school.id',
  PASSWORD('password123'),
  'pembeli',
  'siswa',
  '10'
);
```
**Login Form:** 
- Role: Pembeli
- Tipe: Siswa
- Kelas: X (10)
- Username: siswa_kelas10
- Password: password123

### **2. Guru Pembeli**
```sql
INSERT INTO users (username, email, password, role, tipe_pengguna, kelas) 
VALUES (
  'guru_pembeli',
  'guru@school.id',
  PASSWORD('password123'),
  'pembeli',
  'guru',
  NULL
);
```
**Login Form:**
- Role: Pembeli
- Tipe: Guru
- Username: guru_pembeli
- Password: password123

### **3. Guru Penjual**
```sql
INSERT INTO users (username, email, password, role, tipe_pengguna, nip, kelas) 
VALUES (
  'guru_penjual',
  'guru.penjual@school.id',
  PASSWORD('temppassword'),
  'penjual',
  'guru',
  '123456789',
  NULL
);
```
**Login Form:**
- Role: Penjual
- Username: guru_penjual
- NIP: 123456789

---

## ⚠️ Validation Rules

| Role | Tipe | Kelas | NIP | Password | Status |
|------|------|-------|-----|----------|--------|
| pembeli | siswa | ✅ (10/11/12) | ❌ | ✅ | Lengkap |
| pembeli | guru | ❌ (NULL) | ❌ | ✅ | Lengkap |
| penjual | guru | ❌ (NULL) | ✅ | ⚠️ (optional) | Lengkap |
| admin | - | ❌ (NULL) | ❌ | ✅ | Lengkap |

---

## 📂 File yang Sudah Diubah

1. **`database_schema.sql`** - Struktur users table dengan field baru
2. **`config/config.php`** - Fungsi `kk_ensure_core_schema()` dengan field baru
3. **`app/auth/login.php`** - Form login dengan kondisional pembeli/penjual + info display
4. **`app/auth/proses.php`** - Backend logic untuk verifikasi pembeli & penjual
5. **`migrate_add_user_fields.php`** - Script migrasi untuk database existing

---

## ✨ Fitur Yang Sudah Ditambahkan

- ✅ Info display dinamis (berubah sesuai pilihan user)
- ✅ Validasi real-time di frontend (client-side)
- ✅ Validasi ketat di backend (server-side)
- ✅ Kelas dropdown hanya untuk siswa
- ✅ NIP validation untuk penjual (tanpa password)
- ✅ Responsive design dengan Tailwind CSS
- ✅ Dark mode support

---

## 🚀 Next Steps (Opsional)

Jika diperlukan, bisa tambah:
1. Admin login dengan role khusus
2. Validasi NIP format (misalnya: harus 18 digit)
3. Sync data kelas dengan sistem akademik sekolah
4. Export/Import users dari CSV
5. Validasi email domain sekolah

---

**Last Updated:** May 26, 2026  
**Status:** ✅ Fully Implemented
