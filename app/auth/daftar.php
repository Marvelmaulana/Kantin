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
    <title><?= t('auth.register_title', 'Kantin Kita - Daftar') ?></title>
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
                            tealHover: '#3bb5ac',
                            yellow: '#FFE66D',
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
        /* Smooth Transitions */
        .smooth-transition {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Field Animation */
        .field-animation {
            animation: slideIn 0.4s ease-out forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Conditional Container */
        .conditional-container {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .conditional-container.show {
            max-height: 150px;
            opacity: 1;
        }

        /* Input Focus Effects */
        .input-field {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.15);
        }

        /* Type Buttons */
        .type-btn {
            transition: all 0.3s ease;
        }

        .type-btn.active {
            transform: scale(1.05);
        }

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
<body class="bg-gradient-to-tr from-[#FFF5F0] via-[#F4FBF9] to-[#FFFBF0] dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 min-h-screen text-slate-800 dark:text-slate-100 font-sans smooth-transition">

    <!-- Header -->
    <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl flex items-center justify-between px-6 py-4 w-full sticky top-0 z-50 shadow-sm border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 md:gap-4">
            <a href="login.php" class="p-2 rounded-lg text-slate-400 hover:text-brand-orange dark:hover:text-brand-orange hover:bg-slate-100 dark:hover:bg-slate-800 smooth-transition" title="Kembali">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-lg md:text-xl font-black font-fun text-transparent bg-clip-text bg-gradient-to-r from-brand-orange to-[#FF8A50]">
                    Kantin Kita
                </h1>
                <p class="text-[8px] uppercase tracking-widest font-bold text-slate-400">Pendaftaran Akun</p>
            </div>
        </div>
        <button id="darkModeToggle" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-brand-orange dark:hover:text-brand-orange smooth-transition" title="Ganti Tema">
            <span id="darkIcon" class="material-symbols-outlined text-lg">light_mode</span>
        </button>
    </header>

    <!-- Main Content -->
    <main class="flex-grow py-8 md:py-12 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Title Section -->
            <div class="mb-8 md:mb-12 text-center md:text-left">
                <div class="text-4xl md:text-5xl font-black font-fun mb-3 text-slate-800 dark:text-white">
                    <?= t('auth.create_account', 'Buat Akun Baru') ?>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base">
                    <?= t('auth.join_community', 'Bergabunglah dengan komunitas kami dan mulai pesan makanan!') ?>
                </p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-lg dark:shadow-2xl p-6 md:p-10 border border-slate-200 dark:border-slate-800">
                
                <!-- Error Alert -->
                <div id="errorAlert" class="hidden mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-red-600 dark:text-red-400 text-sm flex items-start gap-3">
                    <span class="material-symbols-outlined text-lg mt-0.5 select-none">error</span>
                    <div class="flex-1">
                        <h4 class="font-bold">Validasi Error</h4>
                        <p id="errorAlertText" class="text-xs mt-0.5"></p>
                    </div>
                    <button type="button" onclick="closeErrorAlert()" class="text-red-400 hover:text-red-600 smooth-transition">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>

                <form id="registerForm" action="proses_daftar.php" method="POST" class="space-y-6" onsubmit="return handleSubmit(event)">
                    
                    <!-- Step 1: User Type Selection -->
                    <div class="field-animation">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-4 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">badge</span> Pilih Tipe Pengguna
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                type="button" 
                                class="type-btn active p-4 rounded-2xl border-2 border-brand-orange bg-brand-orange/10 dark:bg-brand-orange/20 text-slate-800 dark:text-white font-bold flex flex-col items-center gap-2 smooth-transition" 
                                data-type="siswa"
                                onclick="setUserType('siswa')"
                            >
                                <span class="text-3xl">👨‍🎓</span>
                                <span class="text-sm">Siswa</span>
                            </button>
                            <button 
                                type="button" 
                                class="type-btn p-4 rounded-2xl border-2 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-bold flex flex-col items-center gap-2 hover:border-brand-orange hover:text-brand-orange smooth-transition" 
                                data-type="guru"
                                onclick="setUserType('guru')"
                            >
                                <span class="text-3xl">👨‍🏫</span>
                                <span class="text-sm">Guru</span>
                            </button>
                        </div>
                        <input type="hidden" id="userTypeInput" name="user_type" value="siswa">
                    </div>

                    <!-- Step 2: Username -->
                    <div class="field-animation" style="animation-delay: 0.1s">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-3 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">person</span> Username
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-lg select-none pointer-events-none">account_circle</span>
                            <input 
                                type="text" 
                                name="username" 
                                id="usernameInput" 
                                class="input-field w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/20 dark:focus:border-brand-orange" 
                                placeholder="nama_pengguna (tanpa spasi)" 
                                required 
                            />
                            <span id="usernameStatus" class="absolute right-3 top-1/2 -translate-y-1/2 text-lg select-none"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 px-1">Hanya huruf, angka, underscore. Contoh: john_doe, siswa2024</p>
                        <p id="usernameError" class="text-[10px] text-red-500 mt-1 px-1 hidden"></p>
                    </div>

                    <!-- Step 3: Email -->
                    <div class="field-animation" style="animation-delay: 0.15s">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-3 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">mail</span> Email
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-lg select-none pointer-events-none">email</span>
                            <input 
                                type="email" 
                                name="email" 
                                id="emailInput" 
                                class="input-field w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/20 dark:focus:border-brand-orange" 
                                placeholder="nama@gmail.com" 
                                required 
                            />
                            <span id="emailStatus" class="absolute right-3 top-1/2 -translate-y-1/2 text-lg select-none"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 px-1">Email harus menggunakan format @gmail.com</p>
                        <p id="emailError" class="text-[10px] text-red-500 mt-1 px-1 hidden"></p>
                    </div>

                    <!-- Step 4: Class Selection (Only for Siswa) -->
                    <div id="kelasContainer" class="conditional-container show field-animation" style="animation-delay: 0.2s">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-3 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">layers</span> Kelas
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-lg select-none pointer-events-none">school</span>
                            <select 
                                name="kelas" 
                                id="kelasSelect" 
                                required
                                class="input-field w-full pl-11 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-sm focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/20 dark:focus:border-brand-orange"
                            >
                                <option value="">Pilih Kelas Anda</option>
                                <option value="10">Kelas X (10)</option>
                                <option value="11">Kelas XI (11)</option>
                                <option value="12">Kelas XII (12)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Step 5: Password Fields -->
                    <div class="field-animation" style="animation-delay: 0.25s">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-3 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">lock</span> Password
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-lg select-none pointer-events-none">vpn_key</span>
                            <input 
                                type="password" 
                                name="password" 
                                id="passwordInput" 
                                class="input-field w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/20 dark:focus:border-brand-orange" 
                                placeholder="Minimal 8 karakter" 
                                required 
                            />
                            <button 
                                type="button" 
                                onclick="togglePasswordField('passwordInput', 'passwordEyeIcon')" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 smooth-transition hover:bg-slate-100 dark:hover:bg-slate-800/50" 
                                title="Tampilkan Password"
                            >
                                <span id="passwordEyeIcon" class="material-symbols-outlined text-lg select-none">visibility</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 px-1">Gunakan kombinasi huruf besar, kecil, angka, dan simbol</p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="field-animation" style="animation-delay: 0.3s">
                        <label class="block text-xs uppercase tracking-widest font-extrabold text-slate-400 dark:text-slate-500 mb-3 px-1">
                            <span class="material-symbols-outlined text-sm align-middle mr-1 select-none">lock_open</span> Konfirmasi Password
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-lg select-none pointer-events-none">verified_user</span>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                id="confirmPasswordInput" 
                                class="input-field w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/20 dark:focus:border-brand-orange" 
                                placeholder="Ulangi password Anda" 
                                required 
                            />
                            <button 
                                type="button" 
                                onclick="togglePasswordField('confirmPasswordInput', 'confirmEyeIcon')" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 smooth-transition hover:bg-slate-100 dark:hover:bg-slate-800/50" 
                                title="Tampilkan Password"
                            >
                                <span id="confirmEyeIcon" class="material-symbols-outlined text-lg select-none">visibility</span>
                            </button>
                        </div>
                        <p id="passwordMatchStatus" class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 px-1"></p>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="flex items-start gap-3 px-1 py-2 field-animation" style="animation-delay: 0.35s">
                        <input 
                            class="mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-brand-orange focus:ring-brand-orange/20 cursor-pointer smooth-transition" 
                            id="termsCheckbox" 
                            type="checkbox" 
                            required
                        />
                        <label class="text-xs md:text-sm text-slate-600 dark:text-slate-400 leading-relaxed cursor-pointer" for="termsCheckbox">
                            Saya setuju dengan <a href="../../pages/syarat_ketentuan.php" target="_blank" class="text-brand-orange font-bold hover:underline">Syarat & Ketentuan</a> dan <a href="../../pages/syarat_ketentuan.php" target="_blank" class="text-brand-orange font-bold hover:underline">Kebijakan Privasi</a>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-brand-orange to-[#FF8A50] hover:from-brand-orangeHover hover:to-brand-orange text-white font-bold text-sm md:text-base smooth-transition flex items-center justify-center gap-2 shadow-lg shadow-brand-orange/20 hover:shadow-xl hover:shadow-brand-orange/30 hover:scale-[1.01] active:scale-[0.99] mt-8 md:mt-10 disabled:opacity-50 disabled:cursor-not-allowed field-animation"
                        style="animation-delay: 0.4s"
                    >
                        <span id="btnSpinner" class="spinner hidden"></span>
                        <span id="btnText">Daftar Sekarang</span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-8 flex items-center justify-center">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                    </div>
                    <span class="relative px-3 bg-white dark:bg-slate-900 text-xs font-bold text-slate-400 uppercase tracking-widest">atau</span>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Sudah punya akun?
                        <a href="login.php" class="font-extrabold text-brand-orange hover:text-brand-orangeHover hover:underline ml-1 smooth-transition">
                            Masuk di sini
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-[10px] tracking-[0.15em] font-bold uppercase text-slate-400 dark:text-slate-600">
        &copy; <?= date('Y') ?> Kantin Kita Sekolah Digital
    </footer>

    <!-- Scripts -->
    <script>
        // Fungsi toggle password visibility
        window.togglePasswordField = function(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                field.type = 'password';
                icon.textContent = 'visibility';
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            // --- Dark Mode Toggle ---
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkIcon = document.getElementById('darkIcon');

            function applyTheme(isDark) {
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    darkIcon.textContent = 'dark_mode';
                } else {
                    document.documentElement.classList.remove('dark');
                    darkIcon.textContent = 'light_mode';
                }
            }

            const savedTheme = localStorage.getItem('darkMode');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            let isDarkActive = savedTheme === 'true' || (savedTheme === null && systemPrefersDark);
            applyTheme(isDarkActive);

            darkModeToggle.addEventListener('click', () => {
                isDarkActive = !isDarkActive;
                localStorage.setItem('darkMode', isDarkActive);
                applyTheme(isDarkActive);
            });

            // --- User Type Selection ---
            window.setUserType = function(type) {
                const typeButtons = document.querySelectorAll('.type-btn');
                const userTypeInput = document.getElementById('userTypeInput');
                const kelasContainer = document.getElementById('kelasContainer');
                const kelasSelect = document.getElementById('kelasSelect');

                typeButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-brand-orange', 'bg-brand-orange/10', 'dark:bg-brand-orange/20');
                    btn.classList.add('border-slate-200', 'dark:border-slate-700', 'text-slate-500', 'dark:text-slate-400');
                });

                document.querySelector(`[data-type="${type}"]`).classList.add('active', 'border-brand-orange', 'bg-brand-orange/10', 'dark:bg-brand-orange/20', 'text-slate-800', 'dark:text-white');
                document.querySelector(`[data-type="${type}"]`).classList.remove('border-slate-200', 'dark:border-slate-700', 'text-slate-500', 'dark:text-slate-400');

                userTypeInput.value = type;

                if (type === 'siswa') {
                    kelasContainer.classList.add('show');
                    kelasSelect.required = true;
                } else {
                    kelasContainer.classList.remove('show');
                    kelasSelect.required = false;
                    kelasSelect.value = '';
                }
            };

            // --- Realtime Username Validation ---
            const usernameInput = document.getElementById('usernameInput');
            const usernameError = document.getElementById('usernameError');
            const usernameStatus = document.getElementById('usernameStatus');

            usernameInput.addEventListener('input', function() {
                const username = this.value.trim();
                usernameError.classList.add('hidden');
                usernameStatus.textContent = '';

                if (username === '') return;

                // Cek spasi
                if (username.includes(' ')) {
                    usernameError.textContent = '❌ Username tidak boleh mengandung spasi';
                    usernameError.classList.remove('hidden');
                    usernameStatus.textContent = '❌';
                    return;
                }

                // Cek format (hanya huruf, angka, underscore)
                if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                    usernameError.textContent = '❌ Hanya huruf, angka, dan underscore yang diizinkan';
                    usernameError.classList.remove('hidden');
                    usernameStatus.textContent = '❌';
                    return;
                }

                // Cek panjang
                if (username.length < 3) {
                    usernameError.textContent = '❌ Minimal 3 karakter';
                    usernameError.classList.remove('hidden');
                    usernameStatus.textContent = '❌';
                    return;
                }

                // Jika semua validasi passed
                usernameStatus.textContent = '✅';
                usernameError.classList.add('hidden');
            });

            // --- Realtime Email Validation ---
            const emailInput = document.getElementById('emailInput');
            const emailError = document.getElementById('emailError');
            const emailStatus = document.getElementById('emailStatus');

            emailInput.addEventListener('input', function() {
                const email = this.value.trim().toLowerCase();
                emailError.classList.add('hidden');
                emailStatus.textContent = '';

                if (email === '') return;

                // Cek format @gmail.com
                if (!email.endsWith('@gmail.com')) {
                    emailError.textContent = '❌ Email harus menggunakan @gmail.com';
                    emailError.classList.remove('hidden');
                    emailStatus.textContent = '❌';
                    return;
                }

                // Cek format email valid
                if (!/^[a-zA-Z0-9._-]+@gmail\.com$/.test(email)) {
                    emailError.textContent = '❌ Format email tidak valid';
                    emailError.classList.remove('hidden');
                    emailStatus.textContent = '❌';
                    return;
                }

                // Jika semua validasi passed
                emailStatus.textContent = '✅';
                emailError.classList.add('hidden');
            });

            // --- Realtime Password Match Check ---
            const passwordInput = document.getElementById('passwordInput');
            const confirmPasswordInput = document.getElementById('confirmPasswordInput');
            const passwordMatchStatus = document.getElementById('passwordMatchStatus');

            function checkPasswordMatch() {
                if (confirmPasswordInput.value === '') {
                    passwordMatchStatus.textContent = '';
                    return;
                }

                if (passwordInput.value !== confirmPasswordInput.value) {
                    passwordMatchStatus.textContent = '❌ Password tidak cocok';
                    passwordMatchStatus.className = 'text-[10px] text-red-500 mt-1.5 px-1';
                } else {
                    passwordMatchStatus.textContent = '✅ Password cocok';
                    passwordMatchStatus.className = 'text-[10px] text-green-500 mt-1.5 px-1';
                }
            }

            passwordInput.addEventListener('change', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            // --- Form Validation and Submission ---
            const registerForm = document.getElementById('registerForm');
            const errorAlert = document.getElementById('errorAlert');
            const errorAlertText = document.getElementById('errorAlertText');
            const submitBtn = document.getElementById('submitBtn');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnText = document.getElementById('btnText');

            window.closeErrorAlert = function() {
                errorAlert.classList.add('hidden');
            };

            function showErrorAlert(message) {
                errorAlertText.textContent = message;
                errorAlert.classList.remove('hidden');
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            window.handleSubmit = function(event) {
                event.preventDefault();
                errorAlert.classList.add('hidden');

                const username = usernameInput.value.trim();
                const email = emailInput.value.trim().toLowerCase();
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const userType = document.getElementById('userTypeInput').value;
                const kelasSelect = document.getElementById('kelasSelect');
                const termsCheckbox = document.getElementById('termsCheckbox');

                // ✅ Validasi Username (tidak boleh spasi)
                if (!username) {
                    showErrorAlert('Username tidak boleh kosong');
                    return false;
                }
                if (username.includes(' ')) {
                    showErrorAlert('Username tidak boleh mengandung spasi');
                    return false;
                }
                if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                    showErrorAlert('Username hanya boleh berisi huruf, angka, dan underscore');
                    return false;
                }
                if (username.length < 3) {
                    showErrorAlert('Username minimal 3 karakter');
                    return false;
                }

                // ✅ Validasi Email (@gmail.com)
                if (!email) {
                    showErrorAlert('Email tidak boleh kosong');
                    return false;
                }
                if (!email.endsWith('@gmail.com')) {
                    showErrorAlert('Email harus menggunakan format @gmail.com');
                    return false;
                }
                if (!/^[a-zA-Z0-9._-]+@gmail\.com$/.test(email)) {
                    showErrorAlert('Format email tidak valid');
                    return false;
                }

                // ✅ Validasi Kelas (hanya untuk Siswa)
                if (userType === 'siswa') {
                    if (!kelasSelect.value) {
                        showErrorAlert('Silakan pilih kelas Anda');
                        return false;
                    }
                }

                // ✅ Validasi Password
                if (!password) {
                    showErrorAlert('Password tidak boleh kosong');
                    return false;
                }
                if (password.length < 8) {
                    showErrorAlert('Password minimal 8 karakter');
                    return false;
                }

                // ✅ Validasi Confirm Password
                if (!confirmPassword) {
                    showErrorAlert('Konfirmasi password tidak boleh kosong');
                    return false;
                }
                if (password !== confirmPassword) {
                    showErrorAlert('Password tidak cocok');
                    return false;
                }

                // ✅ Validasi Terms
                if (!termsCheckbox.checked) {
                    showErrorAlert('Anda harus menyetujui syarat dan ketentuan');
                    return false;
                }

                // ✅ All validations passed - Submit Form
                submitBtn.disabled = true;
                btnSpinner.classList.remove('hidden');
                btnText.textContent = 'Mendaftar...';

                registerForm.submit();
                return true;
            };
        });
    </script>
</body>
</html>
