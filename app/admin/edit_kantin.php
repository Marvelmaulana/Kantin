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

$id_kantin = admin_validate_id($_GET['id'] ?? 0);
if (!$id_kantin) {
    header('Location: manajemen_kantin.php?error=Data+tidak+valid');
    exit();
}

try {
    $kantin = admin_query_fetch_one($koneksi, 
        "SELECT * FROM kantin WHERE id_kantin = ? LIMIT 1", 
        [$id_kantin], 'i');
    
    if (!$kantin) {
        header('Location: manajemen_kantin.php?error=Kantin+tidak+ditemukan');
        exit();
    }
    
    // Get list of available sellers
    $penjual_list = admin_query_fetch_all($koneksi,
        "SELECT u.id_user, u.username FROM users u 
         LEFT JOIN kantin k ON k.id_user = u.id_user 
         WHERE u.role = 'penjual' AND (k.id_user IS NULL OR u.id_user = ?) 
         ORDER BY u.username ASC",
        [$kantin['id_user'] ?? 0], 'i');
} catch (Exception $e) {
    header('Location: manajemen_kantin.php?error=' . urlencode($e->getMessage()));
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
        $nama_kantin = admin_validate_string($_POST['nama_kantin'] ?? '', 1, 150);
        $deskripsi = admin_validate_string($_POST['deskripsi'] ?? '', 0, 500, true);
        $owner_id = !empty($_POST['owner_id']) ? admin_validate_id($_POST['owner_id']) : null;
        
        // Input validation
        if (!$nama_kantin) {
            $message = 'Nama kantin tidak boleh kosong (1-150 karakter).';
            $message_type = 'error';
        } else {
            try {
                // Verify owner if selected
                if ($owner_id) {
                    $owner_check = admin_query_fetch_one($koneksi,
                        "SELECT id_user FROM users WHERE id_user = ? AND role = 'penjual' LIMIT 1",
                        [$owner_id], 'i');
                    
                    if (!$owner_check) {
                        $message = 'Pemilik tidak valid atau bukan penjual.';
                        $message_type = 'error';
                    }
                }
                
                if ($message_type !== 'error') {
                    $old_owner_id = (int)($kantin['id_user'] ?? 0);
                    
                    // Update kantin
                    admin_execute_transaction($koneksi, function($koneksi) use ($id_kantin, $nama_kantin, $deskripsi, $owner_id, $old_owner_id) {
                        admin_query_execute($koneksi,
                            "UPDATE kantin SET nama_kantin = ?, deskripsi = ?, id_user = ? WHERE id_kantin = ?",
                            [$nama_kantin, $deskripsi, $owner_id, $id_kantin], 'ssii');
                        
                        // Update old owner if changed
                        if ($old_owner_id && $old_owner_id !== $owner_id) {
                            admin_query_execute($koneksi,
                                "UPDATE users SET id_kantin = NULL, nama_kantin = NULL WHERE id_user = ? AND role = 'penjual'",
                                [$old_owner_id], 'i');
                        }
                        
                        // Update new owner
                        if ($owner_id) {
                            admin_query_execute($koneksi,
                                "UPDATE users SET id_kantin = ?, nama_kantin = ? WHERE id_user = ? AND role = 'penjual'",
                                [$id_kantin, $nama_kantin, $owner_id], 'isi');
                        }
                        
                        // Log action
                        admin_log_action($koneksi, $_SESSION['id_user'], 'UPDATE', 'kantin', $id_kantin, 
                            "Updated kantin: $nama_kantin, owner: $owner_id");
                    });
                    
                    $message = 'Data kantin berhasil diperbarui.';
                    $message_type = 'success';
                    
                    // Reload data
                    $kantin = admin_query_fetch_one($koneksi,
                        "SELECT * FROM kantin WHERE id_kantin = ? LIMIT 1",
                        [$id_kantin], 'i');
                }
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
<body class="text-slate-800 flex overflow-x-hidden">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold">Edit Kantin</h2>
            <p class="text-slate-500">Perbarui informasi kantin dan pemilik.</p>
        </div>
        <a href="manajemen_kantin.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700">Kembali</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl <?= $message_type==='success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-3xl shadow p-6 grid gap-5 max-w-3xl">
        <?= admin_csrf_token_field() ?>
        
        <div>
            <label class="block text-sm font-bold mb-2">Nama Kantin</label>
            <input type="text" name="nama_kantin" value="<?= htmlspecialchars($kantin['nama_kantin'] ?? '') ?>" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl focus:border-primary-orange focus:ring-primary-orange/20 outline-none" />
        </div>
        
        <div>
            <label class="block text-sm font-bold mb-2">Pemilik Kantin</label>
            <select name="owner_id" class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white focus:border-primary-orange outline-none">
                <option value="">Pilih pemilik penjual</option>
                <?php foreach ($penjual_list as $p): ?>
                    <option value="<?= (int)$p['id_user'] ?>" <?= ((int)($kantin['id_user'] ?? 0) === (int)$p['id_user']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-2">Pemilik ini akan menjadi akun penjual yang mengelola kantin.</p>
        </div>
        
        <div>
            <label class="block text-sm font-bold mb-2">Deskripsi Singkat</label>
            <textarea name="deskripsi" rows="4" class="w-full px-4 py-3 border border-slate-200 rounded-2xl focus:border-primary-orange focus:ring-primary-orange/20 outline-none"><?= htmlspecialchars($kantin['deskripsi'] ?? '') ?></textarea>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_kantin.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold hover:bg-orange-600 transition">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
