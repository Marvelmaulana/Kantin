<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Initialize CSRF
admin_init_csrf_token();

$id_menu = admin_validate_id($_GET['id'] ?? 0);
if (!$id_menu) {
    header('Location: manajemen_menu.php?error=Data+tidak+valid');
    exit();
}

try {
    $menu = admin_query_fetch_one($koneksi,
        "SELECT * FROM menu WHERE id_menu = ? LIMIT 1",
        [$id_menu], 'i');
    
    if (!$menu) {
        header('Location: manajemen_menu.php?error=Menu+tidak+ditemukan');
        exit();
    }
    
    // Get kantin list
    $kantins = admin_query_fetch_all($koneksi,
        "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin ASC",
        [], '');
} catch (Exception $e) {
    header('Location: manajemen_menu.php?error=' . urlencode($e->getMessage()));
    exit();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'CSRF token tidak valid!';
        $message_type = 'error';
    } else {
        // Validate input
        $nama_menu = admin_validate_string($_POST['nama_menu'] ?? '', 1, 150);
        $harga = admin_validate_numeric($_POST['harga'] ?? 0, 'float', 0, 999999.99);
        $stok = admin_validate_numeric($_POST['stok'] ?? 0, 'int', 0, 99999);
        $kategori = admin_validate_enum($_POST['kategori'] ?? 'Makanan',
            ['Makanan', 'Minuman', 'Cemilan', 'Dessert', 'Snack']);
        $status = admin_validate_enum($_POST['status'] ?? 'Tersedia',
            ['Tersedia', 'Habis']);
        $id_kantin = admin_validate_id($_POST['id_kantin'] ?? 0);
        
        if (!$nama_menu || !$id_kantin || $harga === null || $stok === null) {
            $message = 'Validasi input gagal. Periksa semua field.';
            $message_type = 'error';
        } else {
            try {
                // Handle file upload if provided
                $foto_file = $menu['foto'];
                
                if (!empty($_FILES['foto']['name'])) {
                    $upload = admin_process_file_upload(
                        $_FILES['foto'],
                        __DIR__ . '/../../uploads',
                        'menu',
                        ['jpg', 'jpeg', 'png', 'webp'],
                        5242880
                    );
                    
                    if (!$upload['success']) {
                        throw new Exception($upload['error']);
                    }
                    
                    // Delete old file if exists
                    if (!empty($menu['foto'])) {
                        admin_delete_file(__DIR__ . '/../../uploads/' . $menu['foto']);
                    }
                    
                    $foto_file = $upload['filename'];
                }
                
                // Update menu dengan prepared statement
                admin_query_execute($koneksi,
                    "UPDATE menu SET id_kantin = ?, nama_menu = ?, harga = ?, 
                     kategori = ?, stok = ?, status = ?, foto = ? 
                     WHERE id_menu = ?",
                    [$id_kantin, $nama_menu, $harga, $kategori, $stok, $status,
                     $foto_file, $id_menu],
                    'isisdssi');
                
                $message = 'Menu berhasil diperbarui.';
                $message_type = 'success';
                
                // Log action
                admin_log_action($koneksi, $_SESSION['id_user'], 'UPDATE', 'menu', $id_menu,
                    "Updated menu: $nama_menu, harga: $harga");
                
                // Reload menu data
                $menu = admin_query_fetch_one($koneksi,
                    "SELECT * FROM menu WHERE id_menu = ? LIMIT 1",
                    [$id_menu], 'i');
                
            } catch (Exception $e) {
                $message = 'Error: ' . htmlspecialchars($e->getMessage());
                $message_type = 'error';
            }
        }
    }
}
?>
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= t('admin.edit_menu_title', 'Edit Menu - Admin') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="text-slate-800 flex overflow-x-hidden">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold"><?= t('admin.edit_menu_heading', 'Edit Menu') ?></h2>
            <p class="text-slate-500"><?= t('admin.edit_menu_desc', 'Perbarui data menu dan status.') ?></p>
        </div>
        <a href="manajemen_menu.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700"><?= t('action.back', 'Kembali') ?></a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl <?= $message_type==='success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl shadow p-6 grid gap-5 max-w-3xl">
        <?= admin_csrf_token_field() ?>
        
        <div>
            <label class="block text-sm font-bold mb-2">Nama Menu</label>
            <input type="text" name="nama_menu" value="<?= htmlspecialchars($menu['nama_menu']) ?>" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl focus:border-primary-orange outline-none" />
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Harga (Rp)</label>
                <input type="number" name="harga" step="100" min="0" value="<?= htmlspecialchars($menu['harga'] ?? 0) ?>" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl focus:border-primary-orange outline-none" />
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Stok</label>
                <input type="number" name="stok" min="0" value="<?= htmlspecialchars($menu['stok'] ?? 0) ?>" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl focus:border-primary-orange outline-none" />
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Kategori</label>
                <select name="kategori" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white focus:border-primary-orange outline-none">
                    <option value="Makanan" <?= ($menu['kategori'] ?? '') === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                    <option value="Minuman" <?= ($menu['kategori'] ?? '') === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    <option value="Cemilan" <?= ($menu['kategori'] ?? '') === 'Cemilan' ? 'selected' : '' ?>>Cemilan</option>
                    <option value="Dessert" <?= ($menu['kategori'] ?? '') === 'Dessert' ? 'selected' : '' ?>>Dessert</option>
                    <option value="Snack" <?= ($menu['kategori'] ?? '') === 'Snack' ? 'selected' : '' ?>>Snack</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Status</label>
                <select name="status" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white focus:border-primary-orange outline-none">
                    <option value="Tersedia" <?= ($menu['status'] ?? '') === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="Habis" <?= ($menu['status'] ?? '') === 'Habis' ? 'selected' : '' ?>>Habis</option>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-bold mb-2">Kantin</label>
            <select name="id_kantin" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white focus:border-primary-orange outline-none">
                <option value="">Pilih kantin</option>
                <?php foreach ($kantins as $k): ?>
                    <option value="<?= (int)$k['id_kantin'] ?>" <?= ($menu['id_kantin'] ?? 0) == $k['id_kantin'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kantin']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-bold mb-2">Foto Menu (Opsional)</label>
            <?php if (!empty($menu['foto'])): ?>
                <p class="text-xs text-slate-500 mb-2">Foto saat ini: <?= htmlspecialchars($menu['foto']) ?></p>
            <?php endif; ?>
            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-3 border border-slate-200 rounded-2xl" />
            <p class="text-xs text-slate-500 mt-2">Max 5MB. Format: JPG, PNG, WebP</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_menu.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold hover:bg-orange-600 transition">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
