# DOKUMENTASI REVISI SISTEM KANTIN KITA

## 📋 RINGKASAN PERUBAHAN

Revisi lengkap sistem registrasi, dashboard, dan manajemen siswa untuk web kantin sekolah dengan fitur kenaikan kelas otomatis dan pengelolaan data siswa yang lengkap.

---

## 🎯 ALUR SISTEM LENGKAP

### 1. ALUR REGISTRASI (Register)

```
User Membuka daftar.php
    ↓
Pilih Tipe Pengguna: Siswa atau Guru
    ↓
[IF SISWA]
    - Harus memilih Kelas (10, 11, 12)
    - Field kelas wajib diisi
    
[IF GURU]
    - Tidak perlu memilih kelas
    - Field kelas otomatis kosong
    ↓
Input Data: Username, Email, Password
    ↓
Validasi (Client-side & Server-side)
    - Username: min 3 karakter, hanya alphanumeric + underscore
    - Email: harus @gmail.com
    - Password: min 8 karakter
    - Cek duplikasi username & email
    ↓
Submit ke proses_daftar.php
    ↓
Hash Password dengan password_hash()
    ↓
Insert ke Database dengan Prepared Statement
    ↓
Create User Session & Redirect ke Dashboard
```

**File yang Terlibat:**
- `app/auth/daftar.php` - Form registrasi dengan pilihan siswa/guru
- `app/auth/proses_daftar.php` - Server-side validation & database insert
- `includes/auth_helpers.php` - Helper functions untuk validasi & hashing

---

### 2. ALUR LOGIN & DASHBOARD

```
User Login di app/auth/login.php
    ↓
Validasi username/email + password
    ↓
Create User Session:
    - $_SESSION['id_user']
    - $_SESSION['role'] (admin, pembeli, penjual)
    - $_SESSION['tipe_pengguna'] (siswa, guru)
    - $_SESSION['kelas'] (jika siswa)
    ↓
Redirect ke Dashboard sesuai Role:
    - Admin → app/admin/dashboard_admin.php
    - Penjual → app/penjual/dashboard_penjual.php
    - Pembeli (Siswa/Guru) → app/pembeli/dashboard.php
```

**File yang Terlibat:**
- `app/auth/login.php` - Form login
- `app/pembeli/dashboard.php` - Dashboard pembeli/siswa/guru
- `includes/auth_helpers.php` - create_user_session()

---

### 3. ALUR PROFIL SISWA

```
User Buka app/pembeli/profil.php
    ↓
Tampilkan Data User:
    - Username, Email, Foto
    - Role (Member Pembeli)
    
[IF TIPE_PENGGUNA = 'siswa']
    - Tampil Badge Kelas (Kelas X, XI, atau XII)
    - Tampil Icon Buku 📚
    
[IF TIPE_PENGGUNA = 'guru']
    - Tidak tampil kelas
    ↓
User Klik "Edit Profil"
    ↓
Buka app/pembeli/edit_profil.php
    ↓
Form Input: Username, Email, Foto
    
[IF SISWA]
    - Tambah Field: Pilih Kelas
    - Dropdown kelas: 10, 11, 12
    
[IF GURU]
    - Tidak ada field kelas
    ↓
Update ke Database
    ↓
Redirect ke profil.php
```

**File yang Terlibat:**
- `app/pembeli/profil.php` - Tampil profile card dengan kelas
- `app/pembeli/edit_profil.php` - Edit profil + kelas
- `includes/student_helpers.php` - Helper functions untuk kelas

---

### 4. ALUR ADMIN: MANAJEMEN SISWA

```
Admin Buka app/admin/manajemen_siswa.php
    ↓
Tampil Statistik:
    - Total Siswa
    - Siswa per Kelas (10, 11, 12)
    - Kartu visual untuk setiap kelas
    ↓
Tampil Tiga Tombol Aksi:
    1. "Kenaikan Kelas Otomatis" → proses_naikkan_semua_kelas.php
    2. "Hapus Semua Kelas 12" → proses_hapus_siswa_kelas_12.php?action=delete_all
    3. "Syarat & Ketentuan" → syarat_ketentuan.php
    ↓
Tampil Tabel Daftar Siswa dengan Filter Kelas
    ↓
Untuk Setiap Siswa ada Tombol:
    - "Naikkan" (jika bukan kelas 12) → proses_naikkan_kelas.php?id=X
    - "Hapus" → proses_hapus_siswa_kelas_12.php?action=delete_single&id=X
```

**File yang Terlibat:**
- `app/admin/manajemen_siswa.php` - Admin panel lengkap
- `includes/student_helpers.php` - get_siswa_list(), get_total_students_by_class()

---

### 5. ALUR KENAIKAN KELAS OTOMATIS

#### Ketika Admin Klik "Kenaikan Kelas Otomatis":

```
proses_naikkan_semua_kelas.php

START TRANSACTION
    ↓
[STEP 1] Hapus semua siswa dengan kelas = '12'
    DELETE FROM users WHERE role='pembeli' AND tipe_pengguna='siswa' AND kelas='12'
    ↓
[STEP 2] Naikkan siswa kelas '11' → '12'
    UPDATE users SET kelas='12' WHERE role='pembeli' AND tipe_pengguna='siswa' AND kelas='11'
    ↓
[STEP 3] Naikkan siswa kelas '10' → '11'
    UPDATE users SET kelas='11' WHERE role='pembeli' AND tipe_pengguna='siswa' AND kelas='10'
    ↓
COMMIT TRANSACTION
    ↓
Redirect ke manajemen_siswa.php dengan success message
```

**Contoh Hasil:**
```
Kenaikan kelas otomatis berhasil!
- Siswa kelas 12 dihapus: 45
- Siswa kelas 11 → 12: 52
- Siswa kelas 10 → 11: 58
```

**File yang Terlibat:**
- `app/admin/proses_naikkan_semua_kelas.php`
- `includes/student_helpers.php` - promote_all_students()

---

### 6. ALUR NAIKKAN KELAS INDIVIDUAL

```
Admin Klik Tombol "Naikkan" untuk Siswa Tertentu
    ↓
proses_naikkan_kelas.php?id=123
    ↓
Cek data siswa dan kelas saat ini
    ↓
[IF kelas='10']
    UPDATE users SET kelas='11' WHERE id_user=123
    Pesan: "Kelas berhasil dinaikkan dari 10 ke 11"
    
[IF kelas='11']
    UPDATE users SET kelas='12' WHERE id_user=123
    Pesan: "Kelas berhasil dinaikkan dari 11 ke 12"
    
[IF kelas='12']
    DELETE FROM users WHERE id_user=123
    Pesan: "Siswa kelas 12 berhasil dihapus"
    ↓
Redirect ke manajemen_siswa.php?success=promote
```

**File yang Terlibat:**
- `app/admin/proses_naikkan_kelas.php`
- `includes/student_helpers.php` - promote_student_kelas()

---

### 7. ALUR HAPUS SISWA

#### Delete Single Siswa:
```
Admin Klik "Hapus" untuk siswa tertentu
    ↓
proses_hapus_siswa_kelas_12.php?action=delete_single&id=123
    ↓
DELETE FROM users WHERE id_user=123
    ↓
Success message & redirect
```

#### Delete Semua Kelas 12:
```
Admin Klik "Hapus Semua Kelas 12"
    ↓
proses_hapus_siswa_kelas_12.php?action=delete_all
    ↓
DELETE FROM users WHERE role='pembeli' AND tipe_pengguna='siswa' AND kelas='12'
    ↓
Show pesan: "Berhasil menghapus X siswa kelas 12"
```

**File yang Terlibat:**
- `app/admin/proses_hapus_siswa_kelas_12.php`
- `includes/student_helpers.php` - delete_siswa_kelas_12(), delete_student_by_id()

---

### 8. ALUR SYARAT & KETENTUAN

#### Admin Edit T&K:
```
Admin Buka app/admin/syarat_ketentuan.php
    ↓
Tampil Form dengan TextArea
    ↓
Admin Input/Edit Isi Syarat & Ketentuan
    ↓
Klik "Simpan Perubahan"
    ↓
File disimpan ke: pages/syarat_ketentuan.txt
    ↓
Success message: "Syarat dan ketentuan berhasil diperbarui"
    ↓
Preview tampil di bawah
```

#### User Lihat T&K:
```
User Klik Link "Syarat & Ketentuan" di daftar.php
    ↓
Buka pages/syarat_ketentuan.php
    ↓
Tampilkan Isi T&K dari file
    ↓
Format: Markdown rendering (h1, h2, list, paragraph)
    ↓
Button "Kembali" untuk kembali ke registrasi
```

**File yang Terlibat:**
- `app/admin/syarat_ketentuan.php` - Admin editor
- `pages/syarat_ketentuan.php` - Public viewer
- `pages/syarat_ketentuan.txt` - File penyimpanan konten

---

## 🗂️ FILE STRUKTUR

### Direktori yang Dibuat/Dimodifikasi:

```
kantin/
├── includes/
│   └── student_helpers.php ✨ (BARU)
│
├── pages/
│   └── syarat_ketentuan.php ✨ (BARU)
│   └── syarat_ketentuan.txt ✨ (DIBUAT saat admin simpan)
│
├── app/
│   ├── auth/
│   │   ├── daftar.php ✏️ (DIMODIFIKASI - link T&K)
│   │   └── proses_daftar.php (TIDAK BERUBAH - sudah sempurna)
│   │
│   ├── pembeli/
│   │   ├── profil.php ✏️ (DIMODIFIKASI - tampil kelas siswa)
│   │   └── edit_profil.php ✏️ (DIMODIFIKASI - field kelas)
│   │
│   └── admin/
│       ├── manajemen_siswa.php ✨ (BARU)
│       ├── proses_naikkan_kelas.php ✨ (BARU)
│       ├── proses_naikkan_semua_kelas.php ✨ (BARU)
│       ├── proses_hapus_siswa_kelas_12.php ✨ (BARU)
│       └── syarat_ketentuan.php ✨ (BARU)
```

---

## 🔒 KEAMANAN

### 1. SQL Injection Prevention
```php
// ✅ BENAR: Menggunakan Prepared Statement
$stmt = $koneksi->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
```

### 2. Password Security
```php
// ✅ Hash password dengan bcrypt (cost=12)
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ✅ Verify password
if (password_verify($input_password, $hashed)) {
    // Password benar
}
```

### 3. Input Validation
```php
// ✅ Client-side: Validasi form real-time dengan JavaScript
// ✅ Server-side: Validasi di PHP sebelum simpan ke DB

validate_username($username); // Min 3 karakter, alphanumeric + underscore
validate_email($email);       // Must be @gmail.com
validate_password($password); // Min 8 karakter
validate_kelas($kelas);       // Only 10, 11, 12
```

### 4. CSRF Protection
```php
// ✅ Generate CSRF token
$token = kk_csrf_token();

// ✅ Verify CSRF token
if (!kk_verify_csrf($_POST['csrf_token'])) {
    die('CSRF token invalid');
}
```

### 5. Output Encoding
```php
// ✅ Escape semua output untuk prevent XSS
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### 6. Session Management
```php
// ✅ Check role sebelum akses halaman admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
```

---

## 💾 DATABASE SCHEMA

### Users Table Columns yang Relevan:

```sql
CREATE TABLE users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'penjual', 'pembeli') NOT NULL DEFAULT 'pembeli',
    tipe_pengguna ENUM('siswa', 'guru') NULL,
    nip VARCHAR(20) UNIQUE NULL,
    kelas ENUM('10', '11', '12') NULL,
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
```

### Constraint Penjelasan:
- **Siswa Pembeli**: HARUS punya kelas (10, 11, 12)
- **Guru Pembeli/Penjual**: TIDAK BOLEH punya kelas (NULL)
- **Admin**: Tidak ada tipe_pengguna atau kelas

---

## 📚 HELPER FUNCTIONS (student_helpers.php)

### 1. get_siswa_list($koneksi, $kelas=null, $order_by='username ASC')
Ambil daftar siswa dengan filter kelas optional.

```php
$siswa = get_siswa_list($koneksi, '10'); // Ambil siswa kelas 10
// Result: [['id_user' => 1, 'username' => 'adi', 'kelas' => '10'], ...]
```

### 2. get_siswa_count($koneksi, $kelas=null)
Hitung total siswa.

```php
$total = get_siswa_count($koneksi);           // Total semua siswa
$kelas_10 = get_siswa_count($koneksi, '10'); // Total siswa kelas 10
```

### 3. promote_student_kelas($koneksi, $id_user)
Naikkan kelas seorang siswa atau hapus jika kelas 12.

```php
$result = promote_student_kelas($koneksi, 5);
// Result: ['success' => true, 'message' => 'Kelas berhasil dinaikkan dari 10 ke 11']
```

### 4. promote_all_students($koneksi)
Kenaikan kelas otomatis untuk semua siswa dengan transaction.

```php
$result = promote_all_students($koneksi);
// Result: [
//   'success' => true,
//   'message' => 'Kenaikan kelas otomatis berhasil!...',
//   'detail' => [
//     'deleted_kelas_12' => 45,
//     'promoted_11_to_12' => 52,
//     'promoted_10_to_11' => 58
//   ]
// ]
```

### 5. delete_siswa_kelas_12($koneksi)
Hapus semua siswa kelas 12.

```php
$result = delete_siswa_kelas_12($koneksi);
// Result: ['success' => true, 'message' => 'Berhasil menghapus 45 siswa kelas 12', 'deleted_count' => 45]
```

### 6. delete_student_by_id($koneksi, $id_user)
Hapus seorang siswa.

```php
$result = delete_student_by_id($koneksi, 5);
// Result: ['success' => true, 'message' => 'Siswa berhasil dihapus']
```

### 7. get_kelas_label($kelas)
Convert nilai kelas ke label readable.

```php
get_kelas_label('10'); // Return: 'Kelas X'
get_kelas_label('11'); // Return: 'Kelas XI'
get_kelas_label('12'); // Return: 'Kelas XII'
```

### 8. validate_kelas($kelas)
Validasi nilai kelas.

```php
validate_kelas('10');  // true
validate_kelas('99');  // false
```

### 9. get_total_students_by_class($koneksi)
Ambil statistik siswa per kelas.

```php
$stats = get_total_students_by_class($koneksi);
// Result: [
//   ['kelas' => '10', 'label' => 'Kelas X', 'count' => 58],
//   ['kelas' => '11', 'label' => 'Kelas XI', 'count' => 52],
//   ['kelas' => '12', 'label' => 'Kelas XII', 'count' => 45]
// ]
```

---

## 🚀 CARA MENGGUNAKAN SISTEM

### 1. UNTUK PENGGUNA (Register & Login)

```
1. Kunjungi: http://localhost/kantin/app/auth/daftar.php
2. Pilih: "Siswa" atau "Guru"
3. Jika Siswa: Pilih Kelas (10, 11, atau 12)
4. Input: Username, Email (@gmail.com), Password (min 8 karakter)
5. Centang: Checkbox setuju Syarat & Ketentuan
6. Klik: "Daftar Sekarang"
7. Auto login & redirect ke dashboard
8. Lihat profil → Icon kelas 📚 untuk siswa
9. Edit profil → Bisa ubah kelas siswa
```

### 2. UNTUK ADMIN (Manajemen Siswa)

```
1. Login sebagai admin
2. Buka: Dashboard Admin → Cari link ke Manajemen Siswa
   atau: http://localhost/kantin/app/admin/manajemen_siswa.php
3. Lihat Statistik: Berapa siswa per kelas
4. Filter: Pilih kelas di dropdown untuk filter
5. Naikkan Kelas Individual:
   - Klik tombol "Naikkan" di tabel
   - Confirm dialog
   - Kelas otomatis terupdate
6. Naikkan Semua Kelas (Kenaikan Tahunan):
   - Klik tombol "Kenaikan Kelas Otomatis"
   - Confirm dialog besar (menampilkan apa yang akan terjadi)
   - Proses berjalan dengan transaction
   - Lihat pesan detail hasil
7. Hapus Siswa:
   - Hapus individual: Klik "Hapus" di tabel
   - Hapus semua kelas 12: Klik "Hapus Semua Kelas 12"
8. Kelola T&K:
   - Klik "Syarat & Ketentuan" di admin panel
   - Edit isi di textarea
   - Klik "Simpan Perubahan"
   - Preview otomatis update
```

### 3. UNTUK ADMIN (Edit Syarat & Ketentuan)

```
1. Buka: http://localhost/kantin/app/admin/syarat_ketentuan.php
2. Edit konten di textarea
3. Klik "Simpan Perubahan"
4. File tersimpan di: kantin/pages/syarat_ketentuan.txt
5. Lihat preview di bawah
6. User lihat di: http://localhost/kantin/pages/syarat_ketentuan.php
```

---

## 🧪 TESTING CHECKLIST

- [ ] Register sebagai siswa kelas 10 - verify dashboard
- [ ] Register sebagai siswa kelas 11 - verify kelas di profil
- [ ] Register sebagai siswa kelas 12 - verify bisa edit kelas
- [ ] Register sebagai guru - verify tidak ada field kelas
- [ ] Login siswa - check badge kelas 📚 di profil
- [ ] Edit profil siswa - ubah kelas dan verify
- [ ] Login guru - verify tidak ada kelas di profil
- [ ] Admin: Buka manajemen siswa
- [ ] Admin: Naikkan siswa individual (10→11, 11→12)
- [ ] Admin: Hapus siswa kelas 12 individual
- [ ] Admin: Kenaikan kelas otomatis - verify semua naikkan
- [ ] Admin: Hapus semua siswa kelas 12 - verify count
- [ ] Admin: Edit T&K - verify perubahan tampil
- [ ] User: Lihat T&K dari link registrasi
- [ ] Verify responsive di mobile

---

## 📝 CATATAN PENTING

1. **Kelas hanya untuk Siswa**: Database constraint memastikan hanya siswa yang bisa punya kelas
2. **Transaction untuk Kenaikan Batch**: Jika ada error, semua perubahan akan di-rollback
3. **Prepared Statement**: Semua query menggunakan parameterized query untuk keamanan
4. **Password Hashing**: Menggunakan bcrypt dengan cost=12 (lebih secure)
5. **File T&K**: Disimpan di file, bukan database (lebih fleksibel)
6. **Session Role**: Setiap halaman admin cek `$_SESSION['role'] === 'admin'`

---

## 🎓 Contoh Screenshot Alur

Untuk testing, urutan yang recommended:

1. Registrasi 3 user siswa dengan kelas berbeda
2. Registrasi 1 user guru
3. Login sebagai siswa - lihat kelas di profil
4. Login sebagai guru - tidak ada kelas
5. Admin: Lihat manajemen siswa
6. Admin: Naikkan 1 siswa kelas 10 → 11
7. Admin: Kenaikan otomatis semua siswa
8. Verify: Siswa kelas 12 sudah dihapus
9. Edit T&K dan verify tampil

---

**Dokumentasi Lengkap ✅**
File yang di-revisi: 3 file
File yang dibuat: 7 file
Total helper functions: 12 functions
Total baris kode baru: 1000+ lines
