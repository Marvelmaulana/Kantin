<!-- ====================================
     SECTION: INFORMASI STOK MENU
     Merupakan partial yang menampilkan daftar menu dengan info stok
==================================== -->

<?php
// File ini di-include dari dashboard_penjual.php atau kelola_menu_penjual.php
// Variabel yang tersedia: $id_kantin

// Query menu dengan info stok
$query_menu = mysqli_query($koneksi, "
    SELECT 
        m.id_menu,
        m.nama_menu,
        m.harga,
        m.stok,
        m.status,
        m.kategori,
        COUNT(dp.id_detail) as jml_terjual,
        COALESCE(SUM(dp.qty), 0) as total_terjual,
        m.created_at
    FROM menu m
    LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
    WHERE m.id_kantin = $id_kantin
    GROUP BY m.id_menu
    ORDER BY m.created_at DESC
");

$menus = [];
if ($query_menu && mysqli_num_rows($query_menu) > 0) {
    while ($row = mysqli_fetch_assoc($query_menu)) {
        $menus[] = $row;
    }
}

// Hitung statistik
$total_menu = count($menus);
$menu_tersedia = count(array_filter($menus, fn($m) => $m['status'] === 'Tersedia' && $m['stok'] > 0));
$menu_habis = count(array_filter($menus, fn($m) => $m['stok'] <= 0));
?>

<div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-box text-blue-600 text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-gray-900">Informasi Stok</h2>
                <p class="text-xs text-gray-500 mt-0.5"><?= $total_menu ?> menu tersedia</p>
            </div>
        </div>
    </div>

    <!-- Statistik Mini -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-4 text-center">
            <p class="text-2xl font-black text-blue-700"><?= $total_menu ?></p>
            <p class="text-xs text-blue-600 font-semibold mt-1">Total Menu</p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-4 text-center">
            <p class="text-2xl font-black text-green-700"><?= $menu_tersedia ?></p>
            <p class="text-xs text-green-600 font-semibold mt-1">Tersedia</p>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl p-4 text-center">
            <p class="text-2xl font-black text-red-700"><?= $menu_habis ?></p>
            <p class="text-xs text-red-600 font-semibold mt-1">Habis</p>
        </div>
    </div>

    <!-- Daftar Menu -->
    <?php if (empty($menus)): ?>
    <div class="text-center py-12">
        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-100 flex items-center justify-center">
            <i class="fa-solid fa-inbox text-2xl text-gray-300"></i>
        </div>
        <p class="text-gray-500 font-semibold">Belum ada menu</p>
        <p class="text-sm text-gray-400 mt-1">Tambahkan menu untuk memulai</p>
    </div>
    <?php else: ?>
    <div class="space-y-2 max-h-80 overflow-y-auto">
        <?php foreach ($menus as $menu): 
            $stok = (int)$menu['stok'];
            $status = $menu['status'] ?? 'Tersedia';
            $statusColor = match($status) {
                'Tersedia' => $stok > 5 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700',
                'Habis' => 'bg-red-100 text-red-700',
                default => 'bg-gray-100 text-gray-700'
            };
            $statusLabel = match($status) {
                'Tersedia' => $stok > 5 ? 'Tersedia' : "Terbatas ($stok)",
                'Habis' => 'Habis',
                default => 'Tidak Diketahui'
            };
        ?>
        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all">
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $statusColor ?> whitespace-nowrap ml-2">
                        <?= $statusLabel ?>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mb-2">
                    <i class="fa-solid fa-tag mr-1"></i>
                    <?= htmlspecialchars($menu['kategori']) ?> · 
                    Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                </p>
                <div class="flex items-center gap-4">
                    <div class="text-xs">
                        <span class="font-bold text-gray-700">Stok:</span>
                        <span class="font-black text-lg <?= $stok > 0 ? 'text-blue-600' : 'text-red-600' ?>"><?= $stok ?></span>
                    </div>
                    <div class="text-xs">
                        <span class="font-bold text-gray-700">Terjual:</span>
                        <span class="font-black text-lg text-green-600"><?= (int)$menu['total_terjual'] ?></span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 ml-4 flex-shrink-0">
                <a href="edit_menu.php?id=<?= $menu['id_menu'] ?>" 
                   class="px-3 py-2 rounded-lg bg-blue-500 text-white text-xs font-bold hover:bg-blue-600 transition-colors"
                   title="Edit menu">
                    <i class="fa-solid fa-pen"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
