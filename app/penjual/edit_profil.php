<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];

$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($q_user);
if (!$user) die("User tidak ditemukan");

$id_kantin = (int)$user['id_kantin'];

$q_kantin = mysqli_query($koneksi, "SELECT * FROM kantin WHERE id_kantin = $id_kantin");
$data = mysqli_fetch_assoc($q_kantin);
if (!$data) die("Data kantin tidak ditemukan");

if(isset($_POST['simpan'])){
    $nama_kantin = mysqli_real_escape_string($koneksi, trim($_POST['nama_kantin']));
    $deskripsi   = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi']));
    $jam_buka    = $_POST['jam_buka'];
    $jam_tutup   = $_POST['jam_tutup'];
    $status_buka = $_POST['status_buka'];
    $logo   = $data['logo'];
    $banner = $data['banner'];

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if(!empty($_FILES['logo']['name'])){
        $ext_logo = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext_logo, $allowed)){
            $nama_logo = 'logo_' . time() . '.' . $ext_logo;
            move_uploaded_file($_FILES['logo']['tmp_name'], '../../uploads/logo/' . $nama_logo);
            $logo = 'logo/' . $nama_logo;
        }
    }

    if(!empty($_FILES['banner']['name'])){
        $ext_banner = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        if(in_array($ext_banner, $allowed)){
            $nama_banner = 'banner_' . time() . '.' . $ext_banner;
            move_uploaded_file($_FILES['banner']['tmp_name'], '../../uploads/banner/' . $nama_banner);
            $banner = 'banner/' . $nama_banner;
        }
    }

    mysqli_query($koneksi, "
        UPDATE kantin SET
            nama_kantin  = '$nama_kantin',
            deskripsi    = '$deskripsi',
            jam_buka     = '$jam_buka',
            jam_tutup    = '$jam_tutup',
            status_buka  = '$status_buka',
            logo         = '$logo',
            banner       = '$banner'
        WHERE id_kantin  = $id_kantin
    ");

    echo "<script>alert('Profil berhasil diperbarui'); window.location='dashboard_penjual.php';</script>";
}

$logo_tampil   = !empty($data['logo'])   ? '../../uploads/' . $data['logo']   : '../../uploads/default-logo.png';
$banner_tampil = !empty($data['banner']) ? '../../uploads/' . $data['banner'] : '../../uploads/default-banner.jpg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Kantin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316'
                    }
                }
            }
        }
    </script>

    <!-- ✅ FIX: Material Symbols & Plus Jakarta Sans (sama seperti sidebar) -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background: linear-gradient(135deg, #fff7f0 0%, #fff 60%, #fff0ee 100%); }

        /* Upload preview */
        .upload-zone {
            border: 2px dashed #fed7aa;
            transition: all .2s ease;
        }
        .upload-zone:hover {
            border-color: #f97316;
            background: #fff7ed;
        }

        /* Input focus glow */
        input:focus, select:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(249,115,22,0.15);
        }

        /* Smooth slide-in */
        .card-fade {
            animation: fadeUp .4s ease both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- ✅ Sidebar yang sudah benar -->
    <?php include(__DIR__ . '/../../includes/sidebar_penjual.php'); ?>

    <!-- ✅ Main content dengan margin yang sesuai sidebar (w-72 = 288px) -->
    <main class="lg:ml-72 p-6 pt-20 lg:pt-8 pb-16 min-h-screen">

        <!-- PAGE HEADER -->
        <div class="mb-8 card-fade">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-white text-lg">edit_square</span>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 leading-none">Edit Profil Kantin</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Kelola informasi dan tampilan kantin kamu</p>
                </div>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <!-- BANNER PREVIEW CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-orange-50 overflow-hidden mb-6 card-fade" style="animation-delay:.05s">

                <!-- Banner -->
                <div class="relative h-56 bg-gradient-to-r from-orange-500 to-red-500 overflow-hidden" id="bannerPreviewWrap">
                    <img id="bannerPreview"
                         src="<?= $banner_tampil ?>"
                         class="w-full h-full object-cover"
                         alt="Banner">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

                    <!-- Upload banner overlay -->
                    <label for="inputBanner"
                           class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer opacity-0 hover:opacity-100 transition-opacity bg-black/40 gap-2">
                        <span class="material-symbols-outlined text-white text-4xl">add_photo_alternate</span>
                        <span class="text-white font-semibold text-sm">Ganti Banner</span>
                    </label>
                    <input type="file" name="banner" id="inputBanner" class="hidden" accept="image/*"
                           onchange="previewImage(this,'bannerPreview')">
                </div>

                <!-- Logo -->
                <div class="px-8 pb-6">
                    <div class="relative w-24 h-24 -mt-12 rounded-3xl border-4 border-white shadow-xl overflow-hidden bg-white group">
                        <img id="logoPreview"
                             src="<?= $logo_tampil ?>"
                             class="w-full h-full object-cover"
                             alt="Logo">
                        <label for="inputLogo"
                               class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity bg-black/50 gap-1">
                            <span class="material-symbols-outlined text-white text-2xl">photo_camera</span>
                            <span class="text-white text-[10px] font-semibold">Ganti</span>
                        </label>
                        <input type="file" name="logo" id="inputLogo" class="hidden" accept="image/*"
                               onchange="previewImage(this,'logoPreview')">
                    </div>
                    <p class="text-xs text-gray-400 mt-3">
                        <span class="material-symbols-outlined text-orange-400 align-middle text-sm">info</span>
                        Hover pada banner / logo untuk mengganti gambar
                    </p>
                </div>
            </div>

            <!-- FORM FIELDS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- NAMA KANTIN -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50 card-fade" style="animation-delay:.1s">
                    <label class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-3">
                        <span class="material-symbols-outlined align-middle text-base mr-1">storefront</span>
                        Nama Kantin
                    </label>
                    <input type="text" name="nama_kantin"
                           value="<?= htmlspecialchars($data['nama_kantin']) ?>"
                           class="w-full border border-gray-100 bg-gray-50 rounded-2xl px-5 py-3.5 text-gray-800 font-semibold outline-none focus:border-orange-400 transition-all">
                </div>

                <!-- STATUS -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50 card-fade" style="animation-delay:.15s">
                    <label class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-3">
                        <span class="material-symbols-outlined align-middle text-base mr-1">toggle_on</span>
                        Status Kantin
                    </label>
                    <select name="status_buka"
                            class="w-full border border-gray-100 bg-gray-50 rounded-2xl px-5 py-3.5 text-gray-800 font-semibold outline-none focus:border-orange-400 transition-all">
                        <option value="Buka"  <?= $data['status_buka']=='Buka'  ? 'selected':'' ?>>🟢 Sedang Buka</option>
                        <option value="Tutup" <?= $data['status_buka']=='Tutup' ? 'selected':'' ?>>🔴 Tutup</option>
                    </select>
                </div>

                <!-- JAM BUKA -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50 card-fade" style="animation-delay:.2s">
                    <label class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-3">
                        <span class="material-symbols-outlined align-middle text-base mr-1">schedule</span>
                        Jam Buka
                    </label>
                    <input type="time" name="jam_buka"
                           value="<?= $data['jam_buka'] ?>"
                           class="w-full border border-gray-100 bg-gray-50 rounded-2xl px-5 py-3.5 text-gray-800 font-semibold outline-none focus:border-orange-400 transition-all">
                </div>

                <!-- JAM TUTUP -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50 card-fade" style="animation-delay:.25s">
                    <label class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-3">
                        <span class="material-symbols-outlined align-middle text-base mr-1">nightlight</span>
                        Jam Tutup
                    </label>
                    <input type="time" name="jam_tutup"
                           value="<?= $data['jam_tutup'] ?>"
                           class="w-full border border-gray-100 bg-gray-50 rounded-2xl px-5 py-3.5 text-gray-800 font-semibold outline-none focus:border-orange-400 transition-all">
                </div>

            </div>

            <!-- DESKRIPSI -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50 mt-6 card-fade" style="animation-delay:.3s">
                <label class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-3">
                    <span class="material-symbols-outlined align-middle text-base mr-1">description</span>
                    Deskripsi Kantin
                </label>
                <textarea name="deskripsi" rows="4"
                          class="w-full border border-gray-100 bg-gray-50 rounded-2xl px-5 py-3.5 text-gray-800 outline-none focus:border-orange-400 transition-all resize-none"
                          placeholder="Ceritakan keunggulan kantin kamu..."><?= htmlspecialchars($data['deskripsi']) ?></textarea>
            </div>

            <!-- INFO BOX -->
            <div class="mt-6 bg-orange-50 border border-orange-100 rounded-3xl p-5 flex gap-4 items-start card-fade" style="animation-delay:.35s">
                <span class="material-symbols-outlined text-orange-500 mt-0.5">info</span>
                <div>
                    <p class="font-bold text-orange-700 text-sm mb-1">Info Jam Operasional</p>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Kantin akan otomatis tampil buka sesuai jam yang diatur. Di luar jam tersebut, status otomatis menjadi tutup untuk pembeli.
                    </p>
                </div>
            </div>

            <!-- TOMBOL SIMPAN -->
            <div class="mt-8 flex gap-4 card-fade" style="animation-delay:.4s">
                <button type="submit" name="simpan"
                        class="flex items-center gap-3 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-orange-200 hover:shadow-orange-300 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Perubahan
                </button>
                <a href="dashboard_penjual.php"
                   class="flex items-center gap-3 bg-gray-100 hover:bg-gray-200 text-gray-600 px-8 py-4 rounded-2xl font-bold transition-all duration-200">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Kembali
                </a>
            </div>

        </form>
    </main>

    <script>
    // Preview gambar sebelum upload
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById(previewId).src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>

</body>
</html>