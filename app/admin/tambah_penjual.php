<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$message_type = 'success';

// Ambil semua kantin dengan jumlah penjual yang sudah ada (hanya yang < 5 penjual)
$kantins = mysqli_query($koneksi, "SELECT k.id_kantin, k.nama_kantin, k.deskripsi, k.logo, k.jam_buka, k.jam_tutup, k.status_buka, k.rating, k.total_rating, COUNT(u.id_user) as jumlah_penjual FROM kantin k LEFT JOIN users u ON k.id_kantin = u.id_kantin AND u.role = 'penjual' GROUP BY k.id_kantin HAVING jumlah_penjual < 5 ORDER BY k.nama_kantin ASC");

if (!$kantins) {
    die('Error: ' . mysqli_error($koneksi));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $id_kantin = intval($_POST['id_kantin'] ?? 0);

    if ($username === '' || $email === '' || $password === '') {
        $message = 'Semua kolom wajib diisi.';
        $message_type = 'error';
    } elseif (preg_match('/\s/', $username)) {
        $message = 'Username tidak boleh mengandung spasi.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $message_type = 'error';
    } elseif (!preg_match('/@gmail\.com$/', $email)) {
        $message = 'Email harus berakhir dengan @gmail.com';
        $message_type = 'error';
    } elseif (!$id_kantin || $id_kantin <= 0) {
        $message = 'Anda wajib memilih Kantin untuk penjual ini.';
        $message_type = 'error';
    } else {
        // Validasi jumlah penjual yang sudah ada
        $check_penjual = mysqli_query($koneksi, "SELECT COUNT(id_user) as total FROM users WHERE id_kantin=$id_kantin AND role='penjual'");
        if (!$check_penjual) {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'error';
        } else {
            $row_count = mysqli_fetch_assoc($check_penjual);
            if (!$row_count || $row_count['total'] >= 5) {
                $message = 'Kantin ini sudah mencapai maksimal 5 penjual.';
                $message_type = 'error';
            } else {
                $exists = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username' OR email='$email' LIMIT 1");
                if (!$exists) {
                    $message = 'Error: ' . mysqli_error($koneksi);
                    $message_type = 'error';
                } elseif (mysqli_num_rows($exists) > 0) {
                    $message = 'Username atau email sudah terdaftar.';
                    $message_type = 'error';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = mysqli_query($koneksi, "INSERT INTO users (username,email,password,role,id_kantin) VALUES ('$username','$email','$hash','penjual',$id_kantin)");
                    if (!$ins) {
                        $message = 'Gagal menambahkan penjual: ' . mysqli_error($koneksi);
                        $message_type = 'error';
                    } else {
                        $new_id = mysqli_insert_id($koneksi);
                        // Update nama_kantin di users
                        $update = mysqli_query($koneksi, "UPDATE users SET nama_kantin=(SELECT nama_kantin FROM kantin WHERE id_kantin=$id_kantin) WHERE id_user=$new_id");
                        if (!$update) {
                            $message = 'Penjual berhasil ditambahkan, tapi gagal update nama kantin: ' . mysqli_error($koneksi);
                            $message_type = 'error';
                        } else {
                            // Redirect ke manajemen penjual setelah berhasil
                            header('Location: manajemen_penjual.php?success=1');
                            exit();
                        }
                    }
                }
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
    <title><?= t('admin.add_seller_title', 'Tambah Penjual - Admin') ?></title>
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

        function togglePassword() {
            const passwordField = document.getElementById('password_field');
            const passwordIcon = document.getElementById('password_icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.textContent = 'visibility_off';
            } else {
                passwordField.type = 'password';
                passwordIcon.textContent = 'visibility';
            }
        }

        function selectKantin(kantinId) {
            document.getElementById('id_kantin').value = kantinId;
            
            // Update visual feedback pada semua card
            document.querySelectorAll('[id^="kantin_"]').forEach(radio => {
                const label = document.querySelector(`label[for="${radio.id}"]`);
                const checkIcon = label.querySelector('.kantin-check');
                
                if (radio.id === `kantin_${kantinId}`) {
                    label.classList.remove('border-slate-200', 'bg-white', 'hover:border-slate-300');
                    label.classList.add('border-primary-orange', 'bg-orange-50', 'ring-2', 'ring-primary-orange', 'ring-offset-1');
                    if (checkIcon) checkIcon.style.display = 'block';
                } else {
                    label.classList.remove('border-primary-orange', 'bg-orange-50', 'ring-2', 'ring-primary-orange', 'ring-offset-1');
                    label.classList.add('border-slate-200', 'bg-white', 'hover:border-slate-300');
                    if (checkIcon) checkIcon.style.display = 'none';
                }
            });
        }

        // Initialize visual feedback saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const selectedId = document.getElementById('id_kantin').value;
            if (selectedId) {
                selectKantin(selectedId);
            }
        });
    </script>
</head>
<body class="text-slate-800 flex overflow-x-hidden">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 w-full lg:ml-72 p-3 sm:p-6 lg:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold"><?= t('admin.add_seller_heading', 'Tambah Penjual') ?></h2>
            <p class="text-sm sm:text-base text-slate-500 mt-1"><?= t('admin.add_seller_desc', 'Buat akun penjual baru dan tautkan ke stand yang tersedia.') ?></p>
        </div>
        <a href="manajemen_penjual.php" class="px-3 sm:px-4 py-2 rounded-2xl bg-slate-100 text-slate-700 text-sm sm:text-base whitespace-nowrap"><?= t('action.back', 'Kembali') ?></a>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-4 sm:mb-6 px-4 sm:px-5 py-3 sm:py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-3xl shadow p-4 sm:p-6 lg:p-8 max-w-4xl grid gap-4 sm:gap-6">
        <div>
            <label class="block text-sm sm:text-base font-bold mb-2">Username</label>
            <input name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" pattern="^\S+$" title="Username tidak boleh menggunakan spasi" required class="w-full px-4 py-3 sm:py-4 border rounded-2xl text-base" />
            <p class="text-xs sm:text-sm text-red-500 mt-2 font-semibold">*Username tidak boleh mengandung spasi</p>
        </div>
        <div>
            <label class="block text-sm sm:text-base font-bold mb-2">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="contoh@gmail.com" pattern=".*@gmail\.com$" required class="w-full px-4 py-3 sm:py-4 border rounded-2xl text-base" />
        </div>
        <div>
            <label class="block text-sm sm:text-base font-bold mb-2">Password</label>
            <div class="relative">
                <input name="password" type="password" id="password_field" required class="w-full px-4 py-3 sm:py-4 border rounded-2xl pr-12 text-base" />
                <button type="button" onclick="togglePassword()" class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-slate-600 hover:text-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[20px] sm:text-[24px]" id="password_icon">visibility</span>
                </button>
            </div>
        </div>
        <div>
            <label class="block text-sm sm:text-base font-bold mb-2">Pilih Kantin</label>
            <p class="text-xs sm:text-sm text-slate-500 mb-3 sm:mb-4">Pilih kantin yang akan dikelola oleh penjual ini:</p>
            <input type="hidden" name="id_kantin" id="id_kantin" value="<?= htmlspecialchars($_POST['id_kantin'] ?? '') ?>" required />
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-2 sm:gap-3 max-h-96 overflow-y-auto p-2 sm:p-3 border rounded-2xl bg-slate-50">
                <?php 
                $selected_kantin = $_POST['id_kantin'] ?? '';
                mysqli_data_seek($kantins, 0);
                while ($k = mysqli_fetch_assoc($kantins)): 
                    $is_selected = (int)$selected_kantin === (int)$k['id_kantin'];
                ?>
                <div class="relative">
                    <input type="radio" name="kantin_pilih" id="kantin_<?= $k['id_kantin'] ?>" value="<?= $k['id_kantin'] ?>" <?= $is_selected ? 'checked' : '' ?> onchange="selectKantin(<?= $k['id_kantin'] ?>)" class="sr-only" />
                    <label for="kantin_<?= $k['id_kantin'] ?>" onclick="event.preventDefault(); document.getElementById('kantin_<?= $k['id_kantin'] ?>').checked = true; selectKantin(<?= $k['id_kantin'] ?>)" class="block p-3 sm:p-4 rounded-2xl border-2 cursor-pointer transition-all <?= $is_selected ? 'border-primary-orange bg-orange-50 ring-2 ring-primary-orange ring-offset-1' : 'border-slate-200 bg-white hover:border-slate-300' ?>">
                        <div class="flex items-start justify-between gap-2 mb-2 sm:mb-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm sm:text-base text-[#003049] truncate"><?= htmlspecialchars($k['nama_kantin']) ?></p>
                                <div class="flex items-center gap-1 sm:gap-2 mt-1 flex-wrap">
                                    <span class="material-symbols-outlined text-xs sm:text-sm text-amber-500 flex-shrink-0">star</span>
                                    <span class="text-xs sm:text-sm font-semibold text-slate-600"><?= $k['total_rating'] > 0 ? number_format($k['total_rating'] / max($k['rating'], 1), 1) : '0' ?>/5</span>
                                    <span class="text-xs sm:text-sm text-slate-500">|</span>
                                    <span class="text-xs sm:text-sm font-semibold text-slate-600"><?= $k['jumlah_penjual'] ?>/5</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 sm:gap-2 flex-shrink-0">
                                <span class="inline-block px-2 py-0.5 sm:py-1 rounded-lg text-xs sm:text-sm font-bold <?= $k['status_buka'] === 'Buka' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> whitespace-nowrap">
                                    <?= $k['status_buka'] ?>
                                </span>
                                <span class="material-symbols-outlined text-lg sm:text-xl text-primary-orange font-bold kantin-check flex-shrink-0" style="display: <?= $is_selected ? 'block' : 'none' ?>;">check_circle</span>
                            </div>
                        </div>
                        <div class="text-xs sm:text-sm text-slate-600 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm flex-shrink-0">schedule</span>
                                <span><?= date('H:i', strtotime($k['jam_buka'])) ?> - <?= date('H:i', strtotime($k['jam_tutup'])) ?></span>
                            </div>
                        </div>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
            <?php if (mysqli_num_rows($kantins) === 0): ?>
            <div class="p-3 sm:p-4 rounded-2xl bg-yellow-50 border border-yellow-200 text-yellow-700">
                <p class="font-semibold text-sm sm:text-base">Tidak ada kantin yang tersedia</p>
                <p class="text-xs sm:text-sm mt-2">Semua kantin sudah mencapai maksimal 5 penjual. Buat kantin baru atau hapus penjual yang tidak aktif.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
            <a href="manajemen_penjual.php" class="px-4 py-3 sm:py-2 rounded-2xl border border-slate-200 text-slate-700 text-center text-sm sm:text-base font-semibold hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-4 py-3 sm:py-2 rounded-2xl bg-primary-orange text-white text-sm sm:text-base font-bold hover:bg-orange-600 transition">Simpan Penjual</button>
        </div>
    </form>
</main>
</body>
</html>
