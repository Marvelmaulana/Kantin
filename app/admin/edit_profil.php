<?php
/**
 * Edit Profil Admin
 * Halaman untuk admin mengedit profil mereka sendiri
 */

session_start();

// SECURITY: Session & Role Validation
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// SECURITY: Session Timeout Check (1 hour)
$session_timeout = 3600; // 1 hour in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    session_destroy();
    header("Location: ../auth/login.php?reason=timeout");
    exit();
}

// Update last activity time
$_SESSION['login_time'] = time();


include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

$message = '';
$message_type = '';
$admin_id = $_SESSION['id_user'];

// Fetch current admin data
$query = "SELECT id_user, username, email, nama_lengkap, profil FROM users WHERE id_user = ? LIMIT 1";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$admin_data) {
    header("Location: dashboard_admin.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : $admin_data['nama_lengkap'];
    
    // Validate nama_lengkap
    if (empty($nama_lengkap)) {
        $message = 'Nama lengkap tidak boleh kosong!';
        $message_type = 'error';
    } else if (strlen($nama_lengkap) > 100) {
        $message = 'Nama lengkap terlalu panjang (maksimal 100 karakter)!';
        $message_type = 'error';
    } else {
        // SECURITY: Use prepared statement
        $update_query = "UPDATE users SET nama_lengkap = ? WHERE id_user = ? LIMIT 1";
        $update_stmt = mysqli_prepare($koneksi, $update_query);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $nama_lengkap, $admin_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = 'Profil berhasil diperbarui!';
                $message_type = 'success';
                $admin_data['nama_lengkap'] = $nama_lengkap;
            } else {
                $message = 'Gagal memperbarui profil: ' . mysqli_error($koneksi);
                $message_type = 'error';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $message = 'Kesalahan database. Silakan coba lagi.';
            $message_type = 'error';
        }
    }
}

// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $password_lama = isset($_POST['password_lama']) ? $_POST['password_lama'] : '';
    $password_baru = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
    $password_baru_konfirm = isset($_POST['password_baru_konfirm']) ? $_POST['password_baru_konfirm'] : '';

    if (empty($password_lama) || empty($password_baru) || empty($password_baru_konfirm)) {
        $message = 'Semua field password harus diisi!';
        $message_type = 'error';
    } else if (!password_verify($password_lama, $admin_data['password'])) {
        $message = 'Password lama tidak sesuai!';
        $message_type = 'error';
    } else if ($password_baru !== $password_baru_konfirm) {
        $message = 'Password baru tidak cocok!';
        $message_type = 'error';
    } else if (strlen($password_baru) < 6) {
        $message = 'Password baru minimal 6 karakter!';
        $message_type = 'error';
    } else {
        // Hash password
        $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        
        // SECURITY: Use prepared statement
        $pwd_query = "UPDATE users SET password = ? WHERE id_user = ? LIMIT 1";
        $pwd_stmt = mysqli_prepare($koneksi, $pwd_query);
        
        if ($pwd_stmt) {
            mysqli_stmt_bind_param($pwd_stmt, "si", $password_hashed, $admin_id);
            
            if (mysqli_stmt_execute($pwd_stmt)) {
                $message = 'Password berhasil diubah!';
                $message_type = 'success';
            } else {
                $message = 'Gagal mengubah password: ' . mysqli_error($koneksi);
                $message_type = 'error';
            }
            mysqli_stmt_close($pwd_stmt);
        } else {
            $message = 'Kesalahan database. Silakan coba lagi.';
            $message_type = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.profile_title', 'Edit Profil - Admin') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
</head>
<body class="bg-gray-50">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="lg:ml-64 p-4 md:p-6 lg:p-8 min-h-screen">
    
    <!-- Header -->
    <div class="mb-8 mt-14 lg:mt-0">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Edit Profil</h1>
        <p class="text-gray-600 mt-2">Kelola informasi dan keamanan akun admin Anda</p>
    </div>

    <!-- Message Alert -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?= ($message_type === 'success') ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined <?= ($message_type === 'success') ? 'text-green-600' : 'text-red-600' ?>">
                <?= ($message_type === 'success') ? 'check_circle' : 'error' ?>
            </span>
            <p class="<?= ($message_type === 'success') ? 'text-green-800' : 'text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Sidebar Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-4xl text-white">account_circle</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($admin_data['username']) ?></h2>
                    <p class="text-sm text-gray-600 mt-1">Admin Account</p>
                </div>
                <div class="border-t pt-4 text-sm text-gray-600 space-y-2">
                    <p><span class="font-semibold">Email:</span> <?= htmlspecialchars($admin_data['email']) ?></p>
                    <p><span class="font-semibold">ID User:</span> <?= htmlspecialchars($admin_data['id_user']) ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
            
            <!-- Edit Profile Form -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">person</span>
                    Informasi Profil
                </h3>
                
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="update_profil" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" value="<?= htmlspecialchars($admin_data['username']) ?>" disabled 
                               class="w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-600 border border-gray-300 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" value="<?= htmlspecialchars($admin_data['email']) ?>" disabled 
                               class="w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-600 border border-gray-300 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($admin_data['nama_lengkap'] ?? '') ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Masukkan nama lengkap Anda">
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        <span class="material-symbols-outlined align-middle mr-2">save</span>
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">lock</span>
                    Ubah Password
                </h3>
                
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                        <input type="password" name="password_lama" required 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Masukkan password lama Anda">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="password_baru" required 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Masukkan password baru (minimal 6 karakter)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="password_baru_konfirm" required 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Konfirmasi password baru Anda">
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        <p class="font-semibold mb-1">Catatan Keamanan:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Password minimal 6 karakter</li>
                            <li>Gunakan kombinasi huruf, angka, dan simbol</li>
                            <li>Jangan bagikan password Anda</li>
                        </ul>
                    </div>

                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        <span class="material-symbols-outlined align-middle mr-2">vpn_key</span>
                        Ubah Password
                    </button>
                </form>
            </div>

        </div>

    </div>

    <!-- Back Button -->
    <div class="mt-8">
        <a href="dashboard_admin.php" class="text-orange-600 hover:text-orange-700 font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">arrow_back</span>
            Kembali ke Dashboard
        </a>
    </div>

</main>

</body>
</html>

