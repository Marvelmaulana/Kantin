<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Register - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be_Vietnam_Pro:wght@400;500;700&display=swap" rel="stylesheet"/>
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
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen flex flex-col">

    <header class="bg-white/80 backdrop-blur-xl flex items-center justify-between px-6 py-4 w-full fixed top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="login.php" class="text-primary hover:opacity-80 transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <span class="text-primary font-extrabold text-xl font-headline tracking-tight">Kantin Kita</span>
        </div>
    </header>

    <main class="flex-grow pt-24 pb-12 px-6 max-w-xl mx-auto w-full">
        <div class="mb-10 relative">
            <h1 class="text-4xl font-extrabold tracking-tight text-on-surface mb-2">Create Account</h1>
            <p class="text-gray-500 font-medium opacity-80">Join our curated culinary community today.</p>
        </div>

        <form action="proses_daftar.php" method="POST" class="space-y-6" onsubmit="return validasiPassword()">
            <div class="space-y-2 group">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1">Full Name</label>
                <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                    <input type="text" name="username" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="Enter your full name" required/>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1">Email</label>
                <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                    <input type="email" name="email" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="name@example.com" required/>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1">Password</label>
                    <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                        <input type="password" name="password" id="pass1" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="••••••••" required/>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 px-1">Confirm Password</label>
                    <div class="relative bg-surface-container-highest rounded-xl overflow-hidden input-focus-bar transition-all">
                        <input type="password" id="pass2" class="w-full bg-transparent border-none focus:ring-0 px-5 py-4 font-medium" placeholder="••••••••" required/>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3 px-1 py-2">
                <input class="mt-1 rounded border-gray-300 text-primary focus:ring-primary/20" id="terms" type="checkbox" required/>
                <label class="text-xs text-gray-500 leading-relaxed" for="terms">
                    By registering, you agree to our <span class="text-primary font-bold">Terms of Service</span> and <span class="text-primary font-bold">Privacy Policy</span>.
                </label>
            </div>

            <button type="submit" name="daftar_btn" class="editorial-gradient w-full py-4 rounded-full text-white font-bold tracking-wide shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all">
                Register
            </button>
        </form>
        <div class="mt-10 pt-8 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-600 font-medium">
                Already have an account? 
                <a class="text-primary font-bold hover:underline ml-1" href="login.php">Login</a>
            </p>
        </div>
    </main>

    <footer class="mt-auto py-8 text-center opacity-40">
        <p class="text-[10px] uppercase tracking-[0.2em] font-bold">© 2024 Kantin Kita Culinary Group</p>
    </footer>

    <script>
        function validasiPassword() {
            const p1 = document.getElementById('pass1').value;
            const p2 = document.getElementById('pass2').value;
            if (p1 !== p2) {
                alert("Password dan Konfirmasi Password tidak sama!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>