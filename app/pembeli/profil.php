<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/student_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

// Proteksi Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int) $_SESSION['id_user'];

// Ambil data user dari database
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user LIMIT 1");
$u = $query_user ? mysqli_fetch_assoc($query_user) : null;

if (!$u) {
    header("Location: ../auth/logout.php");
    exit();
}

// Siapkan data kelas untuk siswa
$is_siswa = isset($u['tipe_pengguna']) && $u['tipe_pengguna'] === 'siswa';
$kelas_label = '';
if ($is_siswa && isset($u['kelas'])) {
    $kelas_label = get_kelas_label($u['kelas']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= t('profile.title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "surface": "#fffdfc",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #fffdfc; }
        .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="text-stone-800 pb-32">

<header class="bg-white sticky top-0 z-40 px-6 py-4 flex items-center justify-between shadow-sm">
    <h1 class="text-lg font-extrabold font-headline italic uppercase tracking-tighter text-primary"><?= t('profile.my_profile') ?></h1>
<button type="button" onclick="location.href='pengaturan.php'" class="material-symbols-outlined text-stone-400">settings</button>
</header>

<main class="max-w-xl mx-auto px-6 py-8">
    
    <div class="bg-primary rounded-[2.5rem] p-8 text-white shadow-2xl shadow-red-900/20 mb-10 relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-5">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-3xl flex items-center justify-center border border-white/30 overflow-hidden">
                <?php if (!empty($u['foto_profil'])): ?>
                <img src="<?= kk_upload_url($u['foto_profil'] ?? '', 'profile') ?>" class="w-full h-full object-cover" onerror="this.style.display='none'">
                <?php else: ?>
                <span class="material-symbols-outlined text-4xl">person</span>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-xl font-extrabold font-headline leading-tight"><?= htmlspecialchars($u['username'] ?? '') ?></h2>
                <p class="text-white/70 text-xs font-medium"><?= htmlspecialchars($u['email'] ?? '') ?></p>
                <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-widest italic"><?= t('profile.member') ?> <?= ucfirst($u['role'] ?? 'pembeli') ?></span>
                <?php if ($is_siswa && !empty($kelas_label)): ?>
                <span class="inline-block mt-2 ml-2 px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-widest italic">📚 <?= htmlspecialchars($kelas_label) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full"></div>
    </div>

    <div class="space-y-3">
        <h3 class="text-[10px] font-black text-stone-400 uppercase tracking-[0.2em] ml-4 mb-4"><?= t('profile.account_settings') ?></h3>

        <button type="button" onclick="location.href='edit_profil.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">manage_accounts</span>
                </div>
                <span class="text-sm font-bold"><?= t('profile.edit_profile') ?></span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <button type="button" onclick="location.href='ubah_password.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-stone-50 rounded-xl flex items-center justify-center text-stone-400">
                    <span class="material-symbols-outlined text-xl">lock</span>
                </div>
                <span class="text-sm font-bold"><?= t('profile.change_password') ?></span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <button type="button" onclick="location.href='pengaturan.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-sky-50 rounded-xl flex items-center justify-center text-sky-500">
                    <span class="material-symbols-outlined text-xl">translate</span>
                </div>
                <span class="text-sm font-bold"><?= t('profile.language_settings') ?></span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <h3 class="text-[10px] font-black text-stone-400 uppercase tracking-[0.2em] ml-4 mt-8 mb-4"><?= t('profile.support') ?></h3>

        <button type="button" onclick="location.href='bantuan.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-stone-50 rounded-xl flex items-center justify-center text-stone-400">
                    <span class="material-symbols-outlined text-xl">help_center</span>
                </div>
                <span class="text-sm font-bold"><?= t('profile.help_center') ?></span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <button type="button" onclick="if(confirm('<?= t('profile.logout_confirm') ?>')) location.href='../auth/logout.php'" class="w-full bg-red-50 p-5 rounded-[1.5rem] flex items-center justify-between border border-red-100 mt-10 active:scale-95 transition-all">
            <div class="flex items-center gap-4 text-red-600">
                <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-xl">logout</span>
                </div>
                <span class="text-sm font-black uppercase italic"><?= t('profile.logout') ?></span>
            </div>
        </button>
    </div>
</main>

<?php 
  $current_page = 'profile';
  include(__DIR__ . '/../../includes/navbar.php'); 
?>

</body>
</html>
