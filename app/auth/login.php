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
    <title><?= t('auth.login_title') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
</head>
<body class="bg-surface font-body text-on-surface">
    <header class="bg-white/80 backdrop-blur-xl shadow-sm flex items-center justify-between px-6 py-4 w-full fixed top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="text-primary font-extrabold text-xl font-headline"><?= t('buyer.title') ?></span>
        </div>
        <button id="helpBtn" class="text-primary font-bold bg-transparent border-none cursor-pointer"><?= t('auth.help') ?></button>
    </header>

    <main class="min-h-screen flex items-center justify-center pt-20 pb-12 px-4">
        <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 overflow-hidden rounded-[2rem] shadow-2xl bg-white border border-outline-variant/10">
            <div class="hidden md:block relative h-full">
                <div class="absolute inset-0 bg-gradient-to-tr from-primary/40 to-transparent z-10"></div>
                <img class="absolute inset-0 w-full h-full object-cover" src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=2070&auto=format&fit=crop" />
                <div class="absolute bottom-12 left-12 z-20 text-white">
                    <h2 class="text-4xl font-headline font-bold mb-4">Discover the best flavors.</h2>
                    <p class="text-lg opacity-90">Curated kitchens, exceptional tastes.</p>
                </div>
            </div>

            <div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center">
                <div class="mb-10">
                    <h1 class="text-3xl font-headline font-extrabold tracking-tight mb-2"><?= t('auth.welcome_back') ?></h1>
                    <p class="text-gray-500"><?= t('auth.enter_details') ?></p>
                </div>

                <form action="proses.php" method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold uppercase tracking-widest text-gray-500 px-1"><?= t('auth.email_or_username') ?></label>
                        <div class="relative flex items-center bg-surface-container-highest rounded-xl">
                            <span class="material-symbols-outlined absolute left-4 text-gray-400">alternate_email</span>
                            <input type="text" id="userInput" name="user_input" class="w-full bg-transparent border-none focus:ring-0 py-4 pl-12 pr-4" placeholder="<?= t('auth.placeholder_email') ?>" required/>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2">Masukkan username atau email terdaftar untuk login.</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center px-1">
                            <label class="text-[11px] font-bold uppercase tracking-widest text-gray-500"><?= t('auth.password') ?></label>
                            <a class="text-[11px] font-bold text-primary uppercase" href="lupa_password.php"><?= t('auth.forgot_password') ?></a>
                        </div>
                        <div class="relative flex items-center bg-surface-container-highest rounded-xl">
                            <span class="material-symbols-outlined absolute left-4 text-gray-400">lock</span>
                            <input type="password" id="password" name="password" class="w-full bg-transparent border-none focus:ring-0 py-4 pl-12 pr-12" placeholder="••••••••" required/>
                            <button type="button" onclick="toggleVisibility()" class="absolute right-4 text-gray-400 hover:text-primary">
                                <span class="material-symbols-outlined" id="eye-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login_btn" class="w-full bg-primary text-white font-bold py-4 rounded-full shadow-lg hover:opacity-90 active:scale-95 transition-all mt-4">
                        <?= t('auth.login_btn') ?>
                    </button>
                </form>

                <p class="mt-10 text-center text-sm text-gray-500">
                    <?= t('auth.no_account') ?> <a class="text-primary font-bold hover:underline" href="daftar.php"><?= t('auth.register_link') ?></a>
                </p>
                <!-- Link admin disembunyikan agar hanya admin yang mengetahui URL aksesnya -->
                <p class="mt-3 text-center text-xs text-gray-400" style="display:none;">
                    Admin? <a class="text-primary font-bold hover:underline" href="../admin/login.php">Masuk khusus admin</a>
                </p>
            </div>
        </div>
    </main>

    <script>
        function toggleVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }

    </script>
</body>
</html>

<!-- Modal Bantuan Login -->
<div id="helpModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="w-full max-w-2xl mx-4 bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b flex items-start justify-between">
            <div>
                <h3 class="text-xl font-bold">Bantuan Login</h3>
                <p class="text-sm text-gray-500">Petunjuk dan kontak jika mengalami masalah.</p>
            </div>
            <button id="helpCloseTop" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <div class="p-6 space-y-4 text-sm text-gray-700">
            <section>
                <h4 class="font-semibold">Cara Login</h4>
                <ol class="list-decimal ml-5 mt-2">
                    <li>Masukkan <strong>username</strong> atau <strong>email</strong> pada kolom pertama.</li>
                    <li>Masukkan <strong>password</strong> yang terdaftar.</li>
                    <li>Tekan tombol <strong>Login</strong> untuk masuk.</li>
                </ol>
            </section>

            <section>
                <h4 class="font-semibold">Lupa Password</h4>
                <p class="mt-2">Gunakan tautan <em>Lupa Password</em> pada formulir untuk mereset kata sandi melalui email.</p>
            </section>
            <section>
                <h4 class="font-semibold">Kontak Dukungan</h4>
                <p class="mt-2">Jika masih bermasalah, hubungi: <a href="mailto:cukakamu55@gmail.com" class="text-primary"> ADMIN </a></p>
            </section>
        </div>
        <div class="p-4 border-t flex justify-end gap-2">
            <button id="helpClose" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200">Tutup</button>
        </div>
    </div>
</div>

<script>
    // Modal logic
    (function(){
        const helpBtn = document.getElementById('helpBtn');
        const helpModal = document.getElementById('helpModal');
        const helpClose = document.getElementById('helpClose');
        const helpCloseTop = document.getElementById('helpCloseTop');

        function openModal() {
            helpModal.classList.remove('hidden');
            helpModal.classList.add('flex');
        }
        function closeModal() {
            helpModal.classList.remove('flex');
            helpModal.classList.add('hidden');
        }

        if (helpBtn) helpBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
        if (helpClose) helpClose.addEventListener('click', closeModal);
        if (helpCloseTop) helpCloseTop.addEventListener('click', closeModal);
        helpModal && helpModal.addEventListener('click', function(e){ if (e.target === helpModal) closeModal(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
    })();
</script>