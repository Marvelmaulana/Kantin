<?php
session_start();
include __DIR__ . '/config/config.php';

// 1. PROTEKSI: Hanya Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. LOGIKA TAMBAH KANTIN (ID OTOMATIS)
if (isset($_POST['tambah_kantin'])) {
    if (!kk_verify_csrf($_POST['csrf_token'] ?? '')) {
        kk_abort_csrf();
    }
    // Sekarang hanya menangkap Nama dan Password
    $nama_kantin = mysqli_real_escape_string($koneksi, $_POST['nama_kantin']);
    $pass_kantin = mysqli_real_escape_string($koneksi, $_POST['pass_kantin']);
    $jam_buka = mysqli_real_escape_string($koneksi, $_POST['jam_buka'] ?? '07:00');
    $jam_tutup = mysqli_real_escape_string($koneksi, $_POST['jam_tutup'] ?? '15:00');

    // Query INSERT tanpa menyertakan id_kantin (karena sudah Auto Increment di DB)
    // Pastikan tabel kamu punya kolom 'nama_kantin'
    $query = "INSERT INTO kantin (nama_kantin, pasword_kantin, jam_buka, jam_tutup, status_buka) VALUES ('$nama_kantin', '$pass_kantin', '$jam_buka', '$jam_tutup', 'Buka')";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Kantin Berhasil Ditambahkan!'); window.location='kelola_kantin.php';</script>";
    } else {
        echo "Gagal: " . mysqli_error($koneksi);
    }
}

// 3. LOGIKA HAPUS KANTIN
if (isset($_GET['hapus'])) {
    if (!kk_verify_csrf($_GET['csrf_token'] ?? '')) {
        kk_abort_csrf();
    }
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM kantin WHERE id_kantin = '$id_hapus'");
    header("Location: kelola_kantin.php");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kantin | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 250px; }
        body { background-color: #f4f7f6; }
        .sidebar { width: var(--sidebar-width); min-height: 100vh; background: #1a1d20; color: white; position: fixed; padding: 20px; }
        .main-content { margin-left: var(--sidebar-width); padding: 30px; }
        .nav-link { color: #8d9498; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background: #0d6efd; color: #fff; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center fw-bold mb-4 text-primary">ADMIN PANEL</h4>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard_admin.php"><i class="bi bi-grid-1x2-fill me-2"></i> Dashboard</a>
        <a class="nav-link active" href="kelola_kantin.php"><i class="bi bi-shop me-2"></i> Kelola Kantin</a>
        <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i> Keluar</a>
    </nav>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h2 class="fw-bold">Manajemen Data Kantin</h2>
            <p class="text-muted">Tambah kantin baru tanpa perlu input ID secara manual.</p>
        </div>

        <div class="col-md-4">
            <div class="card card-custom p-4 mb-4 text-white bg-dark">
                <h5 class="fw-bold mb-3">Tambah Kantin</h5>
                <form action="" method="POST">
                    <?= kk_csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">ID KANTIN</label>
                        <input type="text" class="form-control bg-secondary text-white border-0" value="Otomatis" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kantin</label>
                        <input type="text" name="nama_kantin" class="form-control" placeholder="Misal: Kantin Sehat" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password Verifikasi</label>
                        <input type="text" name="pass_kantin" class="form-control" placeholder="Password untuk penjual..." required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Buka</label>
                            <input type="time" name="jam_buka" class="form-control" value="07:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Tutup</label>
                            <input type="time" name="jam_tutup" class="form-control" value="15:00" required>
                        </div>
                    </div>
                    <button type="submit" name="tambah_kantin" class="btn btn-primary w-100 fw-bold">SIMPAN KANTIN</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-custom p-4">
                <h5 class="fw-bold mb-3 text-dark">Daftar Kantin Aktif</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Kantin</th>
                                <th>Password Verifikasi</th>
                                                <th>Jam Operasional</th>
                                                <th>Penjual</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query menampilkan kantin & username penjual yang terhubung
                            $sql = "SELECT k.*, u.username FROM kantin k 
                                    LEFT JOIN users u ON k.id_kantin = u.id_kantin 
                                    ORDER BY k.id_kantin DESC";
                            $res = mysqli_query($koneksi, $sql);
                            
                            if (mysqli_num_rows($res) > 0) {
                                while ($row = mysqli_fetch_assoc($res)) { ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $row['id_kantin']; ?></td>
                                        <td><span class="badge bg-light text-dark border p-2"><?php echo $row['nama_kantin']; ?></span></td>
                                        <td><code><?php echo $row['pasword_kantin']; ?></code></td>
                                        <td><?php echo date('H:i', strtotime($row['jam_buka'] ?? '07:00')); ?> - <?php echo date('H:i', strtotime($row['jam_tutup'] ?? '15:00')); ?></td>
                                        <td>
                                            <?php echo $row['username'] ? '<span class="text-success"><i class="bi bi-person-check"></i> '.$row['username'].'</span>' : '<span class="text-muted small italic">Belum Ada</span>'; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="kelola_kantin.php?hapus=<?php echo $row['id_kantin']; ?>&csrf_token=<?= urlencode(kk_csrf_token()) ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Hapus kantin ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                echo "<tr><td colspan='5' class='text-center py-3'>Belum ada unit kantin.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
