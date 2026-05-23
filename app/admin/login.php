<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../../includes/language_helper.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Login - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-200">
        <div class="p-8 bg-gradient-to-br from-orange-500 to-orange-600 text-white">
            <h1 class="text-3xl font-black">Admin Login</h1>
            <p class="text-sm text-orange-100 mt-2">Masuk dengan akun admin sekolah untuk mengelola user, kantin, dan laporan.</p>
        </div>
        <div class="p-8 space-y-6">
            <form action="proses.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Username atau Email</label>
                    <input type="text" name="user_input" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300" placeholder="Username atau Email" />
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-slate-600">Password</label>
                        <a href="../auth/lupa_password.php" class="text-sm font-semibold text-orange-500 hover:underline">Lupa Password?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">lock</span>
                        <input id="adminPassword" type="password" name="password" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 pl-11 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300" placeholder="Password" />
                        <button type="button" id="toggleAdminPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-orange-500">
                            <span id="adminEyeIcon" class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login_btn" class="w-full rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-white hover:bg-orange-600 transition-all">Masuk Admin</button>
            </form>
            <div class="text-sm text-slate-500 text-center">
                <p>Login bersama untuk penjual dan siswa di <a href="../auth/login.php" class="text-orange-500 font-semibold hover:underline">halaman umum</a>.</p>
            </div>
        </div>
    </div>
    <script>
        const toggleAdminPassword = document.getElementById('toggleAdminPassword');
        const adminPassword = document.getElementById('adminPassword');
        const adminEyeIcon = document.getElementById('adminEyeIcon');

        if (toggleAdminPassword && adminPassword && adminEyeIcon) {
            toggleAdminPassword.addEventListener('click', function() {
                if (adminPassword.type === 'password') {
                    adminPassword.type = 'text';
                    adminEyeIcon.textContent = 'visibility_off';
                } else {
                    adminPassword.type = 'password';
                    adminEyeIcon.textContent = 'visibility';
                }
            });
        }
    </script>
</body>
</html>
