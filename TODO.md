# Plan: Ubah Keamanan Login & Daftar ke Hash Password

## Informasi yang Dikumpulkan
- `proses_daftar.php`: Menyimpan password sebagai **plain text** (teks biasa) langsung ke database.
- `proses.php`: Memverifikasi password dengan perbandingan langsung `$data['password'] == $password`.
- Tidak ada perlindungan jika database bocor — password bisa terbaca langsung.

## Rencana Perubahan

### File yang akan diedit:
1. **`app/auth/proses_daftar.php`**
   - Gunakan `password_hash($password, PASSWORD_DEFAULT)` sebelum menyimpan password ke database.

2. **`app/auth/proses.php`**
   - Ubah pemeriksaan password dari `$data['password'] == $password` menjadi `password_verify($password, $data['password'])`.

### Catatan Penting:
- Password lama di database yang masih plain text tidak akan bisa login setelah perubahan ini (karena tidak bisa diverifikasi dengan `password_verify`).
- Solusi: akun lama perlu didaftarkan ulang atau admin reset password.

## Status:
- [x] Edit `app/auth/proses_daftar.php` — gunakan `password_hash()`
- [x] Edit `app/auth/proses.php` — gunakan `password_verify()`

## Langkah Setelah Edit:
- Test daftar akun baru dan login dengan akun baru untuk memastikan hash berfungsi.

