<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../../includes/language_helper.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= t('auth.register_title') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "surface": "#fff8f6",
                        "on-surface": "#271815",
                        "surface-container-highest": "#f9dcd6",
                    }
                }
            }
        }
    </script>
    <style>
        .editorial-gradient { background: linear-gradient(135deg, #b22204 0%, #d63c1e 100%); }
        .input-focus-bar:focus-within { border-left: 3px solid #b22204; }
    </style>
    <script>
        const passwordNotMatchMsg = '<?= t('auth.password_not_match') ?>';
        const usernameInvalidMsg = 'Nama pengguna hanya boleh berisi huruf, angka, spasi, titik, dan garis bawah.';
    </script>
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen flex flex-col">

    <header class="bg-white/80 backdrop-blur-xl flex items-center justify-between px-6 py-4 w-full fixed top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="login.php" class="text-primary hover:opacity-80 transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <span class="text-primary font-extrabold text-xl font-headline tracking-tight"><?= t('buyer.title') ?></span>
        </div>
    </header>

    <main class="flex-grow pt-24 pb-12 px-6 max-w-xl mx-auto w-full">
        <div class="mb-10 relative">
            <h1 class="text-4xl font-extrabold tracking-tight text-on-surface mb-2"><?= t('auth.create_account') ?></h1>
            <p class="text-gray-500 font-medium opacity-80"><?= t('auth.join_community') ?></p>
        </div>

        <form action="proses_daftar.php" method="POST" class="space-y-6" onsubmit="return validasiForm()">
            <div class="space-y-2 group">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1"><?= t('auth.full_name') ?></label>
                <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                    <input type="text" name="username" id="usernameInput" pattern="[a-zA-Z0-9._\s]+" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="<?= t('auth.placeholder_fullname') ?>" required/>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Huruf, angka, spasi, titik, dan garis bawah saja.</p>
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1"><?= t('auth.email') ?></label>
                <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                    <input type="email" name="email" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="<?= t('auth.placeholder_email_reg') ?>" required/>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1">Kelas</label>
                <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                    <select name="kelas" class="w-full bg-transparent border-none focus:ring-0 py-4 px-5 font-medium" required>
                        <option value="">Pilih kelas</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1"><?= t('auth.password') ?></label>
                    <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                        <input type="password" name="password" id="pass1" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="••••••••" required/>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1"><?= t('auth.confirm_password') ?></label>
                    <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                        <input type="password" id="pass2" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="••••••••" required/>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3 px-1 py-2">
                <input class="mt-1 rounded border-gray-300 text-primary focus:ring-primary/20" id="terms" type="checkbox" required/>
                <label class="text-xs text-gray-500 leading-relaxed" for="terms">
                    <?= t('auth.terms_policy') ?>
                </label>
            </div>

            <button type="submit" name="daftar_btn" class="editorial-gradient w-full py-4 rounded-full text-white font-bold tracking-wide shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all">
                <?= t('auth.register_btn') ?>
            </button>
        </form>
        <div class="mt-10 pt-8 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-600 font-medium">
                <?= t('auth.have_account') ?>
                <a class="text-primary font-bold hover:underline ml-1" href="login.php"><?= t('auth.login_link') ?></a>
            </p>
        </div>
    </main>

    <footer class="mt-auto py-8 text-center opacity-40">
        <p class="text-[10px] uppercase tracking-[0.2em] font-bold"><?= t('auth.copyright') ?></p>
    </footer>

    <script>
        function validasiForm() {
            const username = document.getElementById('usernameInput').value.trim();
            const p1 = document.getElementById('pass1').value;
            const p2 = document.getElementById('pass2').value;
            
            // ✅ Validasi username: hanya huruf, angka, spasi, titik, garis bawah
            const usernameRegex = /^[a-zA-Z0-9._\s]+$/;
            if (!usernameRegex.test(username)) {
                alert(usernameInvalidMsg);
                return false;
            }
            
            // ✅ Validasi password match
            if (p1 !== p2) {
                alert(passwordNotMatchMsg);
                return false;
            }
            return true;
        }
    </script>
</body>
</html>