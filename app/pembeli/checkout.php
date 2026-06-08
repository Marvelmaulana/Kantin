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

$id_user = mysqli_real_escape_string($koneksi, $_SESSION['id_user']);

$id_menu_langsung = isset($_GET['id_menu'])
    ? mysqli_real_escape_string($koneksi, $_GET['id_menu'])
    : null;

$qty_langsung = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$opsi_langsung = isset($_GET['opsi']) ? mysqli_real_escape_string($koneksi, $_GET['opsi']) : '';
$catatan_langsung = isset($_GET['catatan']) ? mysqli_real_escape_string($koneksi, $_GET['catatan']) : '';

$query = null;
$checkoutError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

if ($id_menu_langsung) {
    $cek_stok = mysqli_query($koneksi, "SELECT id_menu, status, stok, nama_menu FROM menu WHERE id_menu = '$id_menu_langsung'");
    $data_stok = mysqli_fetch_assoc($cek_stok);
    if ($data_stok) {
        $stok_status = $data_stok['status'] ?? 'Tersedia';
        $stok_jumlah = (int)($data_stok['stok'] ?? 0);
        if ($stok_status === 'Habis' || in_array($stok_status, ['Diblokir', 'Dinonaktifkan'], true) || $stok_jumlah <= 0 || $qty_langsung > $stok_jumlah) {
            header("Location: detail_menu.php?id=$id_menu_langsung");
            exit();
        }
    }

    $query = mysqli_query($koneksi, "
        SELECT menu.*, '$qty_langsung' AS qty, '$catatan_langsung' AS catatan,
               '$opsi_langsung' AS opsi_pilihan, kantin.nama_kantin,
               kantin.jam_buka, kantin.jam_tutup, kantin.status_buka
        FROM menu
        JOIN kantin ON menu.id_kantin = kantin.id_kantin
        WHERE menu.id_menu = '$id_menu_langsung'
          AND COALESCE(menu.status,'Tersedia') NOT IN ('Diblokir','Dinonaktifkan')
    ");
} else {
    $selected_raw = isset($_GET['selected']) ? $_GET['selected'] : '';
    $selected_ids = array_filter(array_map('intval', explode(',', $selected_raw)));

    if (!empty($selected_ids)) {
        $ids_str = implode(',', $selected_ids);
        $query = mysqli_query($koneksi, "
            SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto, menu.id_kantin,
                   menu.stok, menu.status, kantin.nama_kantin,
                   kantin.jam_buka, kantin.jam_tutup, kantin.status_buka
            FROM keranjang
            JOIN menu ON keranjang.id_menu = menu.id_menu
            JOIN kantin ON menu.id_kantin = kantin.id_kantin
            WHERE keranjang.id_user = '$id_user'
            AND keranjang.id_keranjang IN ($ids_str)
            AND COALESCE(menu.status,'Tersedia') NOT IN ('Diblokir','Dinonaktifkan')
        ");
    }
}

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}

$total_items = mysqli_num_rows($query);
if ($total_items == 0) {
    header("Location: dashboard.php");
    exit();
}

// Check stok dan jam operasional untuk semua item sebelum layar bayar ditampilkan.
$luarJamOperasional = false;
$adaStokBermasalah = false;
$jamBukaKantin = '07:00';
$jamTutupKantin = '15:00';
$kantinCheckoutIds = [];

while ($row = mysqli_fetch_assoc($query)) {
    $kantinCheckoutIds[(int)$row['id_kantin']] = true;
    if (!kk_is_menu_available($row) || (int)$row['qty'] > (int)($row['stok'] ?? 0)) {
        $adaStokBermasalah = true;
    }
    if (!kk_is_kantin_open($row)) {
        $luarJamOperasional = true;
        $jamBukaKantin = $row['jam_buka'] ?? '07:00';
        $jamTutupKantin = $row['jam_tutup'] ?? '15:00';
    }
}
mysqli_data_seek($query, 0);
$pajakCheckout = kk_checkout_tax() * max(1, count($kantinCheckoutIds));
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('checkout.title') ?> - Kantin Kita</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
* { scrollbar-width: thin; scrollbar-color: #fed7aa transparent; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 10px; }

body {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: linear-gradient(135deg, #fff7ed 0%, #fff5eb 30%, #ffe4e1 60%, #f0f4ff 100%);
    min-height: 100vh;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 10% 10%, rgba(249,115,22,0.06) 0%, transparent 35%),
        radial-gradient(circle at 90% 90%, rgba(139,92,246,0.05) 0%, transparent 35%);
    z-index: -1;
}

.headline { font-family: 'Plus Jakarta Sans', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24; }

.gradient-text {
    background: linear-gradient(135deg, #f97316, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.payment-card {
    transition: all 0.25s;
    border: 2px solid transparent;
    cursor: pointer;
}
.payment-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(249,115,22,0.15);
    border-color: #fed7aa;
}
.payment-card input:checked + div {
    border-color: #f97316 !important;
    background: linear-gradient(135deg, #fff7ed, #ffedd5) !important;
    box-shadow: 0 8px 24px rgba(249,115,22,0.2);
}

.item-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
    transition: all 0.25s;
}
.item-card:hover {
    border-color: #fed7aa;
    box-shadow: 0 8px 24px rgba(249,115,22,0.1);
}

.btn-pay {
    background: linear-gradient(135deg, #f97316, #ea580c);
    box-shadow: 0 8px 24px rgba(249,115,22,0.35);
    transition: all 0.3s;
}
.btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(249,115,22,0.45);
}
.btn-pay:active { transform: scale(0.98); }
</style>
</head>
<body class="pb-36">

<!-- TOAST -->
<div id="toast" class="fixed top-24 left-1/2 -translate-x-1/2 z-[9999] hidden">
    <div class="bg-white/95 backdrop-blur-xl px-6 py-4 rounded-2xl shadow-2xl border-2 border-orange-100 flex items-center gap-3">
        <span id="toast-icon" class="text-2xl"></span>
        <span id="toast-message" class="font-bold text-sm text-gray-800"></span>
    </div>
</div>

<!-- HEADER -->
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-50 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-lg mx-auto px-4 py-4 flex items-center gap-4">
        <button onclick="history.back()" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <div>
            <h1 class="headline font-black text-xl gradient-text">Checkout</h1>
            <p class="text-xs text-gray-500 font-semibold"><?= $total_items ?> item dipilih</p>
        </div>
    </div>
</header>

<main class="max-w-lg mx-auto px-4 py-6 space-y-5">
    <?php if ($checkoutError): ?>
    <div class="bg-red-50 border-2 border-red-200 rounded-3xl p-4 text-red-700 text-sm font-bold flex items-start gap-3">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span><?= htmlspecialchars($checkoutError) ?></span>
    </div>
    <?php endif; ?>

    <!-- ORDER REF -->
    <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center">
                <i class="fa-solid fa-receipt text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Order</p>
                <h2 class="headline font-black text-base">#INV-<?= date('Ymd') ?>-<?= $_SESSION['id_user'] ?></h2>
            </div>
        </div>
    </div>

    <?php if ($luarJamOperasional): ?>
    <!-- Jam Operasional Warning -->
    <div class="bg-purple-50 border-2 border-purple-200 rounded-3xl p-5 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-purple-500 text-2xl">schedule</span>
        </div>
        <div>
            <p class="font-black text-purple-700 text-base">Luar Jam Operasional</p>
            <p class="text-sm text-purple-500 mt-1">Kantin buka pukul <?= date('H:i', strtotime($jamBukaKantin)) ?> - <?= date('H:i', strtotime($jamTutupKantin)) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($adaStokBermasalah): ?>
    <div class="bg-red-50 border-2 border-red-200 rounded-3xl p-5 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-red-500 text-2xl">inventory_2</span>
        </div>
        <div>
            <p class="font-black text-red-700 text-base">Stok Tidak Tersedia</p>
            <p class="text-sm text-red-500 mt-1">Ada menu yang habis atau jumlahnya melebihi stok.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ITEMS -->
    <section class="space-y-3">
        <h3 class="headline font-black text-base flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fa-solid fa-bowl-food text-purple-500 text-sm"></i>
            </div>
            Ringkasan Pesanan
            <div class="ml-auto flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-500">
                    <input id="selectAll" type="checkbox" class="w-4 h-4" checked>
                    <span class="font-bold text-xs"><?= t('action.select_all', 'Pilih Semua') ?></span>
                </label>
            </div>
        </h3>        <p class="text-xs text-slate-500">Centang menu yang ingin dibayar. Item yang tidak dipilih akan tetap berada di keranjang.</p>
        <?php
        $total_bayar = 0;
        mysqli_data_seek($query, 0);
        while ($row = mysqli_fetch_assoc($query)):
            $sub = $row['harga'] * $row['qty'];
            $total_bayar += $sub;
            $isHabis = ($row['status'] ?? 'Tersedia') === 'Habis' || (int)($row['stok'] ?? 0) <= 0;
        ?>
        <?php $itemId = isset($row['id_keranjang']) ? (int)$row['id_keranjang'] : (int)$row['id_menu']; ?>
        <div class="item-card rounded-2xl p-4 flex gap-4 <?= $isHabis ? 'opacity-60' : '' ?>">
            <div class="flex items-start">
                <input type="checkbox" class="item-checkbox mr-3" value="<?= $itemId ?>" data-id="<?= $itemId ?>" checked>
            </div>
            <img src="<?= kk_upload_url($row['foto'] ?? '', 'menu') ?>"
                 class="w-16 h-16 rounded-xl object-cover bg-orange-50 border-2 border-white shadow"
                 onerror="this.src='../../public/assets/img/default-food.svg'">
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-black text-sm text-gray-900"><?= htmlspecialchars($row['nama_menu']) ?></h4>
                        <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($row['nama_kantin']) ?></p>
                    </div>
                    <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded-lg">×<?= $row['qty'] ?></span>
                </div>
                <?php if (!empty($row['opsi_pilihan'])): ?>
                <p class="text-xs text-orange-600 font-semibold mt-1">
                    <i class="fa-solid fa-circle-check mr-1"></i><?= htmlspecialchars($row['opsi_pilihan']) ?>
                </p>
                <?php endif; ?>
                <?php if ($isHabis): ?>
                <p class="text-xs text-red-500 font-bold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-circle-exclamation"></i> Stok habis
                </p>
                <?php else: ?>
                <p class="font-black text-orange-600 mt-1">Rp <?= number_format($sub, 0, ',', '.') ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </section>

    <!-- CATATAN -->
    <section class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg space-y-3">
        <h3 class="headline font-black text-sm flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center">
                <i class="fa-solid fa-note-sticky text-yellow-600 text-sm"></i>
            </div>
            Catatan untuk Penjual
        </h3>
        <textarea id="catatan" rows="3"
                  placeholder="Contoh: saus dipisah, nasi setengah..."
                  class="w-full rounded-2xl bg-gray-50 border-2 border-transparent focus:border-orange-300 focus:bg-white p-4 text-sm resize-none outline-none transition-all"></textarea>
    </section>

    <!-- E-WALLET -->
    <section class="space-y-3">
        <h3 class="headline font-black text-base flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-wallet text-blue-500 text-sm"></i>
            </div>
            Pilih Metode Bayar
        </h3>
        <div class="grid grid-cols-3 gap-3">
            <label class="payment-card block">
                <input type="radio" name="payment_method" value="DANA" class="hidden peer" checked>
                <div class="bg-white rounded-2xl border-2 border-gray-100 p-4 flex flex-col items-center gap-2 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center">
                        <i class="fa-brands fa-bitcoin text-blue-500 text-2xl"></i>
                    </div>
                    <span class="text-xs font-black text-gray-800">DANA</span>
                </div>
            </label>
            <label class="payment-card block">
                <input type="radio" name="payment_method" value="OVO" class="hidden peer">
                <div class="bg-white rounded-2xl border-2 border-gray-100 p-4 flex flex-col items-center gap-2 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center">
                        <i class="fa-solid fa-mobile-screen text-purple-500 text-2xl"></i>
                    </div>
                    <span class="text-xs font-black text-gray-800">OVO</span>
                </div>
            </label>
            <label class="payment-card block">
                <input type="radio" name="payment_method" value="GOPAY" class="hidden peer">
                <div class="bg-white rounded-2xl border-2 border-gray-100 p-4 flex flex-col items-center gap-2 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center">
                        <i class="fa-brands fa-google-pay text-green-500 text-2xl"></i>
                    </div>
                    <span class="text-xs font-black text-gray-800">GOPAY</span>
                </div>
            </label>
            <label class="payment-card block">
                <input type="radio" name="payment_method" value="QRIS" class="hidden peer">
                <div class="bg-white rounded-2xl border-2 border-gray-100 p-4 flex flex-col items-center gap-2 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center">
                        <i class="fa-solid fa-qrcode text-slate-600 text-2xl"></i>
                    </div>
                    <span class="text-xs font-black text-gray-800">QRIS</span>
                </div>
            </label>
        </div>
    </section>

    <!-- TOTAL -->
    <section class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
        <div class="flex justify-between items-center mb-3">
            <span class="text-sm text-gray-500 font-semibold">Subtotal</span>
            <span class="font-bold text-gray-700">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-sm text-gray-500 font-semibold">Biaya Layanan<?= count($kantinCheckoutIds) > 1 ? ' (' . count($kantinCheckoutIds) . ' kantin)' : '' ?></span>
            <span class="font-bold text-gray-700">Rp <?= number_format($pajakCheckout, 0, ',', '.') ?></span>
        </div>
        <div class="h-[2px] bg-gradient-to-r from-orange-200 via-orange-400 to-orange-200 rounded-full mb-3"></div>
        <div class="flex justify-between items-center">
            <span class="headline font-black text-base">Total Bayar</span>
            <span class="headline font-black text-2xl gradient-text">Rp <?= number_format($total_bayar + $pajakCheckout, 0, ',', '.') ?></span>
        </div>
    </section>

</main>

<!-- BUTTON -->
<div class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t-2 border-orange-100 shadow-[0_-8px_30px_rgba(249,115,22,0.15)]">
    <div class="max-w-lg mx-auto px-4 py-4">
        <form id="checkoutForm" action="proses_checkout.php" method="POST">
            <?= kk_csrf_field() ?>
            <input type="hidden" name="method" id="methodInput" value="DANA">
            <input type="hidden" name="catatan" id="catatanInput" value="">
            <?php if ($id_menu_langsung): ?>
            <input type="hidden" name="id_menu" value="<?= (int)$id_menu_langsung ?>">
            <input type="hidden" name="qty" value="<?= (int)$qty_langsung ?>">
            <input type="hidden" name="opsi" value="<?= htmlspecialchars($opsi_langsung) ?>">
            <?php else: ?>
            <input type="hidden" name="source" value="cart">
            <input type="hidden" name="selected" value="<?= htmlspecialchars($_GET['selected'] ?? '') ?>">
            <?php endif; ?>
            <button id="btnBayar" type="button" onclick="prosesBayar()"
                    class="btn-pay w-full h-14 rounded-2xl font-bold text-base flex items-center justify-center gap-3 text-white <?= ($luarJamOperasional || $adaStokBermasalah) ? 'bg-gray-300 cursor-not-allowed' : '' ?>"
                    <?= ($luarJamOperasional || $adaStokBermasalah) ? 'disabled' : '' ?>>
                <i class="fa-solid fa-<?= $luarJamOperasional ? 'clock' : ($adaStokBermasalah ? 'box-open' : 'lock') ?> text-lg"></i>
                <span class="headline font-extrabold text-lg"><?= $luarJamOperasional ? 'Kantin Tutup' : ($adaStokBermasalah ? 'Stok Bermasalah' : 'Bayar Sekarang') ?></span>
            </button>
        </form>
    </div>
</div>

<script>
function showToast(message, icon = '✓') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-message').textContent = message;
    document.getElementById('toast-icon').textContent = icon;
    toast.style.display = 'flex';
    toast.style.opacity = '1';
    toast.style.animation = 'slideDown 0.3s ease';
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 2500);
}

function prosesBayar() {
    const btn = document.getElementById('btnBayar');
    const selectedInput = document.querySelector('input[name="selected"]');
    <?php if ($luarJamOperasional): ?>
    showToast('Maaf, kantin sedang tutup. Coba lagi saat jam operasional!', '🕐');
    return;
    <?php endif; ?>
    <?php if ($adaStokBermasalah): ?>
    showToast('Ada menu yang stoknya habis atau tidak mencukupi!', 'Stok');
    return;
    <?php endif; ?>
    if (selectedInput && selectedInput.value.trim() === '') {
        showToast('Pilih minimal 1 item sebelum membayar.', '⚠️');
        return;
    }
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        showToast('Pilih metode pembayaran dulu!', '⚠️');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-lg"></i><span class="headline font-extrabold">Memproses...</span>';

    document.getElementById('methodInput').value = selectedMethod.value;
    document.getElementById('catatanInput').value = document.getElementById('catatan').value;
    document.getElementById('checkoutForm').submit();
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const itemCheckboxes = Array.from(document.querySelectorAll('.item-checkbox'));
    const selectedInput = document.querySelector('input[name="selected"]');
    const payButton = document.getElementById('btnBayar');

    function updateSelected() {
        const vals = itemCheckboxes.filter(cb => cb.checked).map(cb => cb.value);
        if (selectedInput) selectedInput.value = vals.join(',');
    }

    function updateCheckoutEnabled() {
        if (!selectedInput || !payButton) return;
        const hasSelected = selectedInput.value.trim().length > 0;
        const disabled = !hasSelected || <?= ($luarJamOperasional || $adaStokBermasalah) ? 'true' : 'false' ?>;
        payButton.disabled = disabled;
        payButton.classList.toggle('opacity-50', disabled && !hasSelected);
        payButton.classList.toggle('cursor-not-allowed', disabled && !hasSelected);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            itemCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelected();
            updateCheckoutEnabled();
        });
    }

    itemCheckboxes.forEach(cb => cb.addEventListener('change', function () {
        if (!cb.checked && selectAll) selectAll.checked = false;
        if (selectAll && itemCheckboxes.every(c => c.checked)) selectAll.checked = true;
        updateSelected();
        updateCheckoutEnabled();
    }));

    // initialize
    updateSelected();
    updateCheckoutEnabled();
});
</script>
<style>
@keyframes slideDown {
    from { opacity: 0; transform: translate(-50%, -20px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}
</style>
</body>
</html>
