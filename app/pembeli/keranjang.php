<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Hapus satu item
if (isset($_GET['hapus'])) {
    $id_keranjang = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
    header("Location: keranjang.php");
    exit();
}

// AJAX: update qty langsung tanpa reload
if (isset($_POST['ajax_qty'])) {
    header('Content-Type: application/json');
    $id_keranjang = (int)$_POST['id_keranjang'];
    $aksi = $_POST['aksi'];

    $res = mysqli_query($koneksi, "SELECT keranjang.qty, menu.harga FROM keranjang JOIN menu ON keranjang.id_menu = menu.id_menu WHERE keranjang.id_keranjang = '$id_keranjang' AND keranjang.id_user = '$id_user'");
    $data = mysqli_fetch_assoc($res);

    if (!$data) { echo json_encode(['ok' => false]); exit(); }

    $qty   = (int)$data['qty'];
    $harga = (int)$data['harga'];

    if ($aksi === 'tambah') {
        $qty++;
        mysqli_query($koneksi, "UPDATE keranjang SET qty = '$qty' WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
        echo json_encode(['ok' => true, 'deleted' => false, 'qty' => $qty, 'subtotal' => $harga * $qty]);
    } elseif ($aksi === 'kurang') {
        if ($qty <= 1) {
            mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
            echo json_encode(['ok' => true, 'deleted' => true]);
        } else {
            $qty--;
            mysqli_query($koneksi, "UPDATE keranjang SET qty = '$qty' WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
            echo json_encode(['ok' => true, 'deleted' => false, 'qty' => $qty, 'subtotal' => $harga * $qty]);
        }
    }
    exit();
}

// Ambil data keranjang
$sql   = "SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto FROM keranjang JOIN menu ON keranjang.id_menu = menu.id_menu WHERE keranjang.id_user = '$id_user'";
$query = mysqli_query($koneksi, $sql);
$total_items = mysqli_num_rows($query);
$items = [];
while ($row = mysqli_fetch_assoc($query)) $items[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Keranjang - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background: #fff8f6; }
        .headline { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* CHECKBOX CUSTOM - tidak pakai Tailwind Forms agar tidak double */
        input[type="checkbox"].my-check {
            -webkit-appearance: none;
            appearance: none;
            width: 22px; height: 22px; min-width: 22px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            position: relative;
            background: white;
            display: inline-block;
        }
        input[type="checkbox"].my-check:checked {
            background: #b22204;
            border-color: #b22204;
        }
        input[type="checkbox"].my-check:checked::after {
            content: '';
            position: absolute;
            left: 5px; top: 2px;
            width: 8px; height: 12px;
            border: 2.5px solid white;
            border-top: none; border-left: none;
            transform: rotate(40deg);
        }
        input[type="checkbox"].my-check:indeterminate {
            background: #b22204;
            border-color: #b22204;
        }
        input[type="checkbox"].my-check:indeterminate::after {
            content: '';
            position: absolute;
            left: 3px; top: 8px;
            width: 12px; height: 2px;
            background: white; border-radius: 2px;
        }

        .item-card { transition: border-color 0.15s, background 0.15s; border: 2px solid transparent; background: white; }
        .item-card.selected { border-color: #b22204; background: #fff5f3; }
        .qty-btn { transition: transform 0.1s; }
        .qty-btn:active { transform: scale(0.85); }
    </style>
</head>
<body class="min-h-screen pb-48">

<header class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-zinc-100">
    <div class="flex items-center justify-between px-4 h-16 max-w-2xl mx-auto">
        <div class="flex items-center gap-3">
            <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-zinc-100">
                <span class="material-symbols-outlined text-zinc-600">arrow_back</span>
            </button>
            <div>
                <h1 class="headline font-bold text-lg">Keranjang</h1>
                <p class="text-[11px] text-zinc-400"><?= $total_items ?> item tersimpan</p>
            </div>
        </div>
        <?php if ($total_items > 0): ?>
        <button id="btn-hapus" onclick="hapusTerpilih()"
                class="hidden text-xs font-bold text-red-500 px-3 py-1.5 rounded-full hover:bg-red-50 transition-all">
            Hapus Dipilih
        </button>
        <?php endif; ?>
    </div>
</header>

<main class="pt-20 px-4 max-w-2xl mx-auto space-y-3">
    <?php if ($total_items > 0): ?>

    <!-- PILIH SEMUA -->
    <div class="flex items-center justify-between bg-white rounded-2xl px-4 py-3 shadow-sm border border-zinc-100">
        <label class="flex items-center gap-3 cursor-pointer select-none">
            <input type="checkbox" id="selectAll" class="my-check" onchange="toggleAll(this)">
            <span class="font-semibold text-sm text-zinc-700">Pilih Semua</span>
        </label>
        <span id="badge" class="text-xs font-bold text-[#b22204] bg-red-50 px-2.5 py-1 rounded-full opacity-0 transition-opacity">
            0 dipilih
        </span>
    </div>

    <!-- ITEM LIST -->
    <div class="space-y-3">
        <?php foreach ($items as $item):
            $sub = $item['harga'] * $item['qty']; ?>
        <div class="item-card rounded-2xl p-4 shadow-sm" id="card-<?= $item['id_keranjang'] ?>">
            <div class="flex items-center gap-3">

                <!-- Checkbox -->
                <input type="checkbox" class="my-check item-check"
                       id="chk-<?= $item['id_keranjang'] ?>"
                       data-id="<?= $item['id_keranjang'] ?>"
                       onchange="onCheck(this)">

                <!-- Foto (klik = centang) -->
                <label for="chk-<?= $item['id_keranjang'] ?>" class="cursor-pointer flex-shrink-0">
                    <img src="../../uploads/<?= $item['foto'] ?>"
                         alt="<?= htmlspecialchars($item['nama_menu']) ?>"
                         class="w-20 h-20 rounded-xl object-cover bg-zinc-100"
                         onerror="this.src='../../assets/img/default-food.jpg'">
                </label>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="headline font-bold text-sm text-zinc-800"><?= htmlspecialchars($item['nama_menu']) ?></h3>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                <?= !empty($item['catatan']) ? '"'.htmlspecialchars($item['catatan']).'"' : 'Tanpa catatan' ?>
                            </p>
                        </div>
                        <a href="?hapus=<?= $item['id_keranjang'] ?>" onclick="return confirm('Hapus item ini?')"
                           class="material-symbols-outlined text-zinc-300 hover:text-red-500 transition-colors text-xl ml-2 flex-shrink-0">
                            delete
                        </a>
                    </div>

                    <div class="flex items-center justify-between mt-3">
                        <span class="font-extrabold text-[#b22204] text-sm" id="harga-<?= $item['id_keranjang'] ?>">
                            Rp <?= number_format($sub, 0, ',', '.') ?>
                        </span>
                        <div class="flex items-center bg-zinc-100 rounded-full p-0.5 gap-1">
                            <button class="qty-btn w-8 h-8 flex items-center justify-center rounded-full bg-white shadow-sm text-zinc-600"
                                    onclick="ubahQty(<?= $item['id_keranjang'] ?>, 'kurang')">
                                <span class="material-symbols-outlined text-sm">remove</span>
                            </button>
                            <span class="text-sm font-bold min-w-[28px] text-center" id="qty-<?= $item['id_keranjang'] ?>">
                                <?= $item['qty'] ?>
                            </span>
                            <button class="qty-btn w-8 h-8 flex items-center justify-center rounded-full bg-[#b22204] text-white shadow-sm"
                                    onclick="ubahQty(<?= $item['id_keranjang'] ?>, 'tambah')">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button onclick="location.href='dashboard.php'"
            class="w-full flex items-center justify-center gap-2 py-4 border-2 border-dashed border-orange-200 rounded-2xl text-zinc-400 text-sm font-semibold hover:border-[#b22204] hover:text-[#b22204] transition-all group">
        <span class="material-symbols-outlined group-hover:scale-110 transition-transform">add_circle</span>
        Tambah Menu Lainnya
    </button>

    <?php else: ?>
    <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-zinc-200 mt-4">
        <span class="material-symbols-outlined text-6xl text-zinc-200" style="font-variation-settings:'FILL' 1">shopping_cart</span>
        <p class="mt-4 text-zinc-500 font-medium">Keranjangmu masih kosong.</p>
        <button onclick="location.href='dashboard.php'" class="mt-4 text-[#b22204] font-bold text-sm underline underline-offset-4">Mulai Belanja</button>
    </div>
    <?php endif; ?>
</main>

<?php if ($total_items > 0): ?>
<div class="fixed bottom-0 left-0 w-full z-50">
    <div class="bg-white/95 backdrop-blur-xl border-t border-zinc-100 shadow-[0_-8px_30px_rgba(0,0,0,0.06)] px-4 pt-4 pb-8 max-w-2xl mx-auto">
        <div class="flex justify-between items-end mb-3">
            <div>
                <p class="text-xs text-zinc-400">Total Pembayaran</p>
                <p class="headline font-black text-xl text-[#b22204]" id="total-display">Rp 0</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-zinc-400">Item dipilih</p>
                <p class="headline font-bold text-zinc-700" id="qty-display">0 item</p>
            </div>
        </div>
        <button id="btn-checkout" onclick="lanjutCheckout()" disabled
                class="w-full h-14 rounded-full font-bold text-base flex items-center justify-center gap-2 transition-all bg-zinc-200 text-zinc-400 cursor-not-allowed"
                style="font-family:'Plus Jakarta Sans',sans-serif">
            <span class="material-symbols-outlined">shopping_cart_checkout</span>
            <span id="btn-label">Pilih item dulu</span>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
const itemData = {
    <?php foreach ($items as $item): ?>
    <?= $item['id_keranjang'] ?>: { harga: <?= $item['harga'] ?>, qty: <?= $item['qty'] ?> },
    <?php endforeach; ?>
};
const selected = new Set();

function toggleAll(cb) {
    document.querySelectorAll('.item-check').forEach(c => {
        c.checked = cb.checked;
        const id = +c.dataset.id;
        cb.checked ? selected.add(id) : selected.delete(id);
        styleCard(id, cb.checked);
    });
    updateUI();
}

function onCheck(cb) {
    const id = +cb.dataset.id;
    cb.checked ? selected.add(id) : selected.delete(id);
    styleCard(id, cb.checked);
    syncAll();
    updateUI();
}

function styleCard(id, on) {
    const c = document.getElementById('card-' + id);
    if (c) on ? c.classList.add('selected') : c.classList.remove('selected');
}

function syncAll() {
    const all = document.querySelectorAll('.item-check');
    const chk = document.querySelectorAll('.item-check:checked');
    const sa  = document.getElementById('selectAll');
    if (!sa) return;
    if (chk.length === 0)           { sa.checked = false; sa.indeterminate = false; }
    else if (chk.length === all.length) { sa.checked = true;  sa.indeterminate = false; }
    else                            { sa.checked = false; sa.indeterminate = true; }
}

function updateUI() {
    let total = 0, qty = 0;
    selected.forEach(id => {
        if (itemData[id]) { total += itemData[id].harga * itemData[id].qty; qty += itemData[id].qty; }
    });
    document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('qty-display').textContent   = qty + ' item';

    const badge   = document.getElementById('badge');
    const btnHapus = document.getElementById('btn-hapus');
    if (selected.size > 0) {
        badge.textContent = selected.size + ' dipilih';
        badge.classList.remove('opacity-0');
        btnHapus && btnHapus.classList.remove('hidden');
    } else {
        badge.classList.add('opacity-0');
        btnHapus && btnHapus.classList.add('hidden');
    }

    const btn   = document.getElementById('btn-checkout');
    const label = document.getElementById('btn-label');
    if (selected.size > 0) {
        btn.disabled = false;
        btn.style.cssText = 'background:linear-gradient(135deg,#b22204,#d63c1e);color:white;cursor:pointer;box-shadow:0 8px 20px rgba(178,34,4,0.25)';
        btn.classList.remove('bg-zinc-200','text-zinc-400','cursor-not-allowed');
        label.textContent = 'Checkout (' + selected.size + ' item)';
    } else {
        btn.disabled = true;
        btn.style.cssText = '';
        btn.classList.add('bg-zinc-200','text-zinc-400','cursor-not-allowed');
        label.textContent = 'Pilih item dulu';
    }
}

// AJAX qty — tidak reload halaman
async function ubahQty(id, aksi) {
    const fd = new FormData();
    fd.append('ajax_qty', '1');
    fd.append('id_keranjang', id);
    fd.append('aksi', aksi);

    const res  = await fetch('keranjang.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) return;

    if (data.deleted) {
        document.getElementById('card-' + id)?.remove();
        selected.delete(id);
        delete itemData[id];
        syncAll();
        updateUI();
        if (Object.keys(itemData).length === 0) location.reload();
        return;
    }

    itemData[id].qty = data.qty;
    document.getElementById('qty-' + id).textContent   = data.qty;
    document.getElementById('harga-' + id).textContent = 'Rp ' + data.subtotal.toLocaleString('id-ID');
    if (selected.has(id)) updateUI();
}

function lanjutCheckout() {
    if (!selected.size) return;
    location.href = 'checkout.php?selected=' + [...selected].join(',');
}

function hapusTerpilih() {
    if (!selected.size) return;
    if (!confirm('Hapus ' + selected.size + ' item yang dipilih?')) return;
    location.href = 'hapus_banyak.php?ids=' + [...selected].join(',');
}

updateUI();
</script>
</body>
</html>