<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Kantin Kita Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Be+Vietnam+Pro:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "tertiary-fixed": "#c2e8ff",
                      "surface-container-highest": "#f9dcd6",
                      "secondary": "#9c4230",
                      "primary-fixed": "#ffdad3",
                      "primary-fixed-dim": "#ffb4a4",
                      "surface-variant": "#f9dcd6",
                      "inverse-on-surface": "#ffede9",
                      "on-surface-variant": "#5b403b",
                      "surface-container-low": "#fff0ee",
                      "surface-container": "#ffe9e5",
                      "on-error": "#ffffff",
                      "on-tertiary-fixed-variant": "#004d67",
                      "secondary-container": "#ff8f77",
                      "surface-container-lowest": "#ffffff",
                      "primary": "#b22204",
                      "tertiary-fixed-dim": "#76d1ff",
                      "secondary-fixed-dim": "#ffb4a4",
                      "on-background": "#271815",
                      "on-secondary": "#ffffff",
                      "on-tertiary": "#ffffff",
                      "outline-variant": "#e3beb6",
                      "on-tertiary-container": "#fbfcff",
                      "on-surface": "#271815",
                      "inverse-primary": "#ffb4a4",
                      "on-tertiary-fixed": "#001e2c",
                      "on-primary": "#ffffff",
                      "error-container": "#ffdad6",
                      "outline": "#8f7069",
                      "on-primary-fixed-variant": "#8d1600",
                      "on-secondary-fixed": "#3e0500",
                      "error": "#ba1a1a",
                      "surface-tint": "#b62506",
                      "on-primary-container": "#fffbff",
                      "tertiary-container": "#007ea7",
                      "primary-container": "#d63c1e",
                      "inverse-surface": "#3e2c29",
                      "on-error-container": "#93000a",
                      "surface": "#fff8f6",
                      "secondary-fixed": "#ffdad3",
                      "on-secondary-fixed-variant": "#7d2c1b",
                      "surface-bright": "#fff8f6",
                      "background": "#fff8f6",
                      "tertiary": "#006385",
                      "on-primary-fixed": "#3e0500",
                      "on-secondary-container": "#762616",
                      "surface-dim": "#f0d4ce",
                      "surface-container-high": "#ffe2dc"
              },
              "borderRadius": {
                      "DEFAULT": "0.25rem",
                      "lg": "0.5rem",
                      "xl": "0.75rem",
                      "full": "9999px"
              },
              "fontFamily": {
                      "headline": ["Plus Jakarta Sans"],
                      "display": ["Plus Jakarta Sans"],
                      "body": ["Be Vietnam Pro"],
                      "label": ["Be Vietnam Pro"]
              }
            },
          },
        }
    </script>
<style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .glass-nav { backdrop-filter: blur(20px); }
        [onclick] { cursor: pointer; }
    </style>
</head>
<body class="bg-background text-on-surface selection:bg-primary-fixed selection:text-on-primary-fixed">
<aside class="h-screen w-64 fixed left-0 top-0 flex flex-col bg-slate-50 dark:bg-slate-900 font-['Plus_Jakarta_Sans'] text-sm tracking-tight z-50">
<div class="flex flex-col h-full py-6 px-4">
<div class="flex items-center gap-3 mb-10 px-2 cursor-pointer" onclick="location.href='admin_dashboard_overview.php'">
<div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-on-primary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">restaurant</span>
</div>
<div>
<h1 class="text-xl font-bold text-sky-600 dark:text-sky-400 tracking-tighter">Kantin Kita</h1>
<p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Management Portal</p>
</div>
</div>
<nav class="flex-1 space-y-1">
<a class="flex items-center gap-3 px-4 py-3 text-sky-600 dark:text-sky-400 font-semibold border-l-4 border-sky-500 bg-sky-50/50 dark:bg-sky-900/20 transition-all duration-200" href="admin_dashboard_overview.php">
<span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="manajemen_user_admin_panel.php">
<span class="material-symbols-outlined">group</span>
                Management User
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="admin_manajemen_user_penjual.php">
<span class="material-symbols-outlined">storefront</span>
                Management Penjual
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="admin_manajemen_menu_pesanan.php">
<span class="material-symbols-outlined">restaurant_menu</span>
                Management Menu
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="daftar_pesanan_admin_panel.php">
<span class="material-symbols-outlined">shopping_cart</span>
                Pesanan
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="moderasi_analitik_admin.php">
<span class="material-symbols-outlined">analytics</span>
                Laporan
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="admin_promo_pengaturan.php">
<span class="material-symbols-outlined">sell</span>
                Promo
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="moderasi_analitik_admin.php">
<span class="material-symbols-outlined">rate_review</span>
                Review
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-sky-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200" href="admin_promo_pengaturan.php">
<span class="material-symbols-outlined">settings</span>
                Pengaturan
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 transition-all duration-200 mt-4 border-t border-slate-200 pt-4" href="../halaman_login.php">
<span class="material-symbols-outlined">logout</span>
                Keluar
            </a>
</nav>
<div class="mt-auto p-4 bg-slate-100/50 dark:bg-slate-800/50 rounded-2xl flex items-center gap-3">
<img alt="Admin User Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" data-alt="portrait of a professional male administrator in a modern office setting with warm soft lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAx2fymaVbeJWS8F2TQ7o37-p6pZ9xpdm1DtiPey6W1bVyjf794_ayld7I0e7cyLx-OgOZMQi2dU_UUObaUfsJWWlGjAjJwSIXn-CbupsA9n77YEscBQhH4r0JEELfvMZUCplr0D0aEvdAVPwzTROosu5rnwedj0IOGuB-mgQ_dZr-698ARFPMFsdg_g29vBiI4Ieu81yw74cR0y1Y0u6Bqp25guS_ciqxC2MS4FAyiTp1nrGBdzKRfnZ2iGEqPvwB4bG18DA-FQcg"/>
<div class="overflow-hidden">
<p class="font-semibold text-slate-900 dark:text-white truncate">Admin Kantin</p>
<p class="text-xs text-slate-500 truncate">Super Admin</p>
</div>
</div>
</div>
</aside>
<header class="fixed top-0 right-0 w-[calc(100%-16rem)] h-16 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-100/50 dark:border-slate-800/50 shadow-sm dark:shadow-none font-['Be_Vietnam_Pro'] text-sm">
<div class="flex items-center justify-between px-8 h-full">
<div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-full px-4 py-1.5 w-96 relative">
<span class="material-symbols-outlined text-slate-400 text-lg">search</span>
<input class="bg-transparent border-none focus:ring-0 text-sm w-full text-slate-600 placeholder:text-slate-400" id="topSearchInput" placeholder="Cari laporan, menu, atau pesanan..." type="text"/>
<div class="hidden absolute top-full left-0 mt-2 w-full bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 py-3 z-[60]" id="searchDropdown">
<div class="px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer text-xs font-semibold text-slate-500">Hasil pencarian cepat...</div>
<div class="h-px bg-slate-100 dark:bg-slate-700 mx-4"></div>
<a class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700" href="admin_manajemen_menu_pesanan.php">
<span class="material-symbols-outlined text-primary text-lg">restaurant_menu</span>
<span>Menu: Salmon Poke Bowl</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700" href="manajemen_user_admin_panel.php">
<span class="material-symbols-outlined text-tertiary text-lg">person</span>
<span>User: Ahmad Subarjo</span>
</a>
</div>
</div>
<div class="flex items-center gap-2">
<div class="relative">
<button class="hover:text-sky-500 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-full p-2 transition-all active:scale-95 duration-200" id="notifBtn">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full border-2 border-white"></span>
</button>
<div class="hidden absolute top-full right-0 mt-4 w-80 bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-[60]" id="notifPanel">
<div class="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
<span class="font-bold text-slate-800 dark:text-white">Notifikasi</span>
<span class="text-[10px] bg-primary-fixed text-primary px-2 py-0.5 rounded-full font-bold">3 BARU</span>
</div>
<div class="max-h-[300px] overflow-y-auto">
<div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700 border-b border-slate-100/50 dark:border-slate-700/50 transition-colors cursor-pointer">
<p class="text-xs font-bold text-slate-800 dark:text-white">Pesanan Baru #KT-88421</p>
<p class="text-[10px] text-slate-500 mt-1">Dian Sastro memesan Salmon Poke Bowl</p>
<p class="text-[10px] text-primary font-bold mt-2">Baru saja</p>
</div>
<div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700 border-b border-slate-100/50 dark:border-slate-700/50 transition-colors cursor-pointer">
<p class="text-xs font-bold text-slate-800 dark:text-white">Verifikasi Penjual</p>
<p class="text-[10px] text-slate-500 mt-1">Bakso Mas Giri menunggu persetujuan admin</p>
<p class="text-[10px] text-slate-400 mt-2">15 menit yang lalu</p>
</div>
</div>
</div>
</div>
<button class="hover:text-sky-500 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-full p-2 transition-all active:scale-95 duration-200">
<span class="material-symbols-outlined">apps</span>
</button>
<div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-2"></div>
<div class="flex items-center gap-3 pl-2">
<span class="font-medium text-slate-700 dark:text-slate-200">Selamat Datang, Admin</span>
<div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-sm">person</span>
</div>
</div>
</div>
</div>
</header>
<main class="ml-64 pt-24 pb-12 px-8 min-h-screen">
<section class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
<div>
<h2 class="text-3xl font-extrabold text-on-surface tracking-tight mb-1">Dashboard Overview</h2>
<p class="text-slate-500 font-medium">Pantau performa operasional Kantin Kita hari ini.</p>
</div>
<div class="flex items-center gap-3">
<div class="bg-surface-container-high px-4 py-2.5 rounded-xl flex items-center gap-3 cursor-pointer hover:bg-surface-container-highest transition-colors group">
<span class="material-symbols-outlined text-tertiary">calendar_today</span>
<span class="text-sm font-semibold text-on-surface">Oct 1, 2023 - Oct 31, 2023</span>
<span class="material-symbols-outlined text-slate-400 group-hover:translate-y-0.5 transition-transform">expand_more</span>
</div>
<button class="bg-primary-container text-on-primary-container px-6 py-2.5 rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
<span class="material-symbols-outlined text-lg">download</span>
                Export PDF
            </button>
</div>
</section>
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
<div class="bg-surface-container-low p-6 rounded-3xl relative overflow-hidden group">
<div class="absolute -right-4 -top-4 w-24 h-24 bg-tertiary/10 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
<div class="flex items-center gap-4 mb-4">
<div class="w-12 h-12 rounded-2xl bg-tertiary-fixed flex items-center justify-center text-tertiary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">group</span>
</div>
<div>
<p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total User</p>
<h3 class="text-2xl font-black text-on-surface">1,240</h3>
</div>
</div>
<div class="flex items-center gap-2 text-xs font-bold text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded-lg">
<span class="material-symbols-outlined text-xs">trending_up</span>
                +12% this month
            </div>
</div>
<div class="bg-surface-container-low p-6 rounded-3xl relative overflow-hidden group">
<div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/10 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
<div class="flex items-center gap-4 mb-4">
<div class="w-12 h-12 rounded-2xl bg-primary-fixed flex items-center justify-center text-primary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">store</span>
</div>
<div>
<p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Penjual</p>
<h3 class="text-2xl font-black text-on-surface">85</h3>
</div>
</div>
<div class="flex items-center gap-2 text-xs font-bold text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded-lg">
<span class="material-symbols-outlined text-xs">trending_up</span>
                +5% this month
            </div>
</div>
<div class="bg-surface-container-low p-6 rounded-3xl relative overflow-hidden group">
<div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
<div class="flex items-center gap-4 mb-4">
<div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">shopping_cart</span>
</div>
<div>
<p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Pesanan Hari Ini</p>
<h3 class="text-2xl font-black text-on-surface">312</h3>
</div>
</div>
<p class="text-xs text-slate-400 font-medium italic">Update 5 menit yang lalu</p>
</div>
<div class="bg-surface-container-low p-6 rounded-3xl relative overflow-hidden group">
<div class="absolute -right-4 -top-4 w-24 h-24 bg-sky-500/10 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
<div class="flex items-center gap-4 mb-4">
<div class="w-12 h-12 rounded-2xl bg-sky-100 flex items-center justify-center text-sky-600">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
</div>
<div>
<p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Pendapatan</p>
<h3 class="text-xl font-black text-on-surface leading-tight">Rp 150.250.000</h3>
</div>
</div>
<div class="h-1 w-full bg-sky-100 rounded-full overflow-hidden">
<div class="h-full bg-sky-500 w-[78%]"></div>
</div>
<p class="text-[10px] text-sky-600 font-bold mt-2">78% DARI TARGET BULANAN</p>
</div>
</section>
<section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<div class="lg:col-span-2 bg-surface-container-low rounded-[2rem] p-8">
<div class="flex items-center justify-between mb-8">
<div>
<h4 class="text-xl font-extrabold text-on-surface">Penjual Mingguan</h4>
<p class="text-sm text-slate-400 font-medium">Statistik volume transaksi 7 hari terakhir</p>
</div>
<select class="bg-surface-container-high border-none rounded-xl text-sm font-bold text-slate-600 focus:ring-primary-container">
<option>Minggu Ini</option>
<option>Minggu Lalu</option>
</select>
</div>
<div class="flex items-end justify-between h-64 gap-2 px-4 border-b border-slate-100">
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[45%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Sen</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[60%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Sel</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-primary-container rounded-t-xl h-[95%] shadow-lg shadow-primary/20"></div>
<span class="text-xs font-bold text-on-surface uppercase">Rab</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[75%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Kam</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[40%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Jum</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[30%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Sab</span>
</div>
<div class="flex-1 flex flex-col items-center gap-4">
<div class="w-full bg-surface-container-highest rounded-t-xl h-[25%] hover:bg-primary transition-all duration-300"></div>
<span class="text-xs font-bold text-slate-400 uppercase">Min</span>
</div>
</div>
</div>
<div class="bg-surface-container-low rounded-[2rem] p-8 flex flex-col">
<h4 class="text-xl font-extrabold text-on-surface mb-2">Kategori Terlaris</h4>
<p class="text-sm text-slate-400 font-medium mb-8">Distribusi pesanan per kategori</p>
<div class="space-y-6 flex-1">
<div class="space-y-2">
<div class="flex justify-between text-sm font-bold text-on-surface">
<span>Makanan Berat</span>
<span>58%</span>
</div>
<div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
<div class="h-full bg-primary-container w-[58%] rounded-full"></div>
</div>
</div>
<div class="space-y-2">
<div class="flex justify-between text-sm font-bold text-on-surface">
<span>Minuman</span>
<span>24%</span>
</div>
<div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
<div class="h-full bg-tertiary-container w-[24%] rounded-full"></div>
</div>
</div>
<div class="space-y-2">
<div class="flex justify-between text-sm font-bold text-on-surface">
<span>Camilan</span>
<span>12%</span>
</div>
<div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
<div class="h-full bg-sky-500 w-[12%] rounded-full"></div>
</div>
</div>
<div class="space-y-2">
<div class="flex justify-between text-sm font-bold text-on-surface">
<span>Lain-lain</span>
<span>6%</span>
</div>
<div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
<div class="h-full bg-slate-300 w-[6%] rounded-full"></div>
</div>
</div>
</div>
<div class="mt-8 pt-8 border-t border-slate-100 text-center">
<button class="text-primary font-bold hover:underline">Lihat Detail Kategori</button>
</div>
</div>
</section>
<section class="mt-8">
<div class="bg-surface-container-low rounded-[2rem] p-8">
<div class="flex items-center justify-between mb-8">
<div>
<h4 class="text-xl font-extrabold text-on-surface">Transaksi Terkini</h4>
<p class="text-sm text-slate-400 font-medium">Aktivitas pesanan real-time dari seluruh kantin</p>
</div>
<button class="bg-surface-container-high text-on-surface px-6 py-2 rounded-full font-bold text-sm hover:bg-surface-container-highest transition-all">Lihat Semua</button>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead>
<tr class="text-[10px] uppercase tracking-[0.1em] text-slate-400 border-b border-slate-50 font-black">
<th class="pb-4 px-2">ID PESANAN</th>
<th class="pb-4 px-2">PELANGGAN</th>
<th class="pb-4 px-2">PENJUAL</th>
<th class="pb-4 px-2">JUMLAH</th>
<th class="pb-4 px-2">STATUS</th>
<th class="pb-4 px-2 text-right">AKSI</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-50/50">
<tr class="group hover:bg-surface-container/30 transition-colors">
<td class="py-4 px-2 font-bold text-on-surface">#KT-88421</td>
<td class="py-4 px-2">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-slate-200">
<img alt="User" class="w-full h-full rounded-full object-cover" data-alt="headshot of a smiling young woman with glasses for a user avatar profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBasatiGmSZg3IZmxeQzH8k7AH7zKsoHT73KviWoaTVXjl1QcVUlxoog9XW3MeLebGTbtgAWbjm5_H9pbgAtJl2OYz01O8M4ewKDXfovfwJ2RVlaISvXggmVRA3T1YcVfg3M1hZgcko1R5S1-2PyaorGHVXai6_eTI1PcaYTlx6Ud1Mactwo08JtotRTezyg12r6Fh-NpaIkLuPnPAtCpjQxz5E_HfOhEZAciZFyuE8zUda8mrnQ9iyJsJi9OiTDI9gEbHrZY7v8Xk"/>
</div>
<span class="font-semibold text-slate-700">Dian Sastro</span>
</div>
</td>
<td class="py-4 px-2 font-medium text-slate-600">Dapoer Ibu Nana</td>
<td class="py-4 px-2 font-bold text-on-surface">Rp 45.000</td>
<td class="py-4 px-2">
<span class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full text-xs font-bold">Berhasil</span>
</td>
<td class="py-4 px-2 text-right">
<button class="p-2 rounded-full hover:bg-surface-container-highest text-slate-400">
<span class="material-symbols-outlined">more_horiz</span>
</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</section>
</main>
<button class="fixed bottom-8 right-8 w-16 h-16 rounded-full bg-primary-container text-on-primary-container shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50" onclick="location.href='admin_manajemen_menu_pesanan.php'">
<span class="material-symbols-outlined text-3xl">add</span>
</button>
<script>
    // Search Mock Logic
    const searchInput = document.getElementById('topSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');

    searchInput.addEventListener('input', (e) => {
        if (e.target.value.length > 2) {
            searchDropdown.classList.remove('hidden');
        } else {
            searchDropdown.classList.add('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.add('hidden');
        }
    });

    // Notifications Toggle
    const notifBtn = document.getElementById('notifBtn');
    const notifPanel = document.getElementById('notifPanel');

    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifPanel.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!notifPanel.contains(e.target)) {
            notifPanel.classList.add('hidden');
        }
    });

    // Sidebar active state logic (Visual)
    document.querySelectorAll('aside nav a').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('aside nav a').forEach(l => {
                l.classList.remove('text-sky-600', 'dark:text-sky-400', 'font-semibold', 'border-l-4', 'border-sky-500', 'bg-sky-50/50', 'dark:bg-sky-900/20');
                l.classList.add('text-slate-500', 'dark:text-slate-400');
            });
            this.classList.add('text-sky-600', 'dark:text-sky-400', 'font-semibold', 'border-l-4', 'border-sky-500', 'bg-sky-50/50', 'dark:bg-sky-900/20');
            this.classList.remove('text-slate-500', 'dark:text-slate-400');
        });
    });
</script>
</body></html>