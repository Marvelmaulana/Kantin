<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

$token = mysqli_real_escape_string($koneksi, $_GET['token'] ?? $_POST['token'] ?? '');
$valid = false;
$message = '';

if ($token !== '') {
    $q = mysqli_query($koneksi, "SELECT id_user FROM users WHERE reset_token='$token' AND reset_expired >= NOW() LIMIT 1");
    $valid = $q && mysqli_num_rows($q) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $message = 'Konfirmasi password tidak sama.';
    } else {
        $u = mysqli_fetch_assoc($q);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id_user = (int)$u['id_user'];
        mysqli_query($koneksi, "UPDATE users SET password='$hash', reset_token=NULL, reset_expired=NULL WHERE id_user=$id_user");
        mysqli_query($koneksi, "UPDATE password_resets SET used_at=NOW() WHERE token='$token'");
        header("Location: login.php?reset=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Reset Password</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fff8f6}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center px-5">
<form method="POST" class="w-full max-w-md bg-white rounded-[2rem] border border-orange-100 shadow-xl shadow-red-900/5 p-6 space-y-5">
    <h1 class="headline text-2xl font-black">Reset Password</h1>
    <?php if (!$valid): ?>
    <div class="bg-red-50 text-red-600 rounded-2xl p-3 text-sm font-bold">Token reset tidak valid atau sudah kedaluwarsa.</div>
    <a href="lupa_password.php" class="block text-center bg-[#b22204] text-white headline font-black py-4 rounded-2xl">Minta Token Baru</a>
    <?php else: ?>
    <?php if ($message): ?><div class="bg-red-50 text-red-600 rounded-2xl p-3 text-sm font-bold"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div>
        <label class="text-[11px] font-black uppercase tracking-wider text-stone-400">Password Baru</label>
        <input type="password" name="password" class="mt-2 w-full bg-stone-100 rounded-2xl border-none py-4 px-4 text-sm" required>
    </div>
    <div>
        <label class="text-[11px] font-black uppercase tracking-wider text-stone-400">Konfirmasi Password</label>
        <input type="password" name="confirm" class="mt-2 w-full bg-stone-100 rounded-2xl border-none py-4 px-4 text-sm" required>
    </div>
    <button class="w-full bg-[#b22204] text-white headline font-black py-4 rounded-2xl">Simpan Password</button>
    <?php endif; ?>
</form>
</body>
</html>
