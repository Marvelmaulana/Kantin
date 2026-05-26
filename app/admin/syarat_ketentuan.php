<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

kk_ensure_buyer_schema($koneksi);

// Proteksi: hanya admin yang bisa akses
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil atau buat file syarat ketentuan
$sk_file = __DIR__ . '/../../pages/syarat_ketentuan.txt';
$sk_content = '';

if (file_exists($sk_file)) {
    $sk_content = file_get_contents($sk_file);
}

// Proses update
$success_message = '';
if (isset($_POST['update'])) {
    $isi = trim($_POST['isi'] ?? '');
    
    if (strlen($isi) < 50) {
        $error = 'Syarat dan ketentuan minimal 50 karakter';
    } else {
        // Buat direktori jika belum ada
        if (!is_dir(dirname($sk_file))) {
            mkdir(dirname($sk_file), 0755, true);
        }
        
        // Simpan file
        if (file_put_contents($sk_file, $isi) !== false) {
            $success_message = 'Syarat dan ketentuan berhasil diperbarui';
            $sk_content = $isi;
        } else {
            $error = 'Gagal menyimpan file. Cek permission folder.';
        }
    }
}

// Konten default jika belum ada
if (empty($sk_content)) {
    $sk_content = "# Syarat dan Ketentuan Kantin Kita\n\n" .
                 "1. Pengguna harus terdaftar sebagai siswa atau guru untuk menggunakan aplikasi ini.\n\n" .
                 "2. Data pribadi pengguna akan dijaga kerahasiaannya sesuai dengan kebijakan privasi kami.\n\n" .
                 "3. Pengguna bertanggung jawab menjaga keamanan akun pribadi mereka.\n\n" .
                 "4. Aplikasi ini tidak bertanggung jawab atas kesalahan atau kerusakan data pengguna.\n\n" .
                 "5. Pengguna dilarang melakukan transaksi yang mencurigakan atau ilegal.\n\n" .
                 "6. Admin memiliki hak untuk menghapus akun pengguna yang melanggar peraturan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Syarat & Ketentuan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8f7f5; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="text-stone-800">

<header class="bg-white/80 backdrop-blur-xl sticky top-0 z-40 shadow-sm border-b border-stone-200">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center gap-4">
        <a href="manajemen_siswa.php" class="p-2 rounded-lg hover:bg-stone-100 text-stone-400">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="text-xl font-bold text-stone-900">Syarat & Ketentuan</h1>
            <p class="text-xs text-stone-500">Kelola syarat dan ketentuan aplikasi Kantin Kita</p>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">
    
    <!-- Success Alert -->
    <?php if ($success_message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
        <span class="material-symbols-outlined text-green-600 mt-0.5">check_circle</span>
        <div>
            <h3 class="font-bold text-green-900">Berhasil!</h3>
            <p class="text-sm text-green-700"><?= htmlspecialchars($success_message) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Alert -->
    <?php if (isset($error)): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
        <span class="material-symbols-outlined text-red-600 mt-0.5">error</span>
        <div>
            <h3 class="font-bold text-red-900">Error!</h3>
            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Editor -->
    <div class="glass rounded-xl border border-white/50 overflow-hidden">
        <div class="p-6 border-b border-white/50">
            <h2 class="text-lg font-bold">Edit Syarat & Ketentuan</h2>
            <p class="text-sm text-stone-600 mt-1">Konten ini akan ditampilkan kepada semua pengguna saat mendaftar</p>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-bold text-stone-900 mb-2">Isi Syarat & Ketentuan</label>
                <textarea name="isi" class="w-full h-96 p-4 border border-stone-200 rounded-lg focus:border-orange-400 focus:ring-2 focus:ring-orange-200 resize-none" required><?= htmlspecialchars($sk_content) ?></textarea>
                <p class="text-xs text-stone-500 mt-2">Minimal 50 karakter. Format Markdown didukung.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" name="update" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-lg hover:shadow-lg transition-all">
                    <span class="material-symbols-outlined inline-block mr-2 align-middle">save</span>
                    Simpan Perubahan
                </button>
                <a href="manajemen_siswa.php" class="px-6 py-3 bg-stone-100 text-stone-900 font-bold rounded-lg hover:bg-stone-200 transition-all">
                    Batal
                </a>
            </div>
        </form>
    </div>

    <!-- Preview -->
    <div class="mt-8 glass rounded-xl border border-white/50 overflow-hidden">
        <div class="p-6 border-b border-white/50">
            <h2 class="text-lg font-bold">Preview</h2>
        </div>
        <div class="p-6 prose prose-sm max-w-none">
            <?php 
            // Simple markdown-like rendering
            $preview = htmlspecialchars($sk_content);
            $preview = str_replace("\n\n", "</p><p>", $preview);
            $preview = str_replace("\n", "<br>", $preview);
            echo "<p>" . $preview . "</p>";
            ?>
        </div>
    </div>

</main>

<?php include(__DIR__ . '/../../includes/sidebar_admin.php'); ?>

</body>
</html>
