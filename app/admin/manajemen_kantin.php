<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Initialize CSRF
admin_init_csrf_token();

$message = '';
$message_type = 'success';
if (isset($_GET['success']) && $_GET['success'] === 'hapus') {
    $message = 'Kantin berhasil dihapus.';
} elseif (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

// Pagination
$per_page = 10;
$page = admin_validate_id($_GET['page'] ?? 1) ?: 1;

// Search with prepared statement
$search = admin_validate_string($_GET['search'] ?? '', 0);
$search_where = '';
$search_params = [];
$search_types = '';

if ($search) {
    $search_where = " WHERE (k.nama_kantin LIKE ? OR k.deskripsi LIKE ? OR u.username LIKE ?)";
    $search_term = '%' . $search . '%';
    $search_params = [$search_term, $search_term, $search_term];
    $search_types = 'sss';
}

// Count total records
try {
    $count_sql = "SELECT COUNT(*) as total FROM kantin k LEFT JOIN users u ON k.id_user=u.id_user" . $search_where;
    $total_kantin = admin_query_count($koneksi, $count_sql, $search_params, $search_types);
} catch (Exception $e) {
    $total_kantin = 0;
}

// Get pagination info
$paging = admin_pagination_calc($total_kantin, $per_page, $page);
$limit_params = array_merge($search_params, [$paging['offset'], $per_page]);
$limit_types = $search_types . 'ii';

// Fetch kantin with pagination
try {
    $sql = "SELECT k.*, u.username AS pemilik FROM kantin k LEFT JOIN users u ON k.id_user=u.id_user" . 
           $search_where . " ORDER BY k.id_kantin DESC LIMIT ? OFFSET ?";
    $kantins = admin_query_fetch_all($koneksi, $sql, $limit_params, $limit_types);
} catch (Exception $e) {
    $kantins = [];
    $message = 'Error loading data: ' . htmlspecialchars($e->getMessage());
    $message_type = 'error';
}
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
<body class="text-slate-800 flex overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-extrabold">Daftar Kantin</h2>
            <p class="text-sm text-slate-500">Kelola data kantin yang terdaftar di sistem.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <form method="GET" class="relative w-full sm:w-[320px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kantin atau pemilik..." class="pl-12 pr-4 py-3 w-full rounded-2xl border border-slate-200 focus:border-primary-orange focus:ring-primary-orange/20" />
            </form>
            <a href="tambah_kantin.php" class="bg-primary-orange text-white px-4 py-2 rounded-2xl font-bold shadow-lg hover:bg-orange-600 transition-all">Tambah Kantin</a>
        </div>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow p-6 overflow-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold">Total: <?= $total_kantin ?> Kantin</h3>
        </div>
        
        <?php if (count($kantins) === 0): ?>
            <div class="text-center py-20 text-slate-500">
                <p class="text-lg font-bold text-slate-700">Belum ada kantin tersedia.</p>
                <p class="mt-2 text-sm">Tambahkan kantin baru menggunakan tombol di atas.</p>
            </div>
        <?php else: ?>
        <table class="w-full text-left border-collapse min-w-[700px]">
            <thead>
                <tr class="text-sm text-slate-500 uppercase border-b">
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Nama Kantin</th>
                    <th class="py-3 px-4">Pemilik</th>
                    <th class="py-3 px-4">Deskripsi</th>
                    <th class="py-3 px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kantins as $k): ?>
                <tr class="border-b hover:bg-slate-50 transition-all">
                    <td class="py-4 px-4">#<?= htmlspecialchars($k['id_kantin']) ?></td>
                    <td class="py-4 px-4 font-bold"><?= htmlspecialchars($k['nama_kantin']) ?></td>
                    <td class="py-4 px-4"><?= htmlspecialchars($k['pemilik'] ?: '-') ?></td>
                    <td class="py-4 px-4 text-slate-500 truncate"><?= htmlspecialchars(substr($k['deskripsi'] ?? '-', 0, 50)) ?></td>
                    <td class="py-4 px-4">
                        <div class="flex flex-wrap gap-2">
                            <a href="edit_kantin.php?id=<?= (int)$k['id_kantin'] ?>" class="px-3 py-2 rounded-2xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-primary-orange hover:text-white transition">Edit</a>
                            <form method="POST" action="proses_hapus_kantin.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= (int)$k['id_kantin'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="px-3 py-2 rounded-2xl bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-600 hover:text-white transition" onclick="return confirm('Yakin ingin hapus kantin &quot;<?= htmlspecialchars($k['nama_kantin']) ?>&quot;?')">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($paging['total_pages'] > 1): ?>
        <div class="mt-6 flex justify-center gap-2 items-center">
            <?php if ($paging['has_prev']): ?>
                <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">&laquo;</a>
                <a href="?page=<?= $paging['prev_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">&lsaquo;</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $paging['total_pages']; $i++): ?>
                <?php if ($i == $paging['current_page']): ?>
                    <span class="px-3 py-2 rounded-lg bg-primary-orange text-white font-bold"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-100"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($paging['has_next']): ?>
                <a href="?page=<?= $paging['next_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">&rsaquo;</a>
                <a href="?page=<?= $paging['total_pages'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
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
