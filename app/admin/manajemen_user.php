<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. AMBIL DATA USER (PEMBELI)
// Filter pencarian sederhana jika ada
$search = $_GET['search'] ?? '';
$query_sql = "SELECT * FROM users WHERE role='pembeli'";
if (!empty($search)) {
    $query_sql .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
}
$query_sql .= " ORDER BY id_user DESC";
$query = mysqli_query($koneksi, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manajemen User - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF9F8',
                        'primary-orange': '#E25E3E',
                        'accent-blue': '#2D9CDB',
                        'accent-green': '#27AE60'
                    },
                    borderRadius: { '4xl': '2.5rem' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFF9F8; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #E25E3E; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800 flex">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-10 min-h-screen">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 mt-14 lg:mt-0">
        <div class="flex items-center gap-4">
            <a href="dashboard_admin.php" class="hidden md:flex w-12 h-12 rounded-2xl bg-white border border-slate-100 items-center justify-center text-slate-400 hover:text-primary-orange hover:border-primary-orange transition-all shadow-sm">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h2 class="text-3xl font-extrabold text-[#003049]">Manajemen User</h2>
                <p class="text-slate-400 font-medium text-sm">Kelola data pelanggan yang terdaftar di sistem.</p>
            </div>
        </div>
        
        <form action="" method="GET" class="relative w-full md:w-auto">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari user atau email..." 
                   class="pl-12 pr-6 py-3 bg-white border border-slate-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-orange/20 focus:border-primary-orange w-full md:w-80 shadow-sm transition-all">
        </form>
    </header>

    <div class="mb-8 inline-flex items-center gap-2 bg-orange-50 px-4 py-2 rounded-xl border border-orange-100">
        <span class="material-symbols-outlined text-primary-orange text-sm">person</span>
        <span class="text-xs font-bold text-primary-orange uppercase tracking-wider">Total: <?= mysqli_num_rows($query) ?> Pelanggan</span>
    </div>

    <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden transition-all hover:shadow-md">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-white/50 backdrop-blur-md sticky left-0">
            <h3 class="font-bold text-[#003049]">Database Pelanggan</h3>
            <a href="manajemen_user.php" class="text-[10px] font-black text-slate-400 hover:text-primary-orange uppercase tracking-widest transition-all">Reset Filter</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-black bg-slate-50/50">
                        <th class="py-6 px-8">Informasi Profil</th>
                        <th class="py-6 px-8">Kontak Email</th>
                        <th class="py-6 px-8">Kelas</th>
                        <th class="py-6 px-8">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($user = mysqli_fetch_assoc($query)): ?>
                    <tr class="group hover:bg-bg-soft transition-all">
                        <td class="py-6 px-8">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-2xl bg-[#003049] flex items-center justify-center text-white text-xs font-bold uppercase shadow-inner group-hover:scale-110 transition-transform">
                                    <?= substr($user['username'], 0, 2) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-[#003049] group-hover:text-primary-orange transition-colors"><?= htmlspecialchars($user['username']) ?></p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5 tracking-tighter">UID: #<?= $user['id_user'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="material-symbols-outlined text-[16px]">mail</span>
                                <span class="text-sm font-medium"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            <span class="text-sm font-medium text-slate-600"><?= htmlspecialchars($user['kelas'] ?: '-') ?></span>
                        </td>
                        <td class="py-6 px-8">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-accent-green text-[10px] font-black uppercase rounded-lg border border-green-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-accent-green animate-pulse"></span>
                                Active <?= $user['role'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if(mysqli_num_rows($query) == 0): ?>
                    <tr>
                        <td colspan="4" class="py-32 text-center bg-slate-50/20">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                                    <span class="material-symbols-outlined text-4xl text-slate-200">person_search</span>
                                </div>
                                <h4 class="text-slate-800 font-bold">User Tidak Ditemukan</h4>
                                <p class="text-slate-400 text-sm mt-1 max-w-[250px]">Coba gunakan kata kunci lain atau pastikan ejaan sudah benar.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>
