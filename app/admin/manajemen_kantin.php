<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
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
</head>
<body class="text-slate-800 flex">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold">Daftar Kantin</h2>
            <p class="text-sm text-slate-500">Kelola data kantin yang terdaftar di sistem.</p>
        </div>
        <a href="tambah_kantin.php" class="bg-primary-orange text-white px-4 py-2 rounded-2xl font-bold">Tambah Kantin</a>
    </header>

    <div class="bg-white rounded-2xl shadow p-6 overflow-auto">
        <table class="w-full text-left border-collapse min-w-[700px]">
            <thead>
                <tr class="text-sm text-slate-500 uppercase">
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Nama Kantin</th>
                    <th class="py-3 px-4">Lokasi</th>
                    <th class="py-3 px-4">Pemilik</th>
                    <th class="py-3 px-4">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($k = mysqli_fetch_assoc($query)): ?>
                <tr class="border-t">
                    <td class="py-4 px-4">#<?= $k['id_kantin'] ?></td>
                    <td class="py-4 px-4 font-bold"><?= htmlspecialchars($k['nama_kantin']) ?></td>
                    <td class="py-4 px-4"><?= htmlspecialchars($k['lokasi'] ?: '-') ?></td>
                    <td class="py-4 px-4"><?= htmlspecialchars($k['pemilik'] ?: '-') ?></td>
                    <td class="py-4 px-4 text-slate-500"><?= htmlspecialchars($k['deskripsi'] ?: '-') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
