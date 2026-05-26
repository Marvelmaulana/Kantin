<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/student_helpers.php');

kk_ensure_buyer_schema($koneksi);

// Proteksi: hanya admin yang bisa akses
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil filter kelas dari query string
$filter_kelas = $_GET['kelas'] ?? null;
if ($filter_kelas && !validate_kelas($filter_kelas)) {
    $filter_kelas = null;
}

// Ambil daftar siswa
$siswa_list = get_siswa_list($koneksi, $filter_kelas, 'kelas DESC, username ASC');
$total_siswa = get_siswa_count($koneksi);
$siswa_per_kelas = get_total_students_by_class($koneksi);

$kelas_options = get_kelas_options();

// Success message
$success_message = '';
if (isset($_GET['success'])) {
    $success_code = $_GET['success'];
    if ($success_code === 'delete') {
        $success_message = 'Siswa berhasil dihapus';
    } elseif ($success_code === 'promote') {
        $success_message = 'Kelas siswa berhasil dinaikkan';
    } elseif ($success_code === 'promote_all') {
        $success_message = 'Kenaikan kelas otomatis berhasil dilakukan';
    } elseif ($success_code === 'delete_kelas_12') {
        $deleted = $_GET['deleted'] ?? 0;
        $success_message = "Berhasil menghapus $deleted siswa kelas 12";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manajemen Siswa & Kelas - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8f7f5; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="text-stone-800">

<header class="bg-white/80 backdrop-blur-xl sticky top-0 z-40 shadow-sm border-b border-stone-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="dashboard_admin.php" class="p-2 rounded-lg hover:bg-stone-100 text-stone-400">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold text-stone-900">Manajemen Siswa & Kelas</h1>
                <p class="text-xs text-stone-500">Total Siswa: <span class="font-bold"><?= number_format($total_siswa) ?></span></p>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- Success Alert -->
    <?php if ($success_message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
        <span class="material-symbols-outlined text-green-600 mt-0.5">check_circle</span>
        <div>
            <h3 class="font-bold text-green-900">Berhasil!</h3>
            <p class="text-sm text-green-700"><?= htmlspecialchars($success_message) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistik Kelas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="glass rounded-xl p-6 border border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-stone-600">Total Siswa</p>
                    <p class="text-3xl font-bold text-stone-900"><?= number_format($total_siswa) ?></p>
                </div>
                <span class="material-symbols-outlined text-4xl text-stone-300">people</span>
            </div>
        </div>

        <?php foreach ($siswa_per_kelas as $stat): ?>
        <div class="glass rounded-xl p-6 border border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-stone-600"><?= htmlspecialchars($stat['label']) ?></p>
                    <p class="text-3xl font-bold text-stone-900"><?= number_format($stat['count']) ?></p>
                </div>
                <span class="material-symbols-outlined text-4xl text-blue-300">school</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!-- Kenaikan Kelas Otomatis -->
        <button onclick="if(confirm('Apakah Anda yakin? Proses ini akan:\n- Menghapus semua siswa kelas 12\n- Naikkan siswa kelas 11 → 12\n- Naikkan siswa kelas 10 → 11')) {
            location.href='proses_naikkan_semua_kelas.php';
        }" class="p-6 rounded-xl glass border border-white/50 hover:border-orange-300 transition-all text-left hover:shadow-lg">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <span class="material-symbols-outlined text-orange-600 text-2xl">trending_up</span>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900">Kenaikan Kelas Otomatis</h3>
                    <p class="text-xs text-stone-500 mt-1">Naikkan semua siswa ke kelas berikutnya</p>
                </div>
            </div>
        </button>

        <!-- Hapus Semua Siswa Kelas 12 -->
        <button onclick="if(confirm('Apakah Anda yakin ingin menghapus SEMUA siswa kelas 12? Tindakan ini tidak dapat dibatalkan.')) {
            location.href='proses_hapus_siswa_kelas_12.php?action=delete_all';
        }" class="p-6 rounded-xl glass border border-white/50 hover:border-red-300 transition-all text-left hover:shadow-lg">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-100 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-2xl">delete_sweep</span>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900">Hapus Semua Kelas 12</h3>
                    <p class="text-xs text-stone-500 mt-1">Hapus semua akun siswa kelas 12</p>
                </div>
            </div>
        </button>

        <!-- Syarat & Ketentuan -->
        <a href="syarat_ketentuan.php" class="p-6 rounded-xl glass border border-white/50 hover:border-blue-300 transition-all text-left hover:shadow-lg">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">description</span>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900">Syarat & Ketentuan</h3>
                    <p class="text-xs text-stone-500 mt-1">Kelola syarat dan ketentuan aplikasi</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Filter dan Daftar Siswa -->
    <div class="glass rounded-xl border border-white/50 overflow-hidden">
        <div class="p-6 border-b border-white/50">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Daftar Siswa</h2>
                <div class="flex gap-3">
                    <select id="filterKelas" onchange="location.href='?kelas=' + this.value" class="px-3 py-2 rounded-lg bg-stone-100 border-none text-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_options as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['value']) ?>" <?= ($filter_kelas === $opt['value']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($opt['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabel Siswa -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-stone-50">
                    <tr class="border-b border-stone-200">
                        <th class="px-6 py-3 text-left text-xs font-bold text-stone-600">Nama Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-stone-600">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-stone-600">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-stone-600">Terdaftar</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-stone-600">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($siswa_list) > 0): ?>
                        <?php foreach ($siswa_list as $siswa): ?>
                        <tr class="border-b border-stone-100 hover:bg-stone-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-stone-900">
                                <?= htmlspecialchars($siswa['username']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-stone-600">
                                <?= htmlspecialchars($siswa['email']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">
                                    <span class="material-symbols-outlined text-sm">school</span>
                                    <?= get_kelas_label($siswa['kelas']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-stone-500">
                                <?= date('d M Y', strtotime($siswa['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Naikkan Kelas -->
                                    <?php if ($siswa['kelas'] !== '12'): ?>
                                    <button onclick="if(confirm('Naikkan <?= addslashes($siswa['username']) ?> dari <?= get_kelas_label($siswa['kelas']) ?>?')) {
                                        location.href='proses_naikkan_kelas.php?id=<?= $siswa['id_user'] ?>';
                                    }" class="px-3 py-1 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 text-xs font-medium transition-colors">
                                        Naikkan
                                    </button>
                                    <?php else: ?>
                                    <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-500 text-xs font-medium italic">
                                        Kelas Akhir
                                    </span>
                                    <?php endif; ?>

                                    <!-- Hapus Siswa -->
                                    <button onclick="if(confirm('Yakin hapus siswa <?= addslashes($siswa['username']) ?>?')) {
                                        location.href='proses_hapus_siswa_kelas_12.php?action=delete_single&id=<?= $siswa['id_user'] ?>';
                                    }" class="px-3 py-1 rounded-lg bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-stone-500">
                            <span class="material-symbols-outlined text-4xl opacity-20">person_outline</span>
                            <p class="mt-2">Tidak ada data siswa</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php include(__DIR__ . '/../../includes/sidebar_admin.php'); ?>

</body>
</html>
