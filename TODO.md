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


# GitHub Push Plan (Approved)

## Information Gathered
- Repo: https://github.com/Marvelmaulana/Kantin.git
- Branch: main (ahead by 1 commit)
- Untracked: uploads PNGs (ignore dir)

## Steps:
1. [x] Create .gitignore ignoring uploads/ etc.
2. [ ] Update TODO.md with git steps
3. [ ] git add .gitignore TODO.md
4. [ ] git commit -m "Add .gitignore and update TODO.md"
5. [ ] git push origin main
6. [ ] Create blackboxai/github-push branch if PR needed
7. [ ] Install GitHub CLI and create PR

