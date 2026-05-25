<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// PROTEKSI HALAMAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$message_type = 'success';
$kantin_id = null;

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'Token keamanan tidak valid!';
        $message_type = 'error';
    } else {
        // Ambil data dari form
        $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
        $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
        $nama_kantin = mysqli_real_escape_string($koneksi, trim($_POST['nama_kantin'] ?? ''));
        $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
        $jam_buka = $_POST['jam_buka'] ?? '07:00:00';
        $jam_tutup = $_POST['jam_tutup'] ?? '15:00:00';
        $tipe_operasi = $_POST['tipe_operasi'] ?? 'manual'; // manual atau otomatis
        $status_buka = $_POST['status_buka'] ?? 'Tutup';
        $owner_id = isset($_POST['owner_id']) && $_POST['owner_id'] !== '' ? (int)$_POST['owner_id'] : null;
        
        $logo = null;
        $banner = null;
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        // Validasi
        if (empty($nama_kantin)) {
            $message = 'Nama kantin harus diisi!';
            $message_type = 'error';
        } elseif (!$owner_id && (empty($username) || empty($email))) {
            $message = 'Username dan email harus diisi saat membuat akun penjual baru.';
            $message_type = 'error';
        } elseif (!$owner_id && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Format email tidak valid!';
            $message_type = 'error';
        } elseif ($owner_id && mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_user=$owner_id AND role='penjual' LIMIT 1")) === 0) {
            $message = 'Pemilik tidak valid atau bukan penjual.';
            $message_type = 'error';
        } else {
            $id_user = null;

            if ($owner_id) {
                $owner_check = mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_user=$owner_id AND role='penjual' LIMIT 1");
                if (mysqli_num_rows($owner_check) === 0) {
                    $message = 'Pemilik tidak valid atau bukan penjual.';
                    $message_type = 'error';
                } else {
                    $id_user = $owner_id;
                }
            } else {
                // Validasi username/email untuk akun penjual baru
                $cek_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username' OR email='$email'");
                if (mysqli_num_rows($cek_user) > 0) {
                    $message = 'Username atau email sudah terdaftar!';
                    $message_type = 'error';
                } else {
                    $password = password_hash('kantin123', PASSWORD_DEFAULT);
                    $insert_user = mysqli_query($koneksi, "INSERT INTO users (username, email, password, role, created_at) VALUES ('$username', '$email', '$password', 'penjual', NOW())");
                    if ($insert_user) {
                        $id_user = mysqli_insert_id($koneksi);
                    } else {
                        $message = 'Gagal membuat user: ' . mysqli_error($koneksi);
                        $message_type = 'error';
                    }
                }
            }

            if ($id_user) {
                if (!empty($_FILES['logo']['name'])) {
                    $ext_logo = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext_logo, $allowed)) {
                        $nama_logo = 'logo_' . time() . '.' . $ext_logo;
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], '../../uploads/logo/' . $nama_logo)) {
                            $logo = 'logo/' . $nama_logo;
                        }
                    }
                }

                if (!empty($_FILES['banner']['name'])) {
                    $ext_banner = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext_banner, $allowed)) {
                        $nama_banner = 'banner_' . time() . '.' . $ext_banner;
                        if (move_uploaded_file($_FILES['banner']['tmp_name'], '../../uploads/banner/' . $nama_banner)) {
                            $banner = 'banner/' . $nama_banner;
                        }
                    }
                }
                
                $logo_insert = $logo ? "'$logo'" : 'NULL';
                $banner_insert = $banner ? "'$banner'" : 'NULL';
                $insert_kantin = mysqli_query($koneksi, "INSERT INTO kantin (id_user,nama_kantin,deskripsi,logo,banner,jam_buka,jam_tutup,tipe_operasi,status_buka,created_at) VALUES ($id_user,'$nama_kantin','$deskripsi',$logo_insert,$banner_insert,'$jam_buka','$jam_tutup','$tipe_operasi','$status_buka',NOW())");

                if ($insert_kantin) {
                    $kantin_id = mysqli_insert_id($koneksi);
                    mysqli_query($koneksi, "UPDATE users SET id_kantin = $kantin_id, nama_kantin = '$nama_kantin' WHERE id_user = $id_user AND role='penjual'");
                    $message = 'Kantin berhasil ditambahkan! Password default: kantin123';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal membuat kantin: ' . mysqli_error($koneksi);
                    $message_type = 'error';
                    if (!$owner_id && isset($id_user)) {
                        mysqli_query($koneksi, "DELETE FROM users WHERE id_user = $id_user");
                    }
                }
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil daftar penjual untuk opsi pemilik (jika ada)
$penjual_list = mysqli_query($koneksi, "SELECT id_user, username FROM users WHERE role='penjual' AND id_user NOT IN (SELECT id_user FROM kantin WHERE id_user IS NOT NULL) ORDER BY username ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Tambah Kantin Baru - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF9F8',
                        'primary-orange': '#E25E3E',
                        'accent-blue': '#2D9CDB',
                        'accent-green': '#27AE60'
                    },
                    borderRadius: { '4xl': '2.5rem' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFF9F8; }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .upload-preview {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: #f3f4f6;
            border-radius: 1.5rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .upload-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Responsive utilities */
        @media (max-width: 640px) {
            main { padding: 1rem !important; }
            .space-y-8 > * + * { margin-top: 1.5rem !important; }
            .grid { gap: 1rem !important; }
            button, a { font-size: 0.875rem !important; padding: 0.75rem 1rem !important; }
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f3f4f6; }
        ::-webkit-scrollbar-thumb { background: #FF8C20; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #E25E3E; }
    </style>
</head>
<body class="text-slate-800 flex">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 sm:p-6 md:p-10 min-h-screen">
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-6 mb-8 sm:mb-10 mt-14 lg:mt-0">
        <div class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto">
            <a href="manajemen_penjual.php" class="flex w-10 sm:w-12 h-10 sm:h-12 rounded-2xl bg-white border border-slate-100 items-center justify-center text-slate-400 hover:text-primary-orange transition-all shadow-sm flex-shrink-0">
                <span class="material-symbols-outlined text-lg sm:text-2xl">arrow_back</span>
            </a>
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003049] truncate">Tambah Kantin Baru</h2>
                <p class="text-slate-400 font-medium text-xs sm:text-sm mt-1 line-clamp-1">Mendaftarkan penjual dan kantin baru ke platform</p>
            </div>
        </div>
    </header>

    <?php if ($message !== ''): ?>
    <div class="mb-6 px-6 py-4 rounded-2xl border <?= $message_type === 'success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-semibold text-sm">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined mt-0.5"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <div>
                <?= $message ?>
                <?php if ($kantin_id): ?>
                <br><a href="manajemen_penjual.php" class="underline font-bold mt-2 inline-block">← Kembali ke Manajemen Kantin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- SECTION 1: INFORMASI PENJUAL -->
        <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
            <div class="p-6 md:p-8 border-b border-slate-50 bg-gradient-to-r from-orange-50 to-orange-100">
                <h3 class="font-bold text-[#003049] text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-orange">person</span>
                    Informasi Penjual
                </h3>
                <p class="text-sm text-slate-400 mt-1">Data login penjual</p>
            </div>
            
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">account_circle</span>
                            Username
                        </label>
                        <input type="text" name="username"
                               placeholder="contoh: penjual_rapi"
                               class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50">
                        <p class="text-xs text-slate-400 mt-1">Gunakan untuk login ke sistem. Biarkan kosong jika memilih pemilik yang sudah ada.</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">mail</span>
                            Email
                        </label>
                        <input type="email" name="email"
                               placeholder="contoh@email.com"
                               class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50">
                        <p class="text-xs text-slate-400 mt-1">Email unik untuk setiap penjual. Biarkan kosong jika menggunakan akun penjual yang sudah ada.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">Atau Pilih Pemilik (opsional)</label>
                        <select name="owner_id" class="w-full px-4 py-3 border border-slate-100 rounded-2xl bg-white text-sm">
                            <option value="">-- Buat akun baru (isi username & email) --</option>
                            <?php while($p = mysqli_fetch_assoc($penjual_list)): ?>
                                <option value="<?= $p['id_user'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Pilih pemilik jika sudah ada akun penjual.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: INFORMASI KANTIN -->
        <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
            <div class="p-6 md:p-8 border-b border-slate-50 bg-gradient-to-r from-blue-50 to-blue-100">
                <h3 class="font-bold text-[#003049] text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent-blue">storefront</span>
                    Informasi Kantin
                </h3>
                <p class="text-sm text-slate-400 mt-1">Detail toko dan produk</p>
            </div>

            <div class="p-6 md:p-8 space-y-6">
                <!-- Nama Kantin -->
                <div>
                    <label class="block text-sm font-bold text-slate-600 mb-2">
                        <span class="material-symbols-outlined align-middle text-base mr-1">store</span>
                        Nama Kantin
                    </label>
                    <input type="text" name="nama_kantin" required
                           placeholder="Contoh: Kantin Makan Enak"
                           class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50">
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-bold text-slate-600 mb-2">
                        <span class="material-symbols-outlined align-middle text-base mr-1">description</span>
                        Deskripsi Kantin
                    </label>
                    <textarea name="deskripsi" rows="3"
                              placeholder="Deskripsikan kantin, kualitas makanan, spesialisasi, dll..."
                              class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50 resize-none"></textarea>
                </div>

                <!-- Logo & Banner -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Logo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">image</span>
                            Logo Kantin (Opsional)
                        </label>
                        <div class="file-input-wrapper relative">
                            <div class="w-full border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center cursor-pointer hover:border-primary-orange transition-all">
                                <input type="file" name="logo" accept="image/*" onchange="previewImage(event, 'logoPreview')">
                                <span class="material-symbols-outlined text-4xl text-slate-300">image</span>
                                <p class="text-xs text-slate-400 mt-2">JPG, PNG, WebP (max 2MB)</p>
                            </div>
                        </div>
                        <div id="logoPreview" class="mt-3"></div>
                    </div>

                    <!-- Banner -->
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">image</span>
                            Banner Kantin (Opsional)
                        </label>
                        <div class="file-input-wrapper relative">
                            <div class="w-full border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center cursor-pointer hover:border-primary-orange transition-all">
                                <input type="file" name="banner" accept="image/*" onchange="previewImage(event, 'bannerPreview')">
                                <span class="material-symbols-outlined text-4xl text-slate-300">image</span>
                                <p class="text-xs text-slate-400 mt-2">JPG, PNG, WebP (max 2MB)</p>
                            </div>
                        </div>
                        <div id="bannerPreview" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: JAM OPERASIONAL -->
        <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
            <div class="p-6 md:p-8 border-b border-slate-50 bg-gradient-to-r from-green-50 to-green-100">
                <h3 class="font-bold text-[#003049] text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent-green">schedule</span>
                    Jam Operasional
                </h3>
                <p class="text-sm text-slate-400 mt-1">Atur waktu buka-tutup kantin</p>
            </div>

            <div class="p-6 md:p-8 space-y-6">
                <!-- Tipe Operasi -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">toggle_on</span>
                            Mode Operasional
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer p-3 border border-slate-100 rounded-xl hover:bg-blue-50 transition-all">
                                <input type="radio" name="tipe_operasi" value="manual" checked onchange="updateStatusOptions()">
                                <span class="font-semibold text-slate-700">Manual (Buka/Tutup Manual)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 border border-slate-100 rounded-xl hover:bg-blue-50 transition-all">
                                <input type="radio" name="tipe_operasi" value="otomatis" onchange="updateStatusOptions()">
                                <span class="font-semibold text-slate-700">Otomatis (Sesuai Jam)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status Manual -->
                    <div id="statusManualDiv">
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">storefront</span>
                            Status Awal
                        </label>
                        <select name="status_buka" class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange transition-all bg-slate-50">
                            <option value="Tutup">Tutup</option>
                            <option value="Buka">Buka</option>
                        </select>
                    </div>
                </div>

                <!-- Jam Buka -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">schedule</span>
                            Jam Buka
                        </label>
                        <input type="time" name="jam_buka" value="07:00"
                               class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50">
                    </div>

                    <!-- Jam Tutup -->
                    <div>
                        <label class="block text-sm font-bold text-slate-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-base mr-1">nightlight</span>
                            Jam Tutup
                        </label>
                        <input type="time" name="jam_tutup" value="15:00"
                               class="w-full px-4 py-3 border border-slate-100 rounded-2xl focus:outline-none focus:border-primary-orange focus:ring-2 focus:ring-orange-100 transition-all bg-slate-50">
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                    <p class="text-sm text-blue-700 font-semibold flex items-start gap-2">
                        <span class="material-symbols-outlined mt-0.5">info</span>
                        <span>Dalam mode <strong>Otomatis</strong>, kantin akan secara otomatis buka/tutup sesuai jam yang diatur. Dalam mode <strong>Manual</strong>, status buka/tutup diatur secara manual oleh penjual.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- TOMBOL -->
        <div class="flex flex-col sm:flex-row gap-3 pb-10">
            <button type="submit" class="flex-1 sm:flex-none bg-gradient-to-r from-primary-orange to-red-500 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-2xl font-bold text-sm sm:text-base shadow-lg shadow-orange-200 hover:shadow-orange-300 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">add_business</span>
                <span>Tambahkan Kantin</span>
            </button>
            <a href="manajemen_penjual.php" class="flex-1 sm:flex-none bg-slate-100 text-slate-600 px-6 sm:px-8 py-3 sm:py-4 rounded-2xl font-bold text-sm sm:text-base hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">arrow_back</span>
                <span>Batal</span>
            </a>
        </div>
    </form>
</main>

<script>
function previewImage(event, previewId) {
    const file = event.target.files[0];
    const previewDiv = document.getElementById(previewId);
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewDiv.innerHTML = `
                <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-slate-100 shadow-sm">
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" onclick="clearFile('${event.target.name}', '${previewId}')" 
                            class="absolute top-2 right-2 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-all">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

function clearFile(inputName, previewId) {
    document.querySelector(`input[name="${inputName}"]`).value = '';
    document.getElementById(previewId).innerHTML = '';
}

function updateStatusOptions() {
    const statusDiv = document.getElementById('statusManualDiv');
    const tipeOperasi = document.querySelector('input[name="tipe_operasi"]:checked').value;
    
    if (tipeOperasi === 'manual') {
        statusDiv.style.display = 'block';
    } else {
        statusDiv.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateStatusOptions);
</script>

</body>
</html>
