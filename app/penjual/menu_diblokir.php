<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

$id_k = $_SESSION['id_kantin'];
$username_kantin = $_SESSION['username'] ?? 'kantin_user';

$sql = "SELECT * FROM menu WHERE id_kantin = '$id_k' AND status = 'Diblokir' ORDER BY id_menu DESC";
$query = mysqli_query($koneksi, $sql);
$menu_count = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Menu Diblokir - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { "primary": "#b22204", "surface": "#fff8f6", "soft-orange": "#fff0ee" } } } }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-surface text-stone-800 flex min-h-screen">
    <?php include '../../includes/sidebar_penjual.php'; ?>

    <main class="flex-1 lg:ml-72 p-4 md:p-8 transition-all">
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-10 mt-14 lg:mt-0">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-stone-900">Menu Diblokir</h2>
                <p class="text-stone-500 text-sm mt-1">Daftar menu Anda yang sedang diblokir oleh admin.</p>
                <p class="text-stone-400 text-xs mt-2">Menemukan <strong><?= $menu_count ?></strong> menu yang diblokir.</p>
            </div>
        </header>

        <?php if ($menu_count === 0): ?>
        <div class="bg-white rounded-[2.5rem] p-12 text-center border border-orange-50 shadow-sm">
            <span class="material-symbols-outlined text-6xl text-stone-300 mb-4 block">check_circle</span>
            <h3 class="text-xl font-bold text-stone-700">Tidak ada menu yang diblokir</h3>
            <p class="text-stone-500 text-sm mt-2">Semua menu Anda aman dan sesuai dengan kebijakan.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php while($m = mysqli_fetch_assoc($query)): 
                $canViewDetail = true;
            ?>
            <div class="bg-white rounded-[2.5rem] border border-orange-50 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col group opacity-90 grayscale-[10%]">
                <?php if ($canViewDetail): ?>
                <a href="detail_menu_penjual.php?id=<?= $m['id_menu'] ?>" class="relative h-56 bg-stone-100 overflow-hidden block">
                <?php else: ?>
                <div class="relative h-56 bg-stone-100 overflow-hidden">
                <?php endif; ?>
                    <img src="../../uploads/<?= $m['foto'] ?>" class="w-full h-full object-cover transition-transform duration-500 <?= $canViewDetail ? 'group-hover:scale-110' : '' ?>" onerror="this.src='https://placehold.co/600x400?text=Menu'">
                    <img src="../../uploads/logo/Cuplikan_layar_2026-06-08_104038-removebg-preview.png" alt="Halal" class="absolute bottom-2 right-2 w-8 h-8 md:w-10 md:h-10 object-contain drop-shadow-md bg-white/50 backdrop-blur-sm rounded-full p-0.5 z-10">
                    <div class="absolute top-4 right-4 z-10">
                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm backdrop-blur-md bg-red-600/90 text-white">
                            <?= htmlspecialchars($m['status']) ?>
                        </span>
                    </div>
                <?php if ($canViewDetail): ?>
                </a>
                <?php else: ?>
                </div>
                <?php endif; ?>
                <div class="p-8 flex flex-col flex-1">
                    <?php if ($canViewDetail): ?>
                    <a href="detail_menu_penjual.php?id=<?= $m['id_menu'] ?>" class="font-bold text-xl text-stone-800 leading-tight mb-2 group-hover:text-primary transition-colors"><?= htmlspecialchars($m['nama_menu']) ?></a>
                    <?php else: ?>
                    <div class="font-bold text-xl text-stone-800 leading-tight mb-2"><?= htmlspecialchars($m['nama_menu']) ?></div>
                    <?php endif; ?>
                    <p class="text-xs text-stone-400 line-clamp-2 leading-relaxed"><?= $m['deskripsi'] ?></p>
                    <?php if (!empty($m['catatan_blokir'])): ?>
                    <div class="mt-4 p-4 rounded-2xl bg-red-50 border border-red-100 flex gap-3 items-start">
                        <span class="material-symbols-outlined text-red-500 text-[20px] mt-0.5">warning</span>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wider text-red-600 mb-1">Alasan Diblokir</p>
                            <p class="text-xs text-red-700 font-medium leading-relaxed">
                                <?= htmlspecialchars($m['catatan_blokir']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mt-auto flex justify-between items-center pt-6 border-t border-stone-50">
                        <div>
                            <p class="text-[10px] font-bold text-stone-400 uppercase mb-1 tracking-wider">Harga Menu</p>
                            <span class="text-2xl font-black text-primary">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($canViewDetail): ?>
                            <a href="edit_menu.php?id=<?= $m['id_menu'] ?>" class="p-3 bg-stone-50 text-stone-400 hover:bg-blue-50 hover:text-blue-600 rounded-2xl transition-all" title="Edit Menu">
                                <span class="material-symbols-outlined text-[20px]">edit_note</span>
                            </a>
                            <?php endif; ?>
                            <a href="proses_menu.php?aksi=hapus&id=<?= $m['id_menu'] ?>" onclick="return confirm('Hapus menu ini?')" class="p-3 bg-stone-50 text-stone-400 hover:bg-red-50 hover:text-red-600 rounded-2xl transition-all" title="Hapus Menu">
                                <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
