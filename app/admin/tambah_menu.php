<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$message_type = 'success';

// Ambil daftar kantin
$kantin_q = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_menu'])) {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_menu'] ?? ''));
    $harga = floatval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? 'Makanan'));
    $status = in_array($_POST['status'] ?? 'Tersedia', ['Tersedia','Habis']) ? $_POST['status'] : 'Tersedia';
    $id_kantin = intval($_POST['id_kantin'] ?? 0);
    $foto_path = null;

    if ($nama === '' || $id_kantin <= 0) {
        $message = 'Nama menu dan kantin wajib diisi.';
        $message_type = 'error';
    } else {
        if (!empty($_FILES['foto']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $fn = 'menu_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../../uploads/' . $fn)) {
                    $foto_path = $fn;
                }
            }
        }

        $foto_insert = $foto_path ? "'$foto_path'" : 'NULL';
        $ins = mysqli_query($koneksi, "INSERT INTO menu (id_kantin,nama_menu,harga,foto,kategori,stok,status,created_at) VALUES ($id_kantin,'$nama',$harga,$foto_insert,'$kategori',$stok,'$status',NOW())");
        if ($ins) {
            $message = 'Menu berhasil ditambahkan.';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan menu: ' . mysqli_error($koneksi);
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
        <title><?= t('admin.add_menu_title', 'Tambah Menu - Admin') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="text-slate-800 flex overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex items-center justify-between mb-6">
        <div>
              <h2 class="text-2xl font-extrabold"><?= t('admin.add_menu_heading', 'Tambah Menu') ?></h2>
              <p class="text-sm text-slate-500"><?= t('admin.add_menu_desc', 'Form untuk menambahkan menu baru ke kantin.') ?></p>
        </div>
           <a href="manajemen_menu.php" class="px-4 py-2 rounded bg-slate-100"><?= t('action.back', 'Kembali') ?></a>
    </header>

    <?php if ($message !== ''): ?>
    <div class="mb-4 px-4 py-3 rounded <?= $message_type==='success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> border">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-2xl shadow" onsubmit="return confirm('Apakah Anda yakin ingin menambahkan menu ini?');">
        <input type="hidden" name="tambah_menu" value="1" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                    <label class="block text-sm font-bold"><?= t('seller_menu.menu_name', 'Nama Menu') ?></label>
                <input name="nama_menu" required class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                        <label class="block text-sm font-bold"><?= t('menu.price_label', 'Harga') ?></label>
                <input name="harga" type="number" step="0.01" class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                        <label class="block text-sm font-bold"><?= t('menu.stock_label', 'Stok') ?></label>
                <input name="stok" type="number" class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                        <label class="block text-sm font-bold"><?= t('menu.category_label', 'Kategori') ?></label>
                <select name="kategori" class="w-full px-3 py-2 border rounded">
                    <option>Makanan</option>
                    <option>Minuman</option>
                    <option>Cemilan</option>
                </select>
            </div>
            <div>
                        <label class="block text-sm font-bold"><?= t('menu.photo_label', 'Foto Menu') ?></label>
                <input type="file" name="foto" accept="image/*" class="w-full" />
            </div>
            <div>
                        <label class="block text-sm font-bold"><?= t('menu.status_label', 'Status') ?></label>
                <select name="status" class="w-full px-3 py-2 border rounded">
                    <option value="Tersedia">Tersedia</option>
                    <option value="Habis">Habis</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-bold">Pilih Kantin</label>
                <select name="id_kantin" required class="w-full px-3 py-2 border rounded">
                    <option value="">-- Pilih Kantin --</option>
                    <?php mysqli_data_seek($kantin_q,0); while($k = mysqli_fetch_assoc($kantin_q)): ?>
                        <option value="<?= $k['id_kantin'] ?>"><?= htmlspecialchars($k['nama_kantin']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-3">
                <a href="manajemen_menu.php" class="px-4 py-2 rounded border"><?= t('action.cancel', 'Batal') ?></a>
                <button type="submit" class="px-4 py-2 rounded bg-primary-orange text-white font-bold"><?= t('seller_menu.add_new', '+ Tambah Menu Baru') ?></button>
        </div>
    </form>
</main>

</body>
</html>
