<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

$id_k = $_SESSION['id_kantin'];
$username_kantin = $_SESSION['username'] ?? 'kantin_user';
$filter = isset($_GET['kat']) ? mysqli_real_escape_string($koneksi, $_GET['kat']) : 'Semua';

$sql = "SELECT * FROM menu WHERE id_kantin = '$id_k'";
if ($filter != 'Semua') { $sql .= " AND kategori = '$filter'"; }
$query = mysqli_query($koneksi, $sql . " ORDER BY id_menu DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Kelola Menu - Kantin Kita</title>
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
                <h2 class="text-3xl font-extrabold tracking-tight text-stone-900">Kelola Menu</h2>
                <p class="text-stone-500 text-sm mt-1">Atur katalog jualan kamu agar pembeli makin tertarik.</p>
            </div>
            <a href="tambah_menu.php" class="w-full sm:w-auto bg-primary text-white px-8 py-4 rounded-full flex justify-center items-center gap-3 font-bold shadow-lg shadow-red-900/20 active:scale-95 transition-all hover:bg-red-800">
                <span class="material-symbols-outlined text-[20px]">add</span> Tambah Menu
            </a>
        </header>

        <div class="overflow-x-auto no-scrollbar mb-8">
            <div class="flex gap-2 p-1.5 bg-white border border-orange-50 w-fit rounded-2xl shadow-sm">
                <?php foreach (['Semua', 'Makanan', 'Minuman', 'Camilan'] as $kat): ?>
                    <a href="?kat=<?= $kat ?>" class="px-6 py-2.5 rounded-xl text-sm transition-all <?= $filter == $kat ? 'bg-primary text-white font-bold shadow-md' : 'text-stone-400 hover:text-primary' ?>">
                        <?= $kat ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php while($m = mysqli_fetch_assoc($query)): ?>
            <div class="bg-white rounded-[2.5rem] border border-orange-50 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="relative h-56 bg-stone-100 overflow-hidden">
                    <img src="../../uploads/<?= $m['foto'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" onerror="this.src='https://placehold.co/600x400?text=Menu'">
                    <div class="absolute top-4 right-4">
                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm backdrop-blur-md <?= $m['status'] == 'Tersedia' ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white' ?>">
                            <?= $m['status'] ?>
                        </span>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-1">
                    <h3 class="font-bold text-xl text-stone-800 leading-tight mb-2 group-hover:text-primary transition-colors"><?= $m['nama_menu'] ?></h3>
                    <p class="text-xs text-stone-400 line-clamp-2 leading-relaxed"><?= $m['deskripsi'] ?></p>
                    
                    <div class="mt-8 flex justify-between items-center pt-6 border-t border-stone-50">
                        <div>
                            <p class="text-[10px] font-bold text-stone-400 uppercase mb-1 tracking-wider">Harga Menu</p>
                            <span class="text-2xl font-black text-primary">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex gap-2">
                            <a href="edit_menu.php?id=<?= $m['id_menu'] ?>" class="p-3 bg-stone-50 text-stone-400 hover:bg-blue-50 hover:text-blue-600 rounded-2xl transition-all">
                                <span class="material-symbols-outlined text-[20px]">edit_note</span>
                            </a>
                            <a href="proses_menu.php?aksi=hapus&id=<?= $m['id_menu'] ?>" onclick="return confirm('Hapus menu ini?')" class="p-3 bg-stone-50 text-stone-400 hover:bg-red-50 hover:text-red-600 rounded-2xl transition-all">
                                <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>