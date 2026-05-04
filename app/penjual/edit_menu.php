<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// PROTEKSI
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/halaman_login.php");
    exit();
}

// AMBIL DATA
if (!isset($_GET['id'])) {
    header("Location: kelola_menu_penjual.php");
    exit();
}

// Sesuaikan id_menu (huruf kecil semua sesuai saran coding standard, 
// atau pastikan sesuai variabel di URL)
$id_menu   = $_GET['id'];
$id_kantin = $_SESSION['id_kantin'];

// Menggunakan nama kolom sesuai database: id_menu, id_kantin, nama_menu, dll.
$query = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu='$id_menu' AND id_kantin='$id_kantin'");
$data  = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='kelola_menu_penjual.php';</script>";
    exit();
}

// ================= UPDATE =================
if (isset($_POST['update'])) {
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $harga     = $_POST['harga'];
    $kategori  = $_POST['kategori']; // enum('Makanan','Minuman','Camilan')
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $stok      = $_POST['stok'];

    // LOGIKA OTOMATIS STATUS (Sesuai enum database: 'Tersedia', 'Habis')
    $status = ($stok <= 0) ? 'Habis' : 'Tersedia';

    // Proses Foto
    if ($_FILES['foto']['name'] != "") {
        $foto_name = time() . '_' . $_FILES['foto']['name'];
        $tmp_name  = $_FILES['foto']['tmp_name'];

        // Sesuaikan path folder upload kamu
        move_uploaded_file($tmp_name, "../../public/uploads/" . $foto_name);

        // Hapus foto lama jika ada
        if ($data['foto'] && file_exists("../../public/uploads/" . $data['foto'])) {
            unlink("../../public/uploads/" . $data['foto']);
        }

        $sql = "UPDATE menu SET 
                    nama_menu='$nama_menu',
                    harga='$harga',
                    kategori='$kategori',
                    deskripsi='$deskripsi',
                    stok='$stok',
                    status='$status',
                    foto='$foto_name'
                WHERE id_menu='$id_menu'";
    } else {
        $sql = "UPDATE menu SET 
                    nama_menu='$nama_menu',
                    harga='$harga',
                    kategori='$kategori',
                    deskripsi='$deskripsi',
                    stok='$stok',
                    status='$status'
                WHERE id_menu='$id_menu'";
    }

    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Menu berhasil diperbarui! Status otomatis: $status'); window.location='kelola_menu_penjual.php';</script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Menu - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFF9F8; }
    </style>
</head>
<body class="text-slate-800 flex">

<?php include '../../includes/sidebar_penjual.php'; ?>

<main class="flex-1 lg:ml-72 p-4 md:p-10">
    <header class="flex items-center gap-4 mb-10">
        <a href="kelola_menu_penjual.php" class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-500 shadow-sm">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-3xl font-extrabold text-[#003049]">Edit Menu</h2>
            <p class="text-slate-400 text-sm">Update stok dan informasi menu secara realtime.</p>
        </div>
    </header>

    <div class="max-w-2xl bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div>
                <label class="block text-sm font-bold mb-2">Nama Hidangan</label>
                <input type="text" name="nama_menu" value="<?= $data['nama_menu'] ?>" required class="w-full px-5 py-4 rounded-2xl border bg-slate-50 focus:bg-white outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Harga (Rp)</label>
                    <input type="number" name="harga" value="<?= $data['harga'] ?>" required class="w-full px-5 py-4 rounded-2xl border bg-slate-50">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Kategori</label>
                    <select name="kategori" class="w-full px-5 py-4 rounded-2xl border bg-slate-50">
                        <option value="Makanan" <?= $data['kategori'] == 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                        <option value="Minuman" <?= $data['kategori'] == 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                        <option value="Camilan" <?= $data['kategori'] == 'Camilan' ? 'selected' : '' ?>>Camilan</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Stok Saat Ini</label>
                    <input type="number" name="stok" value="<?= $data['stok'] ?>" required min="0" class="w-full px-5 py-4 rounded-2xl border bg-slate-50 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2 text-slate-400">Status Sistem</label>
                    <div class="px-5 py-4 rounded-2xl border bg-slate-100 font-bold <?= $data['status'] == 'Habis' ? 'text-red-500' : 'text-green-500' ?>">
                        <?= $data['status'] ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Deskripsi</label>
                <textarea name="deskripsi" rows="3" class="w-full px-5 py-4 rounded-2xl border bg-slate-50"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Foto Produk</label>
                <div class="flex items-center gap-4">
                    <?php if($data['foto']): ?>
                        <img src="../..//uploads/<?= $data['foto'] ?>" class="w-20 h-20 rounded-2xl object-cover border">
                    <?php endif; ?>
                    <input type="file" name="foto" class="text-xs">
                </div>
            </div>

            <button type="submit" name="update" class="w-full bg-orange-500 text-white py-4 rounded-2xl font-bold hover:shadow-lg transition-all">
                Simpan Perubahan
            </button>
        </form>
    </div>
</main>
</body>
</html>