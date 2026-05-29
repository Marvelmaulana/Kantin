# Test & Verifikasi Sistem Jam Operasional Kantin

## 📋 Daftar Test

### Test Scenario 1: Mode Manual - Buka
**Setup:**
- Kantin: "Bu Dian"
- Mode: Manual
- Status: Buka

**Expected Result (Pembeli):**
- ✅ Tombol Pesan aktif di detail_menu.php
- ✅ Badge status: "BUKA" (hijau) di kantin_detail.php
- ✅ Checkout bisa diproses

**Expected Result (Penjual):**
- ✅ Dashboard menampilkan: Kantin BUKA
- ✅ Edit profil: Mode Manual, Status Buka

---

### Test Scenario 2: Mode Manual - Tutup
**Setup:**
- Kantin: "Bu Kom"
- Mode: Manual
- Status: Tutup

**Expected Result (Pembeli):**
- ✅ Tombol Pesan disabled di detail_menu.php
- ✅ Badge status: "TUTUP" (merah) di kantin_detail.php
- ✅ Checkout ditolak dengan pesan: "Bu Kom sedang tutup"

**Expected Result (Penjual):**
- ✅ Dashboard menampilkan: Kantin TUTUP
- ✅ Bisa toggle ke Buka dengan button

---

### Test Scenario 3: Mode Otomatis - Dalam Jam Operasi
**Setup:**
- Kantin: "Darma Wanita"
- Mode: Otomatis
- Jam Buka: 07:00
- Jam Tutup: 15:00
- Waktu Sekarang: 10:30

**Expected Result (Pembeli):**
- ✅ Tombol Pesan aktif
- ✅ Badge status: "BUKA" (hijau)
- ✅ Checkout bisa diproses

---

### Test Scenario 4: Mode Otomatis - Luar Jam Operasi
**Setup:**
- Kantin: "Darma Wanita"
- Mode: Otomatis
- Jam Buka: 07:00
- Jam Tutup: 15:00
- Waktu Sekarang: 16:00 (atau 06:00)

**Expected Result (Pembeli):**
- ✅ Tombol Pesan disabled
- ✅ Badge status: "TUTUP" (merah)
- ✅ Checkout ditolak

---

### Test Scenario 5: Midnight Wrap-Around
**Setup:**
- Kantin: "Kantin Malam"
- Mode: Otomatis
- Jam Buka: 20:00
- Jam Tutup: 08:00
- Waktu Sekarang: 22:30

**Expected Result:**
- ✅ Kantin dianggap BUKA (22:30 >= 20:00)
- ✅ Tombol Pesan aktif
- ✅ Checkout bisa diproses

**Waktu Sekarang: 06:00**
- ✅ Kantin dianggap BUKA (06:00 <= 08:00)
- ✅ Tombol Pesan aktif

**Waktu Sekarang: 10:00**
- ✅ Kantin dianggap TUTUP (10:00 > 08:00 && 10:00 < 20:00)
- ✅ Tombol Pesan disabled

---

### Test Scenario 6: Edge Case - Jam Sama
**Setup:**
- Kantin: "Kantin 24 Jam"
- Mode: Otomatis
- Jam Buka: 12:00
- Jam Tutup: 12:00

**Expected Result:**
- ✅ Kantin dianggap SELALU BUKA
- ✅ Tombol Pesan selalu aktif
- ✅ Checkout bisa diproses kapan saja

---

### Test Scenario 7: Update Jam via Penjual
**Setup:**
- Login sebagai penjual
- Edit profil kantin
- Ubah jam_buka: "08:00" → "09:00"
- Ubah jam_tutup: "15:00" → "16:00"

**Expected Result:**
- ✅ Form menerima input
- ✅ Validasi: Format HH:MM diterima ✅
- ✅ Database update: jam_buka = "09:00:00" ✅
- ✅ Pembeli melihat jam baru di kantin_detail.php

---

### Test Scenario 8: Invalid Time Format via Admin
**Setup:**
- Login sebagai admin
- Tambah kantin baru
- Jam Buka: "25:00" (INVALID)
- Jam Tutup: "15:00"

**Expected Result:**
- ✅ Form menampilkan error: "Format jam buka tidak valid"
- ✅ Kantin tidak dibuat
- ✅ Database tidak terubah

---

### Test Scenario 9: Status Badge Sinkronisasi
**Setup:**
- Login pembeli di halaman detail_menu.php
- Login penjual di halaman edit_profil.php (same device, different tab)

**Step 1: Penjual toggle status Manual → Tutup**
- Expected: Pembeli refresh halaman → Badge berubah TUTUP

**Step 2: Penjual toggle status Manual → Buka**
- Expected: Pembeli refresh halaman → Badge berubah BUKA

---

### Test Scenario 10: Checkout Hard Stop
**Setup:**
- Pembeli tambah item ke keranjang (dari kantin terbuka)
- Penjual toggle kantin ke TUTUP
- Pembeli checkout

**Expected Result:**
- ✅ Proses checkout ditolak
- ✅ Error message: "Kantin sedang tutup"
- ✅ Pesanan tidak dibuat
- ✅ Stok tidak berkurang

---

## 📊 Checklist Implementasi

### Frontend (Pembeli)
- [x] kantin_detail.php menampilkan badge status
- [x] detail_menu.php menampilkan jam dan disable tombol jika tutup
- [x] dashboard.php menampilkan kantin status
- [x] checkout.php validasi sebelum submit

### Frontend (Penjual)
- [x] edit_profil.php input jam_buka dan jam_tutup
- [x] dashboard_penjual.php menampilkan status kantin
- [x] api_toggle_status.php untuk manual toggle

### Frontend (Admin)
- [x] tambah_kantin.php input jam_buka dan jam_tutup dengan validasi
- [x] manajemen_kantin.php menampilkan jam operasional
- [x] edit_kantin.php (jika ada)

### Backend
- [x] pembeli_helpers.php: kk_is_kantin_open()
- [x] pembeli_helpers.php: kk_kantin_status_badge()
- [x] pembeli_helpers.php: kk_validate_time_format()
- [x] proses_checkout.php validasi jam buka
- [x] api_update_jam.php validasi dan update
- [x] api_toggle_status.php validasi dan update

### Database
- [x] Tabel kantin: jam_buka, jam_tutup, tipe_operasi, status_buka
- [x] Timezone: Asia/Jakarta

---

## 🐛 Known Issues & Fixes

### Issue #1: Duplikasi Fungsi kk_validate_jam
- **Status**: ✅ FIXED
- **Change**: Hapus duplikasi di pembeli_helpers.php line 556-559

### Issue #2: Admin tambah_kantin.php tidak validasi jam
- **Status**: ✅ FIXED
- **Change**: Add include pembeli_helpers.php, timezone set, dan validasi kk_validate_time_format()

---

## 📝 Notes

### Files Modified
1. `includes/pembeli_helpers.php` - Remove duplikasi fungsi
2. `app/admin/tambah_kantin.php` - Add validation dan helpers

### Files Already Good
1. `app/pembeli/proses_checkout.php`
2. `app/pembeli/kantin_detail.php`
3. `app/pembeli/detail_menu.php`
4. `app/penjual/edit_profil.php`
5. `app/penjual/dashboard_penjual.php`
6. `app/penjual/api_update_jam.php`
7. `app/penjual/api_toggle_status.php`

---

## 🔄 Sinkronisasi Antar Halaman

### Pembeli View
- Dashboard → Kantin_detail → Detail_menu → Checkout
- **Status Update**: Refresh halaman untuk melihat status terbaru

### Penjual View
- Dashboard → Edit Profil / API Toggle
- **Status Update**: Real-time via API (api_toggle_status.php)

### Admin View
- Manajemen Kantin → Tambah/Edit Kantin
- **Status Update**: Halaman refresh setelah perubahan

---

## ✅ Verification Checklist

- [ ] Test Scenario 1: Mode Manual - Buka
- [ ] Test Scenario 2: Mode Manual - Tutup
- [ ] Test Scenario 3: Mode Otomatis - Dalam Jam
- [ ] Test Scenario 4: Mode Otomatis - Luar Jam
- [ ] Test Scenario 5: Midnight Wrap-Around
- [ ] Test Scenario 6: Edge Case - Jam Sama
- [ ] Test Scenario 7: Update Jam via Penjual
- [ ] Test Scenario 8: Invalid Time Format via Admin
- [ ] Test Scenario 9: Status Badge Sinkronisasi
- [ ] Test Scenario 10: Checkout Hard Stop
