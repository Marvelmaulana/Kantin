<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$message_type = 'success';

$kantins = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin WHERE id_user IS NULL OR id_user=0 ORDER BY nama_kantin ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $id_kantin = intval($_POST['id_kantin'] ?? 0);

    if ($username === '' || $email === '' || $password === '') {
        $message = 'Semua kolom wajib diisi.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $message_type = 'error';
    } else {
        $exists = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username' OR email='$email' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            $message = 'Username atau email sudah terdaftar.';
            $message_type = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = mysqli_query($koneksi, "INSERT INTO users (username,email,password,role,id_kantin) VALUES ('$username','$email','$hash','penjual'," . ($id_kantin>0?$id_kantin:'NULL') . ")");
            if ($ins) {
                $new_id = mysqli_insert_id($koneksi);
                if ($id_kantin > 0) {
                    mysqli_query($koneksi, "UPDATE kantin SET id_user=$new_id WHERE id_kantin=$id_kantin");
                    mysqli_query($koneksi, "UPDATE users SET nama_kantin=(SELECT nama_kantin FROM kantin WHERE id_kantin=$id_kantin) WHERE id_user=$new_id");
                }
                $message = 'Penjual berhasil ditambahkan.';
                $message_type = 'success';
                $_POST = [];
            } else {
                $message = 'Gagal menambahkan penjual: ' . mysqli_error($koneksi);
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
    <title>Tambah Penjual - Admin</title>
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
<body class="text-slate-800 flex">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 w-full lg:ml-72 p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold">Tambah Penjual</h2>
            <p class="text-slate-500">Buat akun penjual baru dan tautkan ke stand yang tersedia.</p>
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
            <input name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div>
            <label class="block text-sm font-bold mb-2">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required class="w-full px-4 py-3 border rounded-2xl" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Password</label>
                <input name="password" type="password" required class="w-full px-4 py-3 border rounded-2xl" />
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Kantin</label>
                <select name="id_kantin" class="w-full px-4 py-3 border rounded-2xl">
                    <option value="0">Pilih kantin (opsional)</option>
                    <?php while ($k = mysqli_fetch_assoc($kantins)): ?>
                    <option value="<?= $k['id_kantin'] ?>" <?= (int)($_POST['id_kantin'] ?? 0) === (int)$k['id_kantin'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kantin']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="manajemen_penjual.php" class="px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-center">Batal</a>
            <button type="submit" class="px-4 py-3 rounded-2xl bg-primary-orange text-white font-bold">Simpan Penjual</button>
        </div>
    </form>
</main>
</body>
</html>
