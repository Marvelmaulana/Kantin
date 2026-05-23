<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$message_type = 'success';
if (isset($_GET['success']) && $_GET['success'] === 'hapus') {
    $message = 'Kantin berhasil dihapus.';
} elseif (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

$query = mysqli_query($koneksi, "SELECT k.*, u.username AS pemilik FROM kantin k LEFT JOIN users u ON k.id_user=u.id_user ORDER BY k.id_kantin DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manajemen Kantin - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-orange': '#E25E3E'
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
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-extrabold">Daftar Kantin</h2>
            <p class="text-sm text-slate-500">Kelola data kantin yang terdaftar di sistem.</p>
        </div>
        <a href="tambah_kantin.php" class="bg-primary-orange text-white px-4 py-2 rounded-2xl font-bold shadow-lg hover:bg-orange-600 transition-all">Tambah Kantin</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow p-6 overflow-auto">
        <?php if (mysqli_num_rows($query) === 0): ?>
            <div class="text-center py-20 text-slate-500">
                <p class="text-lg font-bold text-slate-700">Belum ada kantin tersedia.</p>
                <p class="mt-2 text-sm">Tambahkan kantin baru menggunakan tombol di atas.</p>
            </div>
        <?php else: ?>
        <table class="w-full text-left border-collapse min-w-[700px]">
            <thead>
                <tr class="text-sm text-slate-500 uppercase">
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Nama Kantin</th>
                    <th class="py-3 px-4">Lokasi</th>
                    <th class="py-3 px-4">Pemilik</th>
                    <th class="py-3 px-4">Deskripsi</th>
                    <th class="py-3 px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($k = mysqli_fetch_assoc($query)): ?>
                <tr class="border-t hover:bg-slate-50 transition-all">
                    <td class="py-4 px-4">#<?= $k['id_kantin'] ?></td>
                    <td class="py-4 px-4 font-bold"><?= htmlspecialchars($k['nama_kantin']) ?></td>
                    <td class="py-4 px-4"><?= htmlspecialchars($k['lokasi'] ?: '-') ?></td>
                    <td class="py-4 px-4"><?= htmlspecialchars($k['pemilik'] ?: '-') ?></td>
                    <td class="py-4 px-4 text-slate-500"><?= htmlspecialchars($k['deskripsi'] ?: '-') ?></td>
                    <td class="py-4 px-4">
                        <div class="flex flex-wrap gap-2">
                            <a href="edit_kantin.php?id=<?= $k['id_kantin'] ?>" class="px-3 py-2 rounded-2xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-primary-orange hover:text-white transition">Edit</a>
                            <a href="proses_hapus_kantin.php?id=<?= $k['id_kantin'] ?>" onclick="return confirm('Hapus kantin ini?')" class="px-3 py-2 rounded-2xl bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-600 hover:text-white transition">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
