# QUICK START: SISTEM JAM OPERASIONAL KANTIN

## 🚀 MULAI MENGGUNAKAN FITUR BARU

### UNTUK PENJUAL:

#### 1. Setting Jam Operasional
1. Login sebagai penjual
2. Ke **Dashboard Penjual**
3. Scroll ke section **"Jam Operasional"**
4. Atur:
   - **Jam Buka**: Pilih jam mulai (contoh: 07:00)
   - **Jam Tutup**: Pilih jam akhir (contoh: 15:00)
   - **Mode**: Pilih Manual atau Otomatis
5. Klik **"Simpan Pengaturan"**

#### 2. Mode Manual vs Otomatis

**Mode Manual:**
- Anda yang mengontrol status buka/tutup
- Di section jam operasional, ada tombol **"Toggle Status"**
- Klik untuk ubah status Buka ↔ Tutup
- Gunakan saat ada hal darurat (tutup mendadak, dll)

**Mode Otomatis:**
- Sistem otomatis set status berdasarkan jam
- Jika sekarang: `jam_buka ≤ waktu_sekarang ≤ jam_tutup` → Buka
- Selain itu → Tutup
- Tidak perlu manual toggle, sistem yang urus

#### 3. Reset Stok Harian
1. Setiap pagi, ke Dashboard Penjual
2. Section "Jam Operasional" → Tombol **"Reset Stok Hari Ini"**
3. Klik, akan diminta konfirmasi
4. Stok semua menu akan jadi 0
5. Setelah itu, set stok manual untuk setiap menu

**Mengapa Reset Manual?**
- ✅ Lebih aman (tidak ada data hilang otomatis)
- ✅ Fleksibel (bisa reset kapan aja, tidak harus pukul 00:00)
- ✅ Realistis (sesuai operasional kantin sekolah)
- ✅ Kontrol penuh (tahu berapa stok kemarin)

#### 4. Edit Menu & Update Stok
1. Dashboard Penjual → Section **"Informasi Stok"**
2. Lihat daftar menu dengan stok real-time
3. Klik tombol **"Edit"** (icon pensil)
4. Update stok di form edit menu
5. Simpan perubahan

### UNTUK PEMBELI:

#### 1. Lihat Status Menu
Saat membuka detail menu, lihat:
- **Badge Status** di atas nama menu:
  - 🟢 **Hijau "Tersedia"**: Menu bisa dipesan
  - 🟡 **Kuning "Stok Habis"**: Stok = 0
  - 🔴 **Merah "Kantin Tutup"**: Di luar jam operasional
  
#### 2. Pesan Menu
- Menu bisa dipesan hanya jika: **Status = Tersedia**
- Jika kantin tutup atau stok habis → tombol **Disabled (abu-abu)**
- Pesan akan jelas kenapa tidak bisa pesan

#### 3. Keranjang & Checkout
1. Lihat status kantin di keranjang:
   - 🟢 Badge hijau = Kantin buka
   - 🔴 Badge merah = Kantin tutup
2. Jika ada kantin tutup:
   - Jangan bisa pilih item dari kantin tutup
   - Checkbox akan disabled
3. Checkout hanya bisa jika semua kantin buka

## 📋 TESTING CHECKLIST

### Fitur Jam Operasional
- [ ] Set jam buka/tutup berhasil
- [ ] Tampilan jam update di live clock
- [ ] Mode manual toggle berfungsi
- [ ] Mode otomatis status auto-update
- [ ] Badge status kantin tampil dengan benar

### Fitur Reset Stok
- [ ] Tombol reset stok tampil
- [ ] Konfirmasi dialog muncul
- [ ] Stok semua menu berubah jadi 0 setelah reset
- [ ] Status menu berubah jadi Habis

### Tampilan Pembeli
- [ ] Badge status menu tampil dengan warna benar
- [ ] Pesan warning tampil sesuai status
- [ ] Tombol disable saat tutup/habis
- [ ] Badge status kantin di keranjang tampil
- [ ] Checkout bisa di-disable saat ada kantin tutup

### Database & Backend
- [ ] API endpoints respons dengan JSON valid
- [ ] Error handling berfungsi (session, validation)
- [ ] SQL query tidak error (check browser console)
- [ ] Data tersimpan ke database

### Security
- [ ] Session check berfungsi (tidak bisa bypass login)
- [ ] CSRF token valid (form submission aman)
- [ ] SQL injection protection active
- [ ] Hanya penjual yang bisa edit kantin sendiri

## 🎨 TAMPILAN YANG DIHARAPKAN

### Dashboard Penjual
```
┌─────────────────────────────────────────┐
│ JAM OPERASIONAL                    BUKA │
├─────────────────────────────────────────┤
│ [Jam Buka: 07:00]  [Jam Tutup: 15:00]  │
│ [✓ Manual  ○ Otomatis]                 │
│ [Toggle Status] [Reset Stok Harian]    │
│                                         │
│ Waktu Sekarang: 12:34:56                │
│ Status: BUKA | Mode: Manual             │
└─────────────────────────────────────────┘
```

### Detail Menu Pembeli
```
┌─────────────────────────────────────────┐
│ Rendang Spesial      ✓ TERSEDIA        │
│ Rp 25.000                              │
├─────────────────────────────────────────┤
│ ✓ Menu tersedia dan siap dipesan        │
│ 📋 Jam: 07:00 - 15:00                  │
├─────────────────────────────────────────┤
│ [+ -] Jumlah: 1                        │
│ [Tambah Keranjang] [Pesan Sekarang]    │
└─────────────────────────────────────────┘
```

### Keranjang
```
┌─────────────────────────────────────────┐
│ 🍜 KANTIN BENTO PRIMA         🟢 BUKA   │
│   ✓ Pilih Semua (checkbox)              │
│                                         │
│   [x] Rendang          Rp 25.000       │
│   [x] Mie Goreng       Rp 15.000       │
│                                         │
├─────────────────────────────────────────┤
│ 🍲 KANTIN SEHAT INDAH        🔴 TUTUP   │
│   (Tidak bisa pilih - kantin tutup)     │
│                                         │
│   [ ] Gado-gado        Rp 12.000       │
│       (Item disabled)                   │
└─────────────────────────────────────────┘

[Total Rp 40.000] [Lanjut Checkout]
```

## 🔧 TROUBLESHOOTING

### Problem: Jam tidak terupdate
**Solusi:**
- Pastikan server timezone = Asia/Jakarta
- Reload halaman browser
- Cek console untuk error

### Problem: Reset stok tidak berfungsi
**Solusi:**
- Refresh page setelah reset
- Cek apakah user sudah login
- Cek browser console untuk error

### Problem: Button pembeli tidak disabled saat tutup
**Solusi:**
- Pastikan kantin sudah di-set jam buka/tutup
- Refresh page browser
- Clear browser cache

### Problem: API error 401/403
**Solusi:**
- Login kembali
- Pastikan user = penjual kantin tersebut
- Cek CSRF token

### Problem: Stok tidak konsisten
**Solusi:**
- Jangan reload page saat input form
- Tunggu response sukses sebelum pergi halaman lain
- Cek database langsung di PhpMyAdmin

## 📞 BANTUAN

Untuk pertanyaan atau bug report:
1. Cek error di browser console (F12 → Console)
2. Cek error di server logs
3. Test API endpoint dengan Postman/cURL
4. Lakukan verifikasi di database

---
Last Updated: 2024-05-26
Version: 2.0 (Revisi Jam Operasional)
