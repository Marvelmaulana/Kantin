<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// Proteksi Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil data user dari database
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$u = mysqli_fetch_assoc($query_user);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Profil Saya - Kantin Kita</title>
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
        .floating-nav {
           position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            height: 75px; display: flex; justify-content: space-around; align-items: center;
            z-index: 50; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border-radius: 25px; width: 90%; max-width: 450px;
        }
    </style>
</head>
<body class="text-stone-800 pb-32">

<header class="bg-white sticky top-0 z-40 px-6 py-4 flex items-center justify-between shadow-sm">
    <h1 class="text-lg font-extrabold font-headline italic uppercase tracking-tighter text-primary">Profil Saya</h1>
    <button class="material-symbols-outlined text-stone-400">settings</button>
</header>

<main class="max-w-xl mx-auto px-6 py-8">
    
    <div class="bg-primary rounded-[2.5rem] p-8 text-white shadow-2xl shadow-red-900/20 mb-10 relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-5">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-3xl flex items-center justify-center border border-white/30">
                <span class="material-symbols-outlined text-4xl">person</span>
            </div>
            <div>
                <h2 class="text-xl font-extrabold font-headline leading-tight"><?= $u['username'] ?></h2>
                <p class="text-white/70 text-xs font-medium"><?= $u['email'] ?></p>
                <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-widest italic">Member <?= ucfirst($u['role']) ?></span>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full"></div>
    </div>

    <div class="space-y-3">
        <h3 class="text-[10px] font-black text-stone-400 uppercase tracking-[0.2em] ml-4 mb-4">Pengaturan Akun</h3>
        
        <button onclick="location.href='edit_profil.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">manage_accounts</span>
                </div>
                <span class="text-sm font-bold">Edit Profil</span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <button onclick="location.href='ubah_password.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-stone-50 rounded-xl flex items-center justify-center text-stone-400">
                    <span class="material-symbols-outlined text-xl">lock</span>
                </div>
                <span class="text-sm font-bold">Ubah Password</span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <h3 class="text-[10px] font-black text-stone-400 uppercase tracking-[0.2em] ml-4 mt-8 mb-4">Dukungan</h3>

        <button onclick="location.href='bantuan.php'" class="w-full bg-white p-5 rounded-[1.5rem] flex items-center justify-between border border-stone-50 shadow-sm active:scale-95 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-stone-50 rounded-xl flex items-center justify-center text-stone-400">
                    <span class="material-symbols-outlined text-xl">help_center</span>
                </div>
                <span class="text-sm font-bold">Pusat Bantuan</span>
            </div>
            <span class="material-symbols-outlined text-stone-300">chevron_right</span>
        </button>

        <button onclick="if(confirm('Yakin ingin keluar?')) location.href='../auth/logout.php'" class="w-full bg-red-50 p-5 rounded-[1.5rem] flex items-center justify-between border border-red-100 mt-10 active:scale-95 transition-all">
            <div class="flex items-center gap-4 text-red-600">
                <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-xl">logout</span>
                </div>
                <span class="text-sm font-black uppercase italic">Keluar Akun</span>
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