<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = (int)($_GET['id'] ?? 0);
if ($id_user <= 0) {
    header('Location: manajemen_penjual.php?error=Data+tidak+valid');
    exit();
}

$penjual = kk_fetch_one($koneksi, "SELECT * FROM users WHERE id_user=$id_user AND role='penjual' LIMIT 1");
if (!$penjual) {
    header('Location: manajemen_penjual.php?error=Penjual+tidak+ditemukan');
    exit();
}

$message = '';
$message_type = 'success';
$kantins = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $id_kantin = intval($_POST['id_kantin'] ?? 0);
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '') {
        $message = 'Username dan email wajib diisi.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $message_type = 'error';
    } elseif (!preg_match('/@gmail\.com$/', $email)) {
        $message = 'Email harus berakhir dengan @gmail.com';
        $message_type = 'error';
    } else {
        $exists = mysqli_query($koneksi, "SELECT id_user FROM users WHERE (username='$username' OR email='$email') AND id_user<>$id_user LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $message = 'Username atau email sudah dipakai oleh user lain.';
            $message_type = 'error';
        } else {
            mysqli_begin_transaction($koneksi);
            try {
                // Update users dengan id_kantin dan nama_kantin yang baru
                $set_password = '';
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $set_password = ", password='$hash'";
                }
                $nama_kantin_sql = $id_kantin > 0 ? "(SELECT nama_kantin FROM kantin WHERE id_kantin=$id_kantin)" : 'NULL';
                $updated = mysqli_query($koneksi, "UPDATE users SET username='$username', email='$email', id_kantin=" . ($id_kantin>0?$id_kantin:'NULL') . ", nama_kantin=$nama_kantin_sql $set_password WHERE id_user=$id_user AND role='penjual'");
                if (!$updated) {
                    throw new Exception(mysqli_error($koneksi));
                }
                mysqli_commit($koneksi);
                $message = 'Data penjual berhasil diperbarui.';
                $message_type = 'success';
                $penjual = kk_fetch_one($koneksi, "SELECT * FROM users WHERE id_user=$id_user LIMIT 1");
            } catch (Throwable $e) {
                mysqli_rollback($koneksi);
                $message = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Penjual - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF9F8',
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
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold">Edit Penjual</h2>
            <p class="text-slate-500">Perbarui akun penjual dan tautkan ke kantin yang tersedia.</p>
        </div>
        <a href="manajemen_penjual.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700">Kembali</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-3xl shadow p-6 max-w-3xl grid gap-6">
        <div>
            <label class="block text-sm font-bold mb-2">Username</label>
            <input name="username" value="<?= htmlspecialchars($_POST['username'] ?? $penjual['username']) ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? $penjual['email']) ?>" placeholder="contoh@gmail.com" pattern=".*@gmail\.com$" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Password Baru</label>
                <input name="password" type="password" placeholder="Kosongkan jika tidak ingin mengubah" class="w-full px-4 py-3 border rounded-2xl" />
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Kantin</label>
                <select name="id_kantin" class="w-full px-4 py-3 border rounded-2xl">
                    <option value="0">Tidak Ada</option>
                    <?php while ($k = mysqli_fetch_assoc($kantins)): ?>
                    <option value="<?= $k['id_kantin'] ?>" <?= ((int)($penjual['id_kantin']) === (int)$k['id_kantin']) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kantin']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_penjual.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
