<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../../includes/language_helper.php');
?>
<!DOCTYPE html>
<html lang="<?= get_current_language(); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= t('admin.login_title', 'Admin Login - Kantin Kita') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fredoka:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#FF6B35',
                            orangeHover: '#e0531f',
                            teal: '#4ECDC4',
                            darkBlue: '#0f172a',
                            purple: '#8B5CF6',
                            pink: '#EC4899'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        fun: ['Fredoka', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        /* Glassmorphism Styles */
        .glass-card {
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.72), rgba(255, 241, 242, 0.72), rgba(255, 247, 237, 0.66));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(244, 63, 94, 0.20);
            box-shadow: 0 24px 60px rgba(244, 63, 94, 0.10), 0 12px 36px rgba(249, 115, 22, 0.10);
        }
        .dark .glass-card {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, 0.72), rgba(76, 5, 25, 0.45), rgba(67, 20, 7, 0.48));
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 14% 16%, rgba(244, 63, 94, .16), transparent 28%),
                radial-gradient(circle at 86% 16%, rgba(249, 115, 22, .18), transparent 28%),
                radial-gradient(circle at 72% 84%, rgba(14, 165, 233, .12), transparent 30%);
            z-index: 0;
        }

        header,
        main,
        footer {
            position: relative;
            z-index: 1;
        }

        .smooth-transition {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Float Animations for SVGs */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }
        @keyframes float-medium {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-22px) rotate(-4deg); }
        }
        .animate-float-1 { animation: float-slow 7s ease-in-out infinite; }
        .animate-float-2 { animation: float-medium 8s ease-in-out infinite 1.5s; }

        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid #fff;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-tr from-[#FFF5F0] via-[#F4FBF9] to-[#FFFBF0] dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 min-h-screen text-slate-800 dark:text-slate-100 font-sans smooth-transition flex flex-col justify-between relative overflow-hidden">

    <!-- Background Decorative SVGs -->
    <div class="absolute -top-12 -left-12 w-64 h-64 bg-brand-orange/5 dark:bg-brand-orange/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-12 -right-12 w-80 h-80 bg-brand-teal/10 dark:bg-brand-teal/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header Section -->
    <header class="w-full py-4 px-6 md:px-12 flex justify-between items-center z-50 relative">
        <div class="flex items-center gap-3">
            <a href="../auth/login.php" class="p-2 rounded-xl bg-white/70 dark:bg-slate-800/80 border border-slate-200/50 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-brand-orange smooth-transition shadow-sm flex items-center justify-center" title="Kembali ke Login Umum">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-lg md:text-xl font-black font-fun tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-brand-orange to-[#FF8A50]">
                    Kantin Kita
                </h1>
                <p class="text-[9px] uppercase tracking-widest font-bold text-slate-400 dark:text-slate-500">Portal Admin</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button id="languageToggle" type="button" class="p-2.5 rounded-xl bg-white/70 dark:bg-slate-800/80 border border-slate-200/50 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-brand-orange dark:hover:text-brand-orange smooth-transition shadow-sm hover:scale-105" title="<?= t('lang.title', 'Ubah Bahasa') ?>" aria-label="<?= t('lang.title', 'Ubah Bahasa') ?>">
                <span class="material-symbols-outlined text-xl">language</span>
                <span id="languageLabel" class="ml-2 hidden sm:inline text-[11px] font-bold uppercase"></span>
            </button>
            <!-- Dark Mode Switcher -->
            <button id="darkModeToggle" class="p-2.5 rounded-xl bg-white/70 dark:bg-slate-800/80 border border-slate-200/50 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-brand-orange dark:hover:text-brand-orange smooth-transition shadow-sm hover:scale-105" title="Ganti Tema">
                <span id="darkIcon" class="material-symbols-outlined block text-xl">light_mode</span>
            </button>
        </div>
    </header>

    <!-- Main Container: Centered Card with fun floaters -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-grow flex items-center justify-center relative z-20">
        
        <!-- Floaters in background -->
        <div class="hidden md:block absolute top-1/4 left-10 animate-float-1 opacity-20 text-6xl select-none">💼</div>
        <div class="hidden md:block absolute bottom-1/4 right-10 animate-float-2 opacity-20 text-6xl select-none">📊</div>

        <div class="w-full max-w-[480px]">
            <div class="glass-card w-full rounded-[2rem] p-6 sm:p-10 smooth-transition relative overflow-hidden">
                <!-- Welcome Title -->
                <div class="text-center mb-8 relative">
                    <div class="text-5xl mb-3 inline-block animate-bounce select-none">👨‍💼</div>
                    <h3 class="text-2xl sm:text-3xl font-black font-fun text-slate-800 dark:text-white">
                        Admin Login
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 leading-relaxed">
                        Masuk dengan akun admin sekolah untuk mengelola data user, kantin, dan laporan transaksi.
                    </p>
                </div>

                <!-- Modern Error Alert Container -->
                <div id="errorAlert" class="hidden mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-red-600 dark:text-red-400 text-sm flex items-start gap-3 animate-pulse">
                    <span class="material-symbols-outlined text-lg mt-0.5 select-none">error</span>
                    <div class="flex-1">
                        <h4 class="font-bold">Gagal Masuk</h4>
                        <p id="errorAlertText" class="text-xs mt-0.5"></p>
                    </div>
                    <button type="button" onclick="closeErrorAlert()" class="text-red-400 hover:text-red-600 smooth-transition">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>

                <!-- Modern Success Alert Container -->
                <div id="successAlert" class="hidden mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-600 dark:text-emerald-400 text-sm flex items-start gap-3">
                    <span class="material-symbols-outlined text-lg mt-0.5 select-none">check_circle</span>
                    <div class="flex-1">
                        <h4 class="font-bold">Berhasil</h4>
                        <p id="successAlertText" class="text-xs mt-0.5">Mengarahkan Anda ke Dashboard Admin...</p>
                    </div>
                </div>

                <!-- Admin Login Form -->
                <form id="adminLoginForm" action="proses.php" method="POST" class="space-y-5">
                    
                    <!-- Username/Email input -->
                    <div>
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-2 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">alternate_email</span> Username atau Email
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg select-none">admin_panel_settings</span>
                            <input type="text" id="userInput" name="user_input" required class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white/70 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 focus:border-brand-orange focus:ring-brand-orange/20 smooth-transition" placeholder="Username / email admin" />
                        </div>
                    </div>

                    <!-- Password input -->
                    <div>
                        <div class="flex justify-between items-center mb-2 px-1">
                            <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500">
                                <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">lock</span> Password
                            </label>
                            <a href="../auth/lupa_password.php" class="text-xs font-bold text-brand-orange hover:text-brand-orangeHover hover:underline smooth-transition">
                                Lupa Password?
                            </a>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg select-none">vpn_key</span>
                            <input id="passwordInput" type="password" name="password" required class="w-full pl-11 pr-12 py-3 rounded-2xl bg-white/70 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 focus:border-brand-orange focus:ring-brand-orange/20 smooth-transition" placeholder="••••••••" />
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 smooth-transition" title="Tampilkan Password">
                                <span id="eyeIcon" class="material-symbols-outlined text-lg select-none">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Hidden input to guarantee $_POST['login_btn'] is detected by backend processes -->
                    <input type="hidden" name="login_btn" value="1">

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-brand-orange to-[#FF8A50] hover:from-brand-orangeHover hover:to-brand-orange text-white font-bold text-sm smooth-transition flex items-center justify-center gap-2 shadow-lg shadow-brand-orange/20 hover:shadow-xl hover:shadow-brand-orange/30 hover:scale-[1.01] active:scale-[0.99] mt-6">
                        <span id="btnSpinner" class="spinner hidden"></span>
                        <span id="btnText">Masuk Admin</span>
                    </button>
                </form>

                <!-- Footer back link -->
                <div class="text-center mt-6 pt-5 border-t border-slate-200/50 dark:border-slate-800/50">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        Bukan admin? Login siswa & penjual di
                        <a href="../auth/login.php" class="text-brand-orange font-bold hover:underline ml-1 smooth-transition">Portal Umum</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Section -->
    <footer class="w-full py-4 text-center opacity-50 text-[10px] tracking-[0.2em] font-extrabold uppercase select-none text-slate-400 dark:text-slate-600 z-10 relative">
        &copy; <?= date('Y') ?> Kantin Kita Sekolah Digital. All Rights Reserved.
    </footer>

    <!-- Form & Interactions Logic -->
    <script>
        // --- 1. Dark Mode + Language Toggle ---
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkIcon = document.getElementById('darkIcon');
        const languageToggle = document.getElementById('languageToggle');
        const languageLabel = document.getElementById('languageLabel');
        const currentLanguage = '<?= get_current_language(); ?>';

        function applyTheme(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
                darkIcon.textContent = 'dark_mode';
            } else {
                document.documentElement.classList.remove('dark');
                darkIcon.textContent = 'light_mode';
            }
        }

        function updateLanguageControl(lang) {
            const nextLang = lang === 'en' ? 'id' : 'en';
            languageToggle.dataset.nextLang = nextLang;
            languageLabel.textContent = nextLang.toUpperCase();
            languageToggle.title = lang === 'en' ? 'Switch to Bahasa Indonesia' : 'Switch to English';
            languageToggle.setAttribute('aria-label', languageToggle.title);
        }

        updateLanguageControl(currentLanguage);

        languageToggle.addEventListener('click', () => {
            const nextLang = languageToggle.dataset.nextLang;
            const url = new URL(window.location.href);
            url.searchParams.set('lang', nextLang);
            window.location.href = url.toString();
        });

        const savedTheme = localStorage.getItem('darkMode');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let isDarkActive = savedTheme === 'true' || (savedTheme === null && systemPrefersDark);
        applyTheme(isDarkActive);

        darkModeToggle.addEventListener('click', () => {
            isDarkActive = !isDarkActive;
            localStorage.setItem('darkMode', isDarkActive);
            applyTheme(isDarkActive);
        });

        // --- 2. Password Visibility Toggle ---
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }

        // --- 3. Form Submission Interceptor (Modern Errors) ---
        const adminLoginForm = document.getElementById('adminLoginForm');
        const errorAlert = document.getElementById('errorAlert');
        const errorAlertText = document.getElementById('errorAlertText');
        const successAlert = document.getElementById('successAlert');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');

        function showErrorAlert(message) {
            errorAlertText.textContent = message;
            errorAlert.classList.remove('hidden');
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeErrorAlert() {
            errorAlert.classList.add('hidden');
        }

        function showSuccessAlert() {
            successAlert.classList.remove('hidden');
        }

        adminLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            closeErrorAlert();

            const userInput = document.getElementById('userInput').value.trim();
            const passwordVal = document.getElementById('passwordInput').value;

            if (!userInput) {
                showErrorAlert('Username atau Email tidak boleh kosong.');
                return;
            }
            if (!passwordVal) {
                showErrorAlert('Password tidak boleh kosong.');
                return;
            }

            // Start Loading State
            submitBtn.disabled = true;
            btnSpinner.classList.remove('hidden');
            btnText.textContent = 'Memverifikasi...';

            try {
                const formData = new FormData(adminLoginForm);
                const response = await fetch(adminLoginForm.action, {
                    method: 'POST',
                    body: formData
                });

                if (response.redirected) {
                    showSuccessAlert();
                    setTimeout(() => {
                        window.location.href = response.url;
                    }, 800);
                    return;
                }

                const responseText = await response.text();
                const alertRegex = /alert\(['"]([^'"]+)['"]\)/;
                const match = responseText.match(alertRegex);

                if (match && match[1]) {
                    showErrorAlert(match[1]);
                    
                    // Reset Button State
                    submitBtn.disabled = false;
                    btnSpinner.classList.add('hidden');
                    btnText.textContent = 'Masuk Admin';
                } else {
                    if (response.url && !response.url.includes('login.php')) {
                        showSuccessAlert();
                        setTimeout(() => {
                            window.location.href = response.url;
                        }, 800);
                    } else {
                        // Fallback
                        showSuccessAlert();
                        setTimeout(() => {
                            adminLoginForm.submit();
                        }, 500);
                    }
                }
            } catch (err) {
                console.error(err);
                showErrorAlert('Terjadi kesalahan jaringan atau server. Silakan coba lagi.');
                
                // Reset Button State
                submitBtn.disabled = false;
                btnSpinner.classList.add('hidden');
                btnText.textContent = 'Masuk Admin';
            }
        });
    </script>
</body>
</html>
