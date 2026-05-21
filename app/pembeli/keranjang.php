<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$csrfToken = kk_csrf_token();
$cartError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

// Hapus satu item
if (isset($_GET['hapus'])) {
    if (!kk_verify_csrf($_GET['csrf_token'] ?? '')) {
        kk_abort_csrf();
    }
    $id_keranjang = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
    header("Location: keranjang.php");
    exit();
}

// AJAX: update qty dengan validasi stok
if (isset($_POST['ajax_qty'])) {
    header('Content-Type: application/json');
    if (!kk_verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'error' => 'csrf', 'message' => 'Sesi keamanan tidak valid. Muat ulang halaman.']);
        exit();
    }
    $id_keranjang = (int)$_POST['id_keranjang'];
    $aksi = $_POST['aksi'];

    $res = mysqli_query($koneksi, "
        SELECT keranjang.qty, menu.harga, menu.stok, menu.status, menu.nama_menu,
               kantin.nama_kantin, kantin.jam_buka, kantin.jam_tutup, kantin.status_buka
        FROM keranjang
        JOIN menu ON keranjang.id_menu = menu.id_menu
        JOIN kantin ON menu.id_kantin = kantin.id_kantin
        WHERE keranjang.id_keranjang = '$id_keranjang'
        AND keranjang.id_user = '$id_user'
    ");
    $data = mysqli_fetch_assoc($res);

    if (!$data) {
        echo json_encode(['ok' => false, 'error' => 'Item tidak ditemukan']);
        exit();
    }

    $qty       = (int)$data['qty'];
    $harga     = (int)$data['harga'];
    $stok_menu = (int)($data['stok'] ?? 0);
    $status    = $data['status'] ?? 'Tersedia';
    $nama_menu = $data['nama_menu'] ?? '';

    // Cek apakah stok habis
    if ($status === 'Habis' || $stok_menu <= 0) {
        echo json_encode(['ok' => false, 'error' => 'stok_habis', 'message' => 'Maaf, ' . $nama_menu . ' sedang habis']);
        exit();
    }

    if (!kk_is_kantin_open($data)) {
        echo json_encode(['ok' => false, 'error' => 'kantin_tutup', 'message' => 'Maaf, ' . ($data['nama_kantin'] ?? 'kantin') . ' sedang tutup']);
        exit();
    }

    if ($aksi === 'tambah') {
        // Validasi stok
        if ($qty >= $stok_menu) {
            echo json_encode([
                'ok' => false,
                'error' => 'stok_cukup',
                'message' => 'Stok ' . $nama_menu . ' tinggal ' . $stok_menu,
                'stok' => $stok_menu
            ]);
            exit();
        }
        $qty++;
        mysqli_query($koneksi, "UPDATE keranjang SET qty = '$qty' WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
        echo json_encode([
            'ok' => true, 'deleted' => false, 'qty' => $qty,
            'subtotal' => $harga * $qty, 'stok' => $stok_menu
        ]);
    } elseif ($aksi === 'kurang') {
        if ($qty <= 1) {
            mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
            echo json_encode(['ok' => true, 'deleted' => true]);
        } else {
            $qty--;
            mysqli_query($koneksi, "UPDATE keranjang SET qty = '$qty' WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
            echo json_encode([
                'ok' => true, 'deleted' => false, 'qty' => $qty,
                'subtotal' => $harga * $qty, 'stok' => $stok_menu
            ]);
        }
    }
    exit();
}

// Ambil data keranjang dengan info stok dan jam operasional
$sql = "
SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto, menu.id_kantin,
       menu.stok, menu.status,
       kantin.nama_kantin, kantin.logo, kantin.jam_buka, kantin.jam_tutup, kantin.status_buka
FROM keranjang
JOIN menu ON keranjang.id_menu = menu.id_menu
JOIN kantin ON menu.id_kantin = kantin.id_kantin
WHERE keranjang.id_user = '$id_user'
ORDER BY kantin.nama_kantin ASC, keranjang.id_keranjang DESC
";
$query = mysqli_query($koneksi, $sql);
$total_items = mysqli_num_rows($query);
$items = [];
while ($row = mysqli_fetch_assoc($query)) $items[] = $row;

$grouped = [];
foreach ($items as $row) {
    $grouped[$row['id_kantin']]['nama'] = $row['nama_kantin'];
    $grouped[$row['id_kantin']]['logo'] = $row['logo'] ?? '';
    $grouped[$row['id_kantin']]['jam_buka'] = $row['jam_buka'] ?? '07:00';
    $grouped[$row['id_kantin']]['jam_tutup'] = $row['jam_tutup'] ?? '15:00';
    $grouped[$row['id_kantin']]['status_buka'] = $row['status_buka'] ?? 'Buka';
    $grouped[$row['id_kantin']]['items'][] = $row;
}

foreach ($grouped as $idKantin => $kantin) {
    $grouped[$idKantin]['is_open'] = kk_is_kantin_open($kantin);
    $grouped[$idKantin]['hours_label'] = kk_kantin_hours_label($kantin);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= t('cart.title') ?> - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,1&display=swap" rel="stylesheet"/>
    <style>
        * { scrollbar-width: thin; scrollbar-color: #fed7aa transparent; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 10px; }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: linear-gradient(135deg, #fef3e2 0%, #fff5eb 25%, #ffe4e1 50%, #f0f4ff 75%, #e8f5e9 100%);
            min-height: 100vh;
        }
        .headline { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Gradient animated background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,107,53,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(139,92,246,0.06) 0%, transparent 40%),
                        radial-gradient(circle at 20% 80%, rgba(16,185,129,0.06) 0%, transparent 40%);
            z-index: -1;
            animation: floatBg 20s ease-in-out infinite;
        }
        @keyframes floatBg {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 1%) rotate(1deg); }
            66% { transform: translate(-1%, 2%) rotate(-1deg); }
        }

        /* CHECKBOX CUSTOM */
        input[type="checkbox"].my-check {
            -webkit-appearance: none;
            appearance: none;
            width: 24px; height: 24px; min-width: 24px;
            border: 2.5px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            background: white;
            display: inline-block;
        }
        input[type="checkbox"].my-check:checked {
            background: linear-gradient(135deg, #f97316, #ea580c);
            border-color: #f97316;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        input[type="checkbox"].my-check:checked::after {
            content: '';
            position: absolute;
            left: 7px; top: 3px;
            width: 7px; height: 12px;
            border: 2.5px solid white;
            border-top: none; border-left: none;
            transform: rotate(40deg);
        }
        input[type="checkbox"].my-check:indeterminate {
            background: linear-gradient(135deg, #f97316, #ea580c);
            border-color: #f97316;
        }
        input[type="checkbox"].my-check:indeterminate::after {
            content: '';
            position: absolute;
            left: 4px; top: 10px;
            width: 12px; height: 2.5px;
            background: white; border-radius: 2px;
        }
        input[type="checkbox"].my-check:hover { border-color: #f97316; }

        .item-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
        }
        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.15);
            border-color: #fed7aa;
        }
        .item-card.selected {
            border-color: #f97316;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.2);
        }
        .item-card.stok-habis {
            opacity: 0.7;
            filter: grayscale(30%);
        }

        .qty-btn {
            transition: all 0.15s;
        }
        .qty-btn:active { transform: scale(0.85); }
        .qty-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #f97316, #ea580c);
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.35);
            transition: all 0.3s;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(249, 115, 22, 0.45);
        }
        .btn-checkout:active { transform: scale(0.98); }

        .kantin-header {
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            border: 2px solid #fed7aa;
        }

        .stok-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .gradient-text {
            background: linear-gradient(135deg, #f97316, #ea580c, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .floating { animation: floating 3s ease-in-out infinite; }
        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .btn-delete {
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
            transform: scale(1.1);
        }
    </style>
</head>
<body class="pb-32">

<!-- TOAST NOTIFICATION -->
<div id="toast" class="fixed top-24 left-1/2 -translate-x-1/2 z-[9999] hidden">
    <div class="bg-white/95 backdrop-blur-xl px-6 py-4 rounded-2xl shadow-2xl border border-orange-100 flex items-center gap-3">
        <span id="toast-icon" class="text-2xl"></span>
        <span id="toast-message" class="font-bold text-sm text-gray-800"></span>
    </div>
</div>

<header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-orange-100 shadow-sm">
    <div class="max-w-[1400px] mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <div>
                <h1 class="headline font-black text-2xl gradient-text">Keranjang</h1>
                <p class="text-xs text-gray-500 font-semibold"><?= $total_items ?> item tersimpan</p>
            </div>
        </div>
        <?php if ($total_items > 0): ?>
        <button id="btn-hapus" onclick="hapusTerpilih()"
                class="hidden px-4 py-2 rounded-xl bg-red-50 border border-red-100 text-red-500 text-xs font-bold hover:bg-red-100 transition-all">
            <i class="fa-solid fa-trash-can mr-1"></i> Hapus
        </button>
        <?php endif; ?>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 py-6 space-y-4">
    <?php if ($cartError): ?>
    <div class="bg-red-50 border-2 border-red-200 rounded-2xl px-4 py-3 text-sm font-bold text-red-700 flex items-start gap-3">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span><?= htmlspecialchars($cartError) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($total_items > 0): ?>

    <!-- EMPTY STATE HINT -->
    <div class="flex items-center gap-2 text-sm text-orange-600 font-semibold">
        <i class="fa-solid fa-lightbulb"></i>
        <span>Klik menu untuk memilih, lalu checkout!</span>
    </div>

    <!-- ITEM LIST -->
    <div class="space-y-4">
        <?php foreach ($grouped as $idKantin => $group): ?>
        <div class="kantin-header rounded-3xl p-4 md:p-5">
            <!-- Header Kantin -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <img src="<?= kk_upload_url($group['logo'] ?? '', 'logo') ?>"
                         class="w-12 h-12 rounded-2xl object-cover border-2 border-white shadow-md">
                    <div>
                        <p class="headline font-black text-base"><?= htmlspecialchars($group['nama']) ?></p>
                        <p class="text-xs text-gray-500 font-semibold"><?= count($group['items']) ?> menu</p>
                        <?php if (!$group['is_open']): ?>
                        <p class="text-[11px] text-purple-600 font-bold mt-0.5">
                            <i class="fa-solid fa-clock mr-1"></i>Tutup, buka <?= htmlspecialchars($group['hours_label']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <label class="flex items-center gap-2 <?= $group['is_open'] ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?> select-none bg-white rounded-2xl px-4 py-3 border-2 border-orange-200 hover:border-orange-400 transition-all">
                    <input type="checkbox" class="my-check kantin-check" data-kantin="<?= (int)$idKantin ?>" onchange="toggleKantin(this)" <?= $group['is_open'] ? '' : 'disabled' ?>>
                    <span class="text-xs font-black text-orange-600">Pilih Semua</span>
                </label>
            </div>

            <!-- Items -->
            <div class="space-y-3">
        <?php foreach ($group['items'] as $item):
            $sub = $item['harga'] * $item['qty'];
            $stok = (int)($item['stok'] ?? 0);
            $statusMenu = $item['status'] ?? 'Tersedia';
            $isHabis = ($statusMenu === 'Habis' || $stok <= 0);
            $stokTerbatas = (!$isHabis && $stok <= 5);
            $isKantinTutup = !$group['is_open'];
            $isUnavailable = $isHabis || $isKantinTutup;
        ?>
        <div class="item-card rounded-2xl p-4 shadow-sm <?= $isUnavailable ? 'stok-habis' : '' ?>"
             id="card-<?= $item['id_keranjang'] ?>"
             data-stok="<?= $stok ?>"
             data-status="<?= $statusMenu ?>"
             data-open="<?= $isKantinTutup ? '0' : '1' ?>">

            <div class="flex items-start gap-4">
                <!-- Checkbox -->
                <input type="checkbox" class="my-check item-check mt-2"
                       id="chk-<?= $item['id_keranjang'] ?>"
                       data-id="<?= $item['id_keranjang'] ?>"
                       data-harga="<?= (int)$item['harga'] ?>"
                       data-stok="<?= $stok ?>"
                       data-status="<?= $statusMenu ?>"
                       data-open="<?= $isKantinTutup ? '0' : '1' ?>"
                       data-nama="<?= htmlspecialchars($item['nama_menu']) ?>"
                       data-foto="<?= htmlspecialchars(kk_upload_url($item['foto'] ?? '', 'menu')) ?>"
                       data-kantin="<?= (int)$idKantin ?>"
                       onchange="onCheck(this)"
                       <?= $isUnavailable ? 'disabled' : '' ?>>

                <!-- Foto -->
                <label for="chk-<?= $item['id_keranjang'] ?>" class="cursor-pointer flex-shrink-0 relative">
                    <img src="<?= kk_upload_url($item['foto'] ?? '', 'menu') ?>"
                         alt="<?= htmlspecialchars($item['nama_menu']) ?>"
                         class="w-20 h-20 md:w-24 md:h-24 rounded-2xl object-cover bg-gray-100 border-2 border-white shadow-md">
                    <?php if ($isHabis): ?>
                    <div class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center">
                        <span class="bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-lg">HABIS</span>
                    </div>
                    <?php elseif ($isKantinTutup): ?>
                    <div class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center">
                        <span class="bg-purple-500 text-white text-[10px] font-black px-2 py-1 rounded-lg">TUTUP</span>
                    </div>
                    <?php elseif ($stokTerbatas): ?>
                    <div class="absolute -top-2 -right-2 bg-amber-500 text-white text-[10px] font-black px-2 py-1 rounded-lg stok-badge">
                        TERSISA <?= $stok ?>
                    </div>
                    <?php endif; ?>
                </label>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <h3 class="headline font-black text-sm md:text-base text-gray-900"><?= htmlspecialchars($item['nama_menu']) ?></h3>
                            <?php if (!empty($item['opsi_pilihan'])): ?>
                            <p class="text-xs text-orange-600 font-semibold mt-0.5">
                                <i class="fa-solid fa-circle-check mr-1"></i><?= htmlspecialchars($item['opsi_pilihan']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($isHabis): ?>
                            <p class="text-xs text-red-500 font-bold mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i> Stok habis
                            </p>
                            <?php elseif ($isKantinTutup): ?>
                            <p class="text-xs text-purple-600 font-bold mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-clock"></i> Kantin tutup
                            </p>
                            <?php elseif ($stokTerbatas): ?>
                            <p class="text-xs text-amber-600 font-bold mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i> Stok terbatas!
                            </p>
                            <?php endif; ?>
                        </div>
                        <button onclick="hapusItem(<?= $item['id_keranjang'] ?>)"
                                class="btn-delete w-9 h-9 rounded-xl flex items-center justify-center text-gray-400">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-3">
                        <span class="font-black text-lg md:text-xl gradient-text" id="harga-<?= $item['id_keranjang'] ?>">
                            Rp <?= number_format($sub, 0, ',', '.') ?>
                        </span>
                        <div class="flex items-center bg-gray-100 rounded-full p-1 gap-1 <?= $isUnavailable ? 'opacity-50' : '' ?>">
                            <button class="qty-btn w-9 h-9 flex items-center justify-center rounded-full bg-white shadow-sm text-gray-600 font-black hover:bg-gray-50"
                                    onclick="ubahQty(<?= $item['id_keranjang'] ?>, 'kurang')"
                                    <?= $isUnavailable ? 'disabled' : '' ?>>
                                <span class="text-sm">−</span>
                            </button>
                            <span class="text-sm font-black min-w-[32px] text-center" id="qty-<?= $item['id_keranjang'] ?>">
                                <?= $item['qty'] ?>
                            </span>
                            <button class="qty-btn w-9 h-9 flex items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-sm font-black hover:from-orange-600 hover:to-orange-700"
                                    onclick="ubahQty(<?= $item['id_keranjang'] ?>, 'tambah')"
                                    <?= $isUnavailable ? 'disabled' : '' ?>>
                                <span class="text-sm">+</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ADD MORE BUTTON -->
    <button onclick="location.href='dashboard.php'"
            class="w-full flex items-center justify-center gap-3 py-5 bg-white border-2 border-dashed border-orange-200 rounded-2xl text-orange-500 text-sm font-bold hover:border-orange-400 hover:bg-orange-50 transition-all group">
        <i class="fa-solid fa-plus text-lg group-hover:scale-110 transition-transform"></i>
        Tambah Menu Lainnya
    </button>

    <?php else: ?>
    <!-- EMPTY STATE -->
    <div class="text-center py-20">
        <div class="floating">
            <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center">
                <i class="fa-solid fa-basket-shopping text-5xl text-orange-400"></i>
            </div>
        </div>
        <h2 class="headline font-black text-2xl text-gray-800 mb-2">Keranjang Kosong</h2>
        <p class="text-gray-500 mb-6">Yuk mulai belanja dan nikmati makanan favoritmu!</p>
        <button onclick="location.href='dashboard.php'"
                class="px-8 py-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-full shadow-lg shadow-orange-200 hover:shadow-xl hover:scale-105 transition-all">
            <i class="fa-solid fa-utensils mr-2"></i>Mulai Belanja
        </button>
    </div>
    <?php endif; ?>
</main>

<?php if ($total_items > 0): ?>
<!-- COLLAPSIBLE CHECKOUT PANEL -->
<div class="fixed bottom-0 left-0 right-0 z-50">
    <!-- Expandable Panel (shown by default when items selected) -->
    <div id="checkoutPanel" class="bg-white/98 backdrop-blur-xl border-t-2 border-orange-200 shadow-[0_-8px_30px_rgba(0,0,0,0.12)]">
        <div class="max-w-[1400px] mx-auto px-4 py-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <button onclick="toggleCheckoutPanel()" class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 hover:bg-orange-100 transition-all flex-shrink-0" id="collapseBtn">
                    <i class="fa-solid fa-chevron-up text-sm transition-transform" id="checkoutArrow"></i>
                </button>
                <div class="flex items-center gap-2 text-xs text-gray-400 font-semibold px-3 py-1.5 bg-gray-50 rounded-xl border border-gray-100">
                    <i class="fa-solid fa-bowl-food text-orange-400 text-[10px]"></i>
                    <span id="item-count-badge">0 item</span>
                </div>
                <button id="btn-checkout" onclick=" lanjutCheckout()"
                        disabled
                        class="px-5 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all bg-gray-200 text-gray-400 cursor-not-allowed flex-shrink-0">
                    <i class="fa-solid fa-lock text-sm"></i>
                    <span id="btn-label">Pilih Menu Dulu</span>
                </button>
            </div>

            <div id="selectedItemsWrap">
                <div class="h-[2px] bg-gradient-to-r from-orange-200 via-orange-400 to-orange-200 rounded-full mb-3"></div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-orange-500 text-[10px]"></i>
                        Item Dipilih
                    </h3>
                    <span class="text-[10px] text-gray-400 font-semibold" id="item-count-detail">0 item</span>
                </div>
                <div id="selected-items-list" class="space-y-2 max-h-48 overflow-y-auto pr-1 mb-3">
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-dashed border-orange-100">
                    <p class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-receipt text-orange-400 text-[10px]"></i>
                        Total Tagihan
                    </p>
                    <p class="font-black text-2xl gradient-text" id="grand-total">Rp 0</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Data items
const itemData = <?php
$jsItems = [];
foreach ($items as $item) {
    $stok = (int)($item['stok'] ?? 0);
    $status = $item['status'] ?? 'Tersedia';
    $isHabis = ($status === 'Habis' || $stok <= 0);
    $jsItems[$item['id_keranjang']] = [
        'id' => (int)$item['id_keranjang'],
        'nama' => $item['nama_menu'] ?? '',
        'foto' => kk_upload_url($item['foto'] ?? '', 'menu'),
        'harga' => (int)$item['harga'],
        'qty' => (int)$item['qty'],
        'stok' => $stok,
        'habis' => $isHabis,
        'open' => kk_is_kantin_open($item)
    ];
}
echo json_encode($jsItems);
?>;

const selected = new Set();
const selectedData = {};
const csrfToken = <?= json_encode($csrfToken) ?>;

function showToast(message, icon = '✓', type = 'success') {
    const toast = document.getElementById('toast');
    const iconEl = document.getElementById('toast-icon');
    const msgEl = document.getElementById('toast-message');

    iconEl.textContent = icon;
    msgEl.textContent = message;
    toast.className = 'fixed top-24 left-1/2 -translate-x-1/2 z-[9999] flex items-center gap-3 ' +
        (type === 'error' ? 'animate-shake' : '');
    toast.style.display = 'flex';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

function toggleKantin(cb) {
    const kantin = cb.dataset.kantin;
    document.querySelectorAll(`.item-check[data-kantin="${kantin}"]`).forEach(c => {
        if (c.disabled) return;
        c.checked = cb.checked;
        const id = +c.dataset.id;
        if (cb.checked) {
            selected.add(id);
            selectedData[id] = {
                id: id,
                nama: c.dataset.nama,
                harga: +c.dataset.harga,
                qty: itemData[id]?.qty || 1,
                stok: +c.dataset.stok,
                status: c.dataset.status,
                open: c.dataset.open === '1',
                foto: c.dataset.foto || ''
            };
        } else {
            selected.delete(id);
            delete selectedData[id];
        }
        styleCard(id, cb.checked);
    });
    syncKantin(kantin);
    updateUI();
}

function onCheck(cb) {
    const id = +cb.dataset.id;
    if (cb.checked && (cb.disabled || cb.dataset.open !== '1' || cb.dataset.status === 'Habis' || +cb.dataset.stok <= 0)) {
        cb.checked = false;
        showToast('Item ini belum bisa dipilih.', '!', 'error');
        return;
    }
    if (cb.checked) {
        selected.add(id);
        selectedData[id] = {
            id: id,
            nama: cb.dataset.nama,
            harga: +cb.dataset.harga,
            qty: itemData[id]?.qty || 1,
            stok: +cb.dataset.stok,
            status: cb.dataset.status,
            open: cb.dataset.open === '1',
            foto: cb.dataset.foto || ''
        };
    } else {
        selected.delete(id);
        delete selectedData[id];
    }
    styleCard(id, cb.checked);
    syncKantin(cb.dataset.kantin);
    updateUI();
}

function styleCard(id, on) {
    const c = document.getElementById('card-' + id);
    if (c) {
        on ? c.classList.add('selected') : c.classList.remove('selected');
    }
}

function syncKantin(kantin) {
    const all = document.querySelectorAll(`.item-check[data-kantin="${kantin}"]:not(:disabled)`);
    const chk = document.querySelectorAll(`.item-check[data-kantin="${kantin}"]:checked`);
    const sa  = document.querySelector(`.kantin-check[data-kantin="${kantin}"]`);
    if (!sa) return;
    if (chk.length === 0) { sa.checked = false; sa.indeterminate = false; }
    else if (chk.length === all.length) { sa.checked = true; sa.indeterminate = false; }
    else { sa.checked = false; sa.indeterminate = true; }
}

function updateUI() {
    let total = 0, qty = 0, adaHabis = false, adaTutup = false;

    selected.forEach(id => {
        if (selectedData[id]) {
            total += selectedData[id].harga * selectedData[id].qty;
            qty += 1;
            if (selectedData[id].status === 'Habis' || selectedData[id].stok <= 0) {
                adaHabis = true;
            }
            if (!selectedData[id].open) {
                adaTutup = true;
            }
        }
    });

    document.getElementById('grand-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('item-count-badge').textContent = qty + ' item';
    document.getElementById('item-count-detail').textContent = qty + ' item';

    // Populate selected items list
    const itemsList = document.getElementById('selected-items-list');
    const itemsWrap = document.getElementById('selectedItemsWrap');
    const collapseBtn = document.getElementById('collapseBtn');

    if (itemsList) {
        if (selected.size === 0) {
            itemsList.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Belum ada item dipilih — klik item di atas untuk memilih</p>';
            itemsWrap && itemsWrap.classList.add('hidden');
            if (collapseBtn) collapseBtn.style.display = 'none';
        } else {
            if (itemsWrap) itemsWrap.classList.remove('hidden');
            if (collapseBtn) collapseBtn.style.display = 'flex';
            let itemsHtml = '';
            selected.forEach(id => {
                const item = selectedData[id];
                const fullItem = itemData[id];
                if (item) {
                    const isHabis = item.status === 'Habis' || item.stok <= 0;
                    const isClosed = !item.open;
                    const subtotal = item.harga * item.qty;
                    const fotoUrl = fullItem && fullItem.foto ? fullItem.foto : '../../public/assets/img/default-food.svg';
                    itemsHtml += `
                    <div class="flex items-center gap-3 p-2.5 rounded-xl ${(isHabis || isClosed) ? 'bg-red-50 border border-red-100' : 'bg-gray-50 border border-gray-100'}">
                        <img src="${fotoUrl}" class="w-11 h-11 rounded-xl object-cover bg-gray-100 flex-shrink-0"
                             onerror="this.src='../../public/assets/img/default-food.svg'">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-gray-800 truncate ${isHabis ? 'text-red-500 line-through' : ''}">${item.nama || 'Menu'}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">${item.qty} x Rp ${item.harga.toLocaleString('id-ID')}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-black ${isHabis ? 'text-red-400 line-through' : 'gradient-text'}">Rp ${subtotal.toLocaleString('id-ID')}</p>
                            ${isHabis ? '<p class="text-[9px] text-red-400 font-bold">Stok habis</p>' : (isClosed ? '<p class="text-[9px] text-purple-500 font-bold">Kantin tutup</p>' : '')}
                        </div>
                    </div>`;
                }
            });
            itemsList.innerHTML = itemsHtml;
        }
    }

    const btn = document.getElementById('btn-checkout');
    const label = document.getElementById('btn-label');
    const btnHapus = document.getElementById('btn-hapus');

    if (selected.size > 0 && !adaHabis && !adaTutup) {
        btn.disabled = false;
        btn.className = 'px-6 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all bg-gradient-to-r from-orange-500 to-orange-600 text-white cursor-pointer shadow-lg shadow-orange-200 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] flex-shrink-0';
        label.textContent = 'Checkout (' + qty + ')';
    } else if (adaHabis) {
        btn.disabled = true;
        btn.className = 'px-6 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all bg-gray-200 text-gray-400 cursor-not-allowed flex-shrink-0';
        label.textContent = 'Ada Stok Habis';
    } else if (adaTutup) {
        btn.disabled = true;
        btn.className = 'px-6 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all bg-gray-200 text-gray-400 cursor-not-allowed flex-shrink-0';
        label.textContent = 'Kantin Tutup';
    } else {
        btn.disabled = true;
        btn.className = 'px-6 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all bg-gray-200 text-gray-400 cursor-not-allowed flex-shrink-0';
        label.textContent = 'Pilih Menu Dulu';
    }

    btnHapus && (selected.size > 0 ? btnHapus.classList.remove('hidden') : btnHapus.classList.add('hidden'));
}

let checkoutPanelOpen = true;

function toggleCheckoutPanel() {
    const wrap = document.getElementById('selectedItemsWrap');
    const arrow = document.getElementById('checkoutArrow');
    checkoutPanelOpen = !checkoutPanelOpen;
    if (checkoutPanelOpen) {
        wrap.classList.remove('hidden');
        arrow.style.transform = 'rotate(0deg)';
    } else {
        wrap.classList.add('hidden');
        arrow.style.transform = 'rotate(180deg)';
    }
}

// AJAX qty dengan validasi stok
async function ubahQty(id, aksi) {
    const fd = new FormData();
    fd.append('ajax_qty', '1');
    fd.append('id_keranjang', id);
    fd.append('aksi', aksi);
    fd.append('csrf_token', csrfToken);

    const res = await fetch('keranjang.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (!data.ok) {
        if (data.error === 'stok_habis') {
            showToast(data.message, '⚠️', 'error');
            location.reload();
        } else if (data.error === 'stok_cukup') {
            showToast(data.message, '📦', 'error');
        } else {
            showToast(data.message || 'Terjadi kesalahan', '!', 'error');
            if (data.error === 'kantin_tutup') location.reload();
        }
        return;
    }

    if (data.deleted) {
        document.getElementById('card-' + id)?.remove();
        selected.delete(id);
        delete itemData[id];
        delete selectedData[id];
        document.querySelectorAll('.kantin-check').forEach(cb => syncKantin(cb.dataset.kantin));
        updateUI();
        if (Object.keys(itemData).length === 0) location.reload();
        return;
    }

    itemData[id].qty = data.qty;
    document.getElementById('qty-' + id).textContent = data.qty;
    document.getElementById('harga-' + id).textContent = 'Rp ' + data.subtotal.toLocaleString('id-ID');

    if (selectedData[id]) {
        selectedData[id].qty = data.qty;
    }
    if (selected.has(id)) updateUI();
}

function hapusItem(id) {
    if (!confirm('Hapus item ini dari keranjang?')) return;
    location.href = 'keranjang.php?hapus=' + id + '&csrf_token=' + encodeURIComponent(csrfToken);
}

function hapusTerpilih() {
    if (!selected.size) return;
    if (!confirm('Hapus ' + selected.size + ' item dari keranjang?')) return;
    location.href = 'hapus_banyak.php?ids=' + [...selected].join(',') + '&csrf_token=' + encodeURIComponent(csrfToken);
}

function lanjutCheckout() {
    if (!selected.size) return;

    // Cek lagi apakah ada yang habis atau kantinnya tutup
    let adaHabis = false;
    let adaTutup = false;
    for (let id of selected) {
        if (selectedData[id] && (selectedData[id].status === 'Habis' || selectedData[id].stok <= 0)) {
            adaHabis = true;
            break;
        }
        if (selectedData[id] && !selectedData[id].open) {
            adaTutup = true;
            break;
        }
    }

    if (adaHabis) {
        showToast('Pilih menu yang tidak habis ya!', '⚠️', 'error');
        return;
    }

    if (adaTutup) {
        showToast('Maaf, kantin sedang tutup. Coba lagi saat jam operasional!', '🕐', 'error');
        return;
    }

    location.href = 'checkout.php?selected=' + [...selected].join(',');
}

// Init
updateUI();
</script>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(-50%); }
    25% { transform: translateX(-50%) translateX(-5px); }
    75% { transform: translateX(-50%) translateX(5px); }
}
.animate-shake { animation: shake 0.5s ease-in-out; }
</style>

<?php $current_page = 'cart'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>

