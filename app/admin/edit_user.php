<?php
session_start();
include(__DIR__ . '/../../config/config.php');
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = (int)($_GET['id'] ?? 0);
if ($id_user <= 0) {
    header('Location: manajemen_user.php?error=Data+tidak+valid');
    exit();
}

$user = kk_fetch_one($koneksi, "SELECT * FROM users WHERE id_user=$id_user AND role='pembeli' LIMIT 1");
if (!$user) {
    header('Location: manajemen_user.php?error=User+tidak+ditemukan');
    exit();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $kelas = mysqli_real_escape_string($koneksi, trim($_POST['kelas'] ?? ''));

    if ($username === '' || $email === '') {
        $message = 'Username dan email wajib diisi.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $message_type = 'error';
    } else {
        $exists = mysqli_query($koneksi, "SELECT id_user FROM users WHERE (username='$username' OR email='$email') AND id_user<>$id_user LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $message = 'Username atau email sudah dipakai oleh user lain.';
            $message_type = 'error';
        } else {
            $kelas_sql = $kelas !== '' ? "kelas='$kelas'" : "kelas=NULL";
            $updated = mysqli_query($koneksi, "UPDATE users SET username='$username', email='$email', $kelas_sql WHERE id_user=$id_user AND role='pembeli'");
            if ($updated) {
                $message = 'Data user berhasil diperbarui.';
                $message_type = 'success';
                $user = kk_fetch_one($koneksi, "SELECT * FROM users WHERE id_user=$id_user LIMIT 1");
            } else {
                $message = 'Gagal memperbarui user: ' . mysqli_error($koneksi);
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
    <title>Edit User - Admin</title>
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
            <h2 class="text-3xl font-extrabold">Edit User Pembeli</h2>
            <p class="text-slate-500">Perbarui informasi pelanggan.</p>
        </div>
        <a href="manajemen_user.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-slate-700">Kembali</a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl <?= $message_type==='success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-3xl shadow p-6 grid gap-5 max-w-3xl">
        <div>
            <label class="block text-sm font-bold mb-2">Username</label>
            <input name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Kelas</label>
            <input name="kelas" value="<?= htmlspecialchars($user['kelas']) ?>" placeholder="10 / 11 / 12" class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_user.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold">Simpan Perubahan</button>
        </div>
    </form>
</main>
</body>
</html>
