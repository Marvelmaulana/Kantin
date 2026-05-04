<?php
session_start();
// Aktifkan pelaporan error agar tidak muncul layar putih polos jika ada salah ketik
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../../config/config.php');

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$error = "";
$success = "";

// 2. Proses Ubah Password
if (isset($_POST['update_password'])) {
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    // Ambil password asli dari database
    $query = mysqli_query($koneksi, "SELECT password FROM users WHERE id_user = '$id_user'");
    $data = mysqli_fetch_assoc($query);

    // Validasi
    if (!password_verify($pw_lama, $data['password'])) {
        $error = "Password lama Anda salah!";
    } elseif ($pw_baru !== $konfirmasi) {
        $error = "Konfirmasi password baru tidak cocok!";
    } elseif (strlen($pw_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } else {
        // Jika semua oke, Hash password baru dan Update
        $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $update = mysqli_query($koneksi, "UPDATE users SET password = '$pw_hash' WHERE id_user = '$id_user'");
        
        if ($update) {
            $success = "Password berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fff8f6] text-stone-800 antialiased min-h-screen">

    <header class="p-6 flex items-center gap-4 bg-white shadow-sm">
        <button onclick="window.location.href='profil.php'" class="material-symbols-outlined p-2 hover:bg-stone-100 rounded-full transition-all">arrow_back</button>
        <h1 class="font-headline font-bold text-lg text-orange-800">Ubah Keamanan</h1>
    </header>

    <main class="max-w-md mx-auto p-6 mt-4">
        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-orange-900/5 border border-orange-50">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl">lock_reset</span>
                </div>
                <h2 class="text-xl font-black font-headline">Ganti Password</h2>
                <p class="text-sm text-stone-400 mt-1">Pastikan password baru Anda sulit ditebak.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-sm">error</span> <?= $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-sm">check_circle</span> <?= $success; ?>
                </div>
                <script>setTimeout(() => { window.location.href='profil.php'; }, 2000);</script>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-widest text-stone-400 ml-2 mb-2 block">Password Lama</label>
                    <input type="password" name="password_lama" required 
                        class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-orange-500 transition-all" placeholder="••••••••">
                </div>

                <hr class="border-stone-50">

                <div>
                    <label class="text-[10px] font-bold uppercase tracking-widest text-stone-400 ml-2 mb-2 block">Password Baru</label>
                    <input type="password" name="password_baru" required 
                        class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-orange-500 transition-all" placeholder="Minimal 6 karakter">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase tracking-widest text-stone-400 ml-2 mb-2 block">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" required 
                        class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-orange-500 transition-all" placeholder="Ulangi password baru">
                </div>

                <button type="submit" name="update_password" 
                    class="w-full bg-orange-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-900/20 active:scale-95 transition-all mt-4 flex items-center justify-center gap-3">
                    <span>Simpan Password Baru</span>
                    <span class="material-symbols-outlined text-sm">vpn_key</span>
                </button>
            </form>
        </div>
    </main>

</body>
</html>