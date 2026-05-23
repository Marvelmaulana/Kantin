<?php
session_start();
include(__DIR__ . '/../../config/config.php');
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_kantin = (int)($_GET['id'] ?? 0);
if ($id_kantin <= 0) {
    header('Location: manajemen_kantin.php?error=Data+tidak+valid');
    exit();
}

$kantin = kk_fetch_one($koneksi, "SELECT * FROM kantin WHERE id_kantin=$id_kantin LIMIT 1");
if (!$kantin) {
    header('Location: manajemen_kantin.php?error=Kantin+tidak+ditemukan');
    exit();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kantin = mysqli_real_escape_string($koneksi, trim($_POST['nama_kantin'] ?? ''));
    $lokasi = mysqli_real_escape_string($koneksi, trim($_POST['lokasi'] ?? ''));
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));

    if ($nama_kantin === '') {
        $message = 'Nama kantin wajib diisi.';
        $message_type = 'error';
    } else {
        $updated = mysqli_query($koneksi, "UPDATE kantin SET nama_kantin='$nama_kantin', lokasi='$lokasi', deskripsi='$deskripsi' WHERE id_kantin=$id_kantin");
        if ($updated) {
            $message = 'Data kantin berhasil diperbarui.';
            $message_type = 'success';
            $kantin = kk_fetch_one($koneksi, "SELECT * FROM kantin WHERE id_kantin=$id_kantin LIMIT 1");
        } else {
            $message = 'Gagal memperbarui kantin: ' . mysqli_error($koneksi);
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
    <title>Edit Kantin - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF9F8',
                        'primary-orange': '#E25E3E',
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
            <h2 class="text-3xl font-extrabold">Edit Kantin</h2>
            <p class="text-slate-500">Perbarui informasi kantin dan lokasi.</p>
        </div>
        <a href="manajemen_kantin.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700">Kembali</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl <?= $message_type==='success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-3xl shadow p-6 grid gap-5 max-w-3xl">
        <div>
            <label class="block text-sm font-bold mb-2">Nama Kantin</label>
            <input name="nama_kantin" value="<?= htmlspecialchars($kantin['nama_kantin']) ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Lokasi / Nomor Stand</label>
            <input name="lokasi" value="<?= htmlspecialchars($kantin['lokasi'] ?? '') ?>" class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Deskripsi Singkat</label>
            <textarea name="deskripsi" rows="4" class="w-full px-4 py-3 border rounded-2xl"><?= htmlspecialchars($kantin['deskripsi'] ?? '') ?></textarea>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_kantin.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
