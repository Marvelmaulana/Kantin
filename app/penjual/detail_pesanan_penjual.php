<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$id_pesanan = (int)($_GET['id'] ?? 0);
$id_kantin = (int)($_SESSION['id_kantin'] ?? 0);

if ($id_pesanan <= 0 || $id_kantin <= 0) {
    header("Location: pesanan_masuk.php");
    exit;
}

$query_t = mysqli_query($koneksi, "
    SELECT p.*, u.username
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    WHERE p.id_pesanan = $id_pesanan
      AND p.id_kantin = $id_kantin
    LIMIT 1
");
$data_t = mysqli_fetch_assoc($query_t);

if (!$data_t) {
    header("Location: pesanan_masuk.php?error=Pesanan+tidak+ditemukan");
    exit;
}

$query_d = mysqli_query($koneksi, "
    SELECT dp.*, COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu, m.foto
    FROM detail_pesanan dp
    JOIN menu m ON dp.id_menu = m.id_menu
    WHERE dp.id_pesanan = $id_pesanan
");

$nextStatus = [
    'Pending' => ['Diproses', 'Proses Pesanan', 'bg-primary'],
    'Diproses' => ['Siap Diambil', 'Tandai Siap Diambil', 'bg-blue-600'],
    'Siap Diambil' => ['Selesai', 'Selesaikan Pesanan', 'bg-green-600'],
];
$action = $nextStatus[$data_t['status']] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Pesanan #<?= $id_pesanan ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Be+Vietnam+Pro:wght@400;500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { "primary": "#b22204", "surface": "#fff8f6", "on-surface": "#271815", "on-surface-variant": "#5b403b", "surface-container-high": "#ffe2dc" } } } }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; overflow-x: hidden; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-surface text-on-surface">
<div class="flex min-h-screen relative">
    <button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-primary text-white p-2 rounded-xl shadow-lg">
        <span class="material-symbols-outlined">menu</span>
    </button>
    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <aside id="sidebar" class="h-screen w-64 fixed left-0 top-0 flex flex-col bg-white border-r border-orange-50 z-50 -translate-x-full lg:translate-x-0">
        <div class="flex flex-col h-full p-4 gap-2">
            <div class="px-4 py-6 mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-black text-primary">Kantin Kita</h1>
                    <p class="text-xs font-medium text-on-surface-variant uppercase tracking-widest">Seller Center</p>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-on-surface-variant">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <nav class="flex-1 space-y-1">
                <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm transition-all" href="dashboard_penjual.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard
                </a>
                <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm transition-all" href="kelola_menu_penjual.php">
                    <span class="material-symbols-outlined text-[20px]">restaurant_menu</span> Kelola Menu
                </a>
                <a class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-bold text-sm" href="pesanan_masuk.php">
                    <span class="material-symbols-outlined text-[20px]">pending_actions</span> Pesanan Masuk
                </a>
            </nav>
            <div class="mt-auto pt-4 border-t border-orange-100">
                <a class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl font-bold text-sm transition-all" href="../auth/logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                </a>
            </div>
        </div>
    </aside>

    <main class="flex-1 w-full lg:ml-64 p-6 md:p-8">
        <div class="mb-6 mt-12 lg:mt-0">
            <h2 class="text-2xl font-extrabold">Rincian Pesanan</h2>
            <p class="text-sm text-on-surface-variant">Detail pesanan pelanggan</p>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 border-b pb-4 mb-4">
                <div>
                    <p class="text-xs text-on-surface-variant">Pesanan Dari</p>
                    <h3 class="font-bold text-lg"><?= htmlspecialchars(strtoupper($data_t['username'])) ?></h3>
                    <p class="text-xs text-on-surface-variant">
                        ID #<?= $id_pesanan ?> &bull; <?= date('d M Y H:i', strtotime($data_t['tanggal'])) ?>
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 w-fit">
                    <?= htmlspecialchars(strtoupper($data_t['status'])) ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-on-surface-variant border-b">
                            <th class="py-3">Menu</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = mysqli_fetch_assoc($query_d)) : ?>
                        <tr class="border-b hover:bg-orange-50">
                            <td class="py-3 flex items-center gap-3">
                                <img src="<?= kk_upload_url($d['foto'] ?? '', 'menu') ?>" class="w-12 h-12 object-cover rounded-lg" onerror="this.src='../../public/assets/img/default-food.svg'">
                                <span class="font-semibold"><?= htmlspecialchars($d['nama_menu']) ?></span>
                            </td>
                            <td><?= (int)$d['qty'] ?>x</td>
                            <td>Rp <?= number_format((float)$d['harga'], 0, ',', '.') ?></td>
                            <td class="font-bold text-primary">Rp <?= number_format((float)$d['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mt-6">
                <a href="pesanan_masuk.php" class="px-4 py-2 rounded-xl bg-gray-100 text-sm font-bold hover:bg-gray-200 w-fit">Kembali</a>
                <div class="text-right">
                    <p class="text-xs text-on-surface-variant">Total</p>
                    <p class="text-xl font-black text-primary">Rp <?= number_format((float)$data_t['total_harga'], 0, ',', '.') ?></p>
                </div>
            </div>

            <?php if ($action) : ?>
            <div class="mt-6 flex justify-end">
                <form action="update_status.php" method="POST">
                    <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                    <input type="hidden" name="status_baru" value="<?= htmlspecialchars($action[0]) ?>">
                    <button class="<?= $action[2] ?> text-white px-6 py-3 rounded-xl font-bold hover:opacity-90"><?= htmlspecialchars($action[1]) ?></button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>
</body>
</html>
