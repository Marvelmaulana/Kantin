<?php
session_start();
include(__DIR__ . '/../../config/config.php');
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_menu = (int)($_GET['id'] ?? 0);
if ($id_menu <= 0) {
    header('Location: manajemen_menu.php?error=Data+tidak+valid');
    exit();
}

$menu = kk_fetch_one($koneksi, "SELECT * FROM menu WHERE id_menu=$id_menu LIMIT 1");
if (!$menu) {
    header('Location: manajemen_menu.php?error=Menu+tidak+ditemukan');
    exit();
}

$kantin_q = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin ASC");

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_menu = mysqli_real_escape_string($koneksi, trim($_POST['nama_menu'] ?? ''));
    $harga = floatval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? 'Makanan'));
    $status = in_array($_POST['status'] ?? 'Tersedia', ['Tersedia','Habis']) ? $_POST['status'] : 'Tersedia';
    $id_kantin = intval($_POST['id_kantin'] ?? $menu['id_kantin']);

    if ($nama_menu === '' || $id_kantin <= 0) {
        $message = 'Nama menu dan kantin wajib diisi.';
        $message_type = 'error';
    } else {
        $foto_path = null;
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

        $foto_set = $foto_path ? ", foto='$foto_path'" : '';
        $updated = mysqli_query($koneksi, "UPDATE menu SET id_kantin=$id_kantin, nama_menu='$nama_menu', harga=$harga, kategori='$kategori', stok=$stok, status='$status'$foto_set WHERE id_menu=$id_menu");
        if ($updated) {
            $message = 'Menu berhasil diperbarui.';
            $message_type = 'success';
            $menu = kk_fetch_one($koneksi, "SELECT * FROM menu WHERE id_menu=$id_menu LIMIT 1");
        } else {
            $message = 'Gagal memperbarui menu: ' . mysqli_error($koneksi);
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
    <title>Edit Menu - Admin</title>
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
<body class="text-slate-800 flex">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold">Edit Menu</h2>
            <p class="text-slate-500">Perbarui data menu dan status.</p>
        </div>
        <a href="manajemen_menu.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700">Kembali</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl <?= $message_type==='success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl shadow p-6 grid gap-5 max-w-3xl">
        <div>
            <label class="block text-sm font-bold mb-2">Nama Menu</label>
            <input name="nama_menu" value="<?= htmlspecialchars($menu['nama_menu']) ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Harga</label>
                <input name="harga" type="number" step="0.01" value="<?= htmlspecialchars($menu['harga']) ?>" class="w-full px-4 py-3 border rounded-2xl" />
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Stok</label>
                <input name="stok" type="number" value="<?= htmlspecialchars($menu['stok']) ?>" class="w-full px-4 py-3 border rounded-2xl" />
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Kategori</label>
                <select name="kategori" class="w-full px-4 py-3 border rounded-2xl">
                    <option <?= $menu['kategori']==='Makanan'?'selected':'' ?>>Makanan</option>
                    <option <?= $menu['kategori']==='Minuman'?'selected':'' ?>>Minuman</option>
                    <option <?= $menu['kategori']==='Cemilan'?'selected':'' ?>>Cemilan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 border rounded-2xl">
                    <option value="Tersedia" <?= $menu['status']==='Tersedia'?'selected':'' ?>>Tersedia</option>
                    <option value="Habis" <?= $menu['status']==='Habis'?'selected':'' ?>>Habis</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Kantin</label>
            <select name="id_kantin" class="w-full px-4 py-3 border rounded-2xl">
                <?php mysqli_data_seek($kantin_q,0); while($k = mysqli_fetch_assoc($kantin_q)): ?>
                    <option value="<?= $k['id_kantin'] ?>" <?= $menu['id_kantin']==$k['id_kantin']?'selected':'' ?>><?= htmlspecialchars($k['nama_kantin']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Foto Menu (Opsional)</label>
            <input type="file" name="foto" accept="image/*" class="w-full" />
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_menu.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
