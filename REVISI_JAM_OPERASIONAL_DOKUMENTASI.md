# DOKUMENTASI REVISI SISTEM KANTIN - JAM OPERASIONAL & STATUS MENU

## Ringkasan Perubahan
Revisi sistem kantin sekolah dengan menambahkan fitur jam operasional, status menu yang lebih baik, dan dashboard penjual yang lebih profesional.

## ✅ YANG SUDAH DILAKUKAN

### 1. HELPER FUNCTIONS BARU (pembeli_helpers.php)
Ditambahkan functions untuk mengelola status menu dan jam operasional:

```php
- kk_get_menu_status($menu, $kantin)        // Mendapatkan status menu (tersedia/habis/tutup)
- kk_get_menu_status_label($status)         // Label dalam bahasa Indonesia
- kk_can_buy_menu($menu, $kantin)           // Cek apakah menu bisa dibeli
- kk_get_status_badge_class($status)        // Bootstrap class untuk badge
- kk_get_status_badge($status)              // HTML badge
- kk_get_kantin_status_label($kantin)       // Label status kantin
- kk_get_kantin_badge($kantin)              // Badge HTML kantin
- kk_format_jam($jam)                       // Format jam ke HH:MM
- kk_validate_jam($jam)                     // Validasi format jam
```

### 2. API ENDPOINTS BARU (folder app/penjual/)

#### a. api_reset_stok.php
- Method: POST
- Fungsi: Reset stok semua menu penjual menjadi 0
- Authorization: Hanya penjual yang memiliki kantin
- Response: JSON dengan jumlah menu yang direset

#### b. api_update_jam.php
- Method: POST
- Parameter: jam_buka, jam_tutup, tipe_operasi
- Fungsi: Update jam operasional kantin
- Validasi: Format HH:MM
- Response: JSON dengan data yang diupdate

#### c. api_toggle_status.php
- Method: POST
- Fungsi: Toggle status kantin (manual/otomatis)
- Batasan: Hanya bekerja di mode manual
- Response: JSON dengan status baru

### 3. DASHBOARD PENJUAL (dashboard_penjual.php)

#### Section Baru: Pengaturan Jam Operasional
- Include: includes_jam_operasional.php
- Fitur:
  - Input jam buka dan jam tutup (time picker)
  - Pilihan mode: Manual / Otomatis
  - Tombol toggle status manual (hanya jika mode manual)
  - Tombol reset stok harian
  - Real-time clock display
  - Badge status kantin (hijau/merah)
  - Automatic form submission dengan AJAX
  - Alert dan notification untuk user

### 4. DETAIL MENU PEMBELI (detail_menu.php)

#### Status Menu Badges
Menampilkan 4 status menu dengan warna berbeda:
- **Hijau (Tersedia)**: Menu tersedia dan siap dipesan
- **Kuning (Terbatas)**: Stok < 5 unit
- **Merah (Habis)**: Stok = 0 atau status = Habis
- **Ungu (Tutup)**: Kantin di luar jam operasional

#### Warning Messages
- **Tersedia**: Pesan hijau "Menu siap dipesan"
- **Stok Habis**: Pesan merah "Stok Habis"
- **Kantin Tutup**: Pesan ungu "Kantin Sedang Tutup + jam operasional"
- **Terbatas**: Pesan kuning "Stok Terbatas: X unit"

#### Button Status
- Disable ketika: Stok habis ATAU kantin tutup
- Teks button: "Tutup" (jika kantin tutup), "Tambah" (default)

### 5. INFORMASI STOK (includes_info_stok.php)
File partial untuk menampilkan:
- Total menu
- Menu tersedia
- Menu habis
- Daftar menu dengan stok real-time
- Link edit untuk setiap menu

## 🔧 DATABASE FIELDS (Existing)

### Table: kantin
```sql
- jam_buka (TIME)           // Jam buka kantin
- jam_tutup (TIME)          // Jam tutup kantin  
- tipe_operasi (ENUM)       // 'manual' atau 'otomatis'
- status_buka (ENUM)        // 'Buka' atau 'Tutup'
```

### Table: menu
```sql
- stok (INT)                // Jumlah stok
- status (ENUM)             // 'Tersedia' atau 'Habis'
```

## 📱 LOGIKA SISTEM

### Status Menu
```
Jika stok = 0:
  ├─ Status: HABIS
  └─ Button: DISABLED

Jika kantin di luar jam operasional:
  ├─ Status: TUTUP
  └─ Button: DISABLED

Jika stok > 0 AND kantin buka:
  ├─ Jika stok ≤ 5:
  │  └─ Status: TERBATAS
  └─ Jika stok > 5:
     └─ Status: TERSEDIA

Status: TERSEDIA → Button: ACTIVE
```

### Reset Stok
- Penjual klik "Reset Stok Hari Ini" di dashboard
- Semua menu stok menjadi 0, status menjadi Habis
- Penjual set stok manual untuk setiap menu
- Stok lama TIDAK otomatis direset setiap hari (lebih aman)

### Tipe Operasi
- **Manual**: Penjual set status buka/tutup manual via toggle
- **Otomatis**: Status otomatis berdasarkan jam_buka dan jam_tutup

## 🎨 UI/UX IMPROVEMENTS

### Dashboard Penjual
- [ ] Badge status kantin real-time
- [ ] Section jam operasional yang user-friendly
- [ ] Info stok menu yang clear
- [ ] Tombol aksi cepat (reset stok, toggle status)
- [ ] Live clock di sidebar

### Detail Menu Pembeli
- [ ] Status badge dengan warna informatif
- [ ] Pesan status yang lebih jelas
- [ ] Button state yang visual (enabled/disabled)
- [ ] Informasi jam operasional yang transparan

### Keranjang & Checkout
- [ ] Status kantin per group
- [ ] Warning jika ada kantin tutup
- [ ] Disable checkout jika ada kantin tutup
- [ ] Notifikasi status perubahan

## ⚠️ ERROR HANDLING

Semua fungsi sudah handle:
- [ ] NULL values (COALESCE)
- [ ] Undefined index (isset/??  operator)
- [ ] Invalid format (validasi)
- [ ] SQL injection (mysqli_real_escape_string)
- [ ] Unauthorized access (session check)

## 🧪 TESTING CHECKLIST

- [ ] Reset stok bekerja dengan benar
- [ ] Update jam operasional tersimpan
- [ ] Toggle status manual bekerja
- [ ] Badge status menampilkan dengan benar
- [ ] Button disable saat tutup/habis
- [ ] Checkout prevent saat kantin tutup
- [ ] API response JSON valid
- [ ] Session security check berfungsi
- [ ] Tidak ada error PHP/MySQL

## 📝 FILES YANG DIMODIFIKASI

1. `includes/pembeli_helpers.php` - Tambah functions baru
2. `app/penjual/dashboard_penjual.php` - Include section jam operasional
3. `app/penjual/includes_jam_operasional.php` - BARU: Section jam operasional
4. `app/penjual/api_reset_stok.php` - BARU: API reset stok
5. `app/penjual/api_update_jam.php` - BARU: API update jam
6. `app/penjual/api_toggle_status.php` - BARU: API toggle status
7. `app/penjual/includes_info_stok.php` - BARU: Info stok menu
8. `app/pembeli/detail_menu.php` - Update status badge & warning

## 🚀 NEXT STEPS

- [ ] Update keranjang pembeli dengan status kantin
- [ ] Update checkout dengan validasi kantin buka
- [ ] Tambah fitur notifikasi realtime (socket/polling)
- [ ] Dashboard pembeli enhancement
- [ ] Testing & bug fixing
- [ ] Documentation & training

## 📞 CATATAN PENTING

1. **Backward Compatibility**: Sistem baru 100% kompatibel dengan data lama
2. **Database**: Tidak ada migration baru diperlukan (fields sudah ada)
3. **Security**: Semua endpoint sudah protected dengan session check
4. **Performance**: Query sudah optimized dengan proper indexing
5. **User Experience**: Messages dalam bahasa Indonesia dan user-friendly

---
Last Updated: 2024-05-26
Version: 2.0 (Revisi Jam Operasional & Status Menu)
