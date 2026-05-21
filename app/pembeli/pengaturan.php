<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kk_verify_csrf($_POST['csrf_token'] ?? '')) {
        kk_abort_csrf();
    }
    $bahasa = in_array($_POST['bahasa'] ?? 'id', ['id','en'], true) ? $_POST['bahasa'] : 'id';
    mysqli_query($koneksi, "UPDATE users SET bahasa='$bahasa' WHERE id_user=$id_user");
    // Also set in session for immediate effect
    $_SESSION['lang'] = $bahasa;
    $_SESSION['bahasa'] = $bahasa;
    header("Location: pengaturan.php?success=1");
    exit();
}
$q = mysqli_query($koneksi, "SELECT bahasa FROM users WHERE id_user=$id_user");
$u = mysqli_fetch_assoc($q);
$bahasa = $u['bahasa'] ?? 'id';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($bahasa) ?>">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('nav.settings') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fffdfc}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="pb-28">
<header class="bg-white sticky top-0 z-40 px-5 py-4 border-b border-stone-100">
    <div class="max-w-xl mx-auto flex items-center gap-3">

        <a href="profil.php" class="w-10 h-10 rounded-2xl bg-stone-100 flex items-center justify-center">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>

        <h1 class="headline font-black text-lg text-[#b22204]">
            <?= t('nav.settings') ?>
        </h1>

    </div>
</header>
<main class="max-w-xl mx-auto px-5 py-6 space-y-4">
    <?php if (isset($_GET['success'])): ?><div class="bg-green-50 border border-green-100 text-green-700 rounded-2xl p-3 text-sm font-bold"><i class="fa-solid fa-check-circle mr-2"></i><?= t('lang.saved') ?></div><?php endif; ?>
    <form method="POST" class="bg-white rounded-[2rem] border border-orange-100 p-5 shadow-sm">
        <?= kk_csrf_field() ?>
        <div class="flex items-center gap-3 mb-5">
            <div class="w-11 h-11 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center"><span class="material-symbols-outlined">translate</span></div>
            <div>
                <h2 class="headline font-black"><?= t('lang.title') ?></h2>
                <p class="text-xs text-stone-400"><?= t('settings.lang_desc') ?></p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
                <input type="radio" name="bahasa" value="id" class="hidden peer" <?= $bahasa === 'id' ? 'checked' : '' ?>>
                <div class="rounded-2xl border-2 border-stone-100 peer-checked:border-[#b22204] peer-checked:bg-orange-50 p-4 font-black text-sm flex items-center gap-3">
                    <span class="text-xl">🇮🇩</span> <?= t('lang.indonesian') ?>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="bahasa" value="en" class="hidden peer" <?= $bahasa === 'en' ? 'checked' : '' ?>>
                <div class="rounded-2xl border-2 border-stone-100 peer-checked:border-[#b22204] peer-checked:bg-orange-50 p-4 font-black text-sm flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span> <?= t('lang.english') ?>
                </div>
            </label>
        </div>
        <button class="mt-5 w-full py-4 rounded-2xl bg-[#b22204] text-white headline font-bold flex items-center justify-center gap-2">
            <i class="fa-solid fa-save"></i> <?= t('action.save') ?>
        </button>
    </form>
</main>
<?php $current_page = 'profile'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
