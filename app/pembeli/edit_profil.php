<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($query);

if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $foto = kk_upload_image('foto_profil', __DIR__ . '/../../uploads/profil');

    $setFoto = $foto ? ", foto_profil='$foto'" : '';
    $update = mysqli_query($koneksi, "UPDATE users SET username='$nama', email='$email' $setFoto WHERE id_user=$id_user");
    if ($update) {
        header("Location: profil.php?success=profil");
        exit();
    }
    $error = mysqli_error($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('profile.edit_profile') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fffdfc}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="text-stone-800 pb-28">
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 px-5 py-4 border-b border-stone-100">
    <div class="max-w-xl mx-auto flex items-center gap-3">
        <button onclick="history.back()" class="w-10 h-10 rounded-2xl bg-stone-100 flex items-center justify-center"><span class="material-symbols-outlined">arrow_back</span></button>
        <h1 class="headline font-black text-lg text-[#b22204]"><?= t('profile.edit_profile') ?></h1>
    </div>
</header>
<main class="max-w-xl mx-auto px-5 py-6">
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-[2rem] border border-orange-100 shadow-sm p-5 space-y-5">
        <?php if (!empty($error)): ?><div class="bg-red-50 text-red-600 rounded-2xl p-3 text-sm font-bold"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <label class="flex items-center gap-4 cursor-pointer">
            <div class="w-24 h-24 rounded-3xl bg-orange-50 overflow-hidden flex items-center justify-center text-[#b22204]">
                <?php if (!empty($user['foto_profil'])): ?>
                <img src="<?= kk_upload_url($user['foto_profil'] ?? '', 'profile') ?>" class="w-full h-full object-cover">
                <?php else: ?>
                <span class="material-symbols-outlined text-4xl">add_a_photo</span>
                <?php endif; ?>
            </div>
            <div>
                <p class="headline font-black"><?= t('profile.photo') ?></p>
                <p class="text-xs text-stone-400 mt-1"><?= t('profile.photo_hint') ?></p>
            </div>
            <input type="file" name="foto_profil" accept="image/*" class="hidden">
        </label>
        <div>
            <label class="text-[11px] font-black uppercase tracking-wider text-stone-400">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="mt-2 w-full bg-stone-100 rounded-2xl border-none px-4 py-3 text-sm" required>
        </div>
        <div>
            <label class="text-[11px] font-black uppercase tracking-wider text-stone-400">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="mt-2 w-full bg-stone-100 rounded-2xl border-none px-4 py-3 text-sm" required>
        </div>
        <button type="submit" name="update" class="w-full h-13 py-4 rounded-2xl bg-gradient-to-r from-[#b22204] to-[#ff6b35] text-white headline font-black"><?= t('profile.save_changes') ?></button>
    </form>
</main>
<?php $current_page = 'profile'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
