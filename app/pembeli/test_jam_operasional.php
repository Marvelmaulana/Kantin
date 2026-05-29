<?php
/**
 * TEST PEMBELI - Jam Operasional Kantin (Interactive)
 * 
 * Panduan test manual untuk pembeli:
 * 1. Login sebagai pembeli di: /kantin/app/auth/login.php
 * 2. Akses halaman-halaman di bawah untuk test berbagai skenario
 * 3. Verifikasi tampilan badge status dan tombol pesan
 */

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);
date_default_timezone_set('Asia/Jakarta');

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get pembeli info
$id_user = (int)$_SESSION['id_user'];
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user LIMIT 1");
$user = mysqli_fetch_assoc($q_user);

// Get all kantins with their status
$q_kantins = mysqli_query($koneksi, "
    SELECT k.*, 
           COUNT(DISTINCT m.id_menu) as total_menu,
           COUNT(DISTINCT r.id_rating) as total_rating,
           COALESCE(AVG(r.nilai_rating), 0) as avg_rating
    FROM kantin k
    LEFT JOIN menu m ON k.id_kantin = m.id_kantin AND COALESCE(m.status,'Tersedia') <> 'Habis'
    LEFT JOIN rating_menu r ON m.id_menu = r.id_menu
    GROUP BY k.id_kantin
    ORDER BY k.nama_kantin
");

$kantins = [];
while ($k = mysqli_fetch_assoc($q_kantins)) {
    $kantins[] = $k;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Pembeli - Jam Operasional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
</head>
<body class="bg-slate-50">
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h1 class="text-3xl font-bold text-slate-800 mb-2">🧪 Test Pembeli - Jam Operasional Kantin</h1>
            <p class="text-slate-600 mb-4">Halo <span class="font-bold"><?= htmlspecialchars($user['username'] ?? 'Pembeli') ?></span>! 
            Verifikasi status jam operasional di berbagai kantin.</p>
            <p class="text-sm text-slate-500">⏰ Waktu sekarang: <span class="font-mono font-bold"><?= date('H:i:s (D, M d Y)', strtotime(date('Y-m-d H:i:s'))) ?></span></p>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-lg font-bold mb-4 text-slate-700">📍 Quick Navigation</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="../../app/pembeli/dashboard.php" class="p-4 border-2 border-orange-300 rounded-lg hover:bg-orange-50 transition">
                    <div class="font-bold text-orange-600 mb-1">Dashboard Pembeli</div>
                    <div class="text-sm text-slate-600">Lihat semua kantin & menu</div>
                </a>
                
                <a href="javascript:alert('Pilih kantin dari daftar di bawah untuk test')" class="p-4 border-2 border-blue-300 rounded-lg hover:bg-blue-50 transition">
                    <div class="font-bold text-blue-600 mb-1">Kantin Detail</div>
                    <div class="text-sm text-slate-600">Lihat badge BUKA/TUTUP</div>
                </a>

                <a href="javascript:alert('Pilih menu dari kantin untuk test tombol')" class="p-4 border-2 border-green-300 rounded-lg hover:bg-green-50 transition">
                    <div class="font-bold text-green-600 mb-1">Detail Menu</div>
                    <div class="text-sm text-slate-600">Test tombol Pesan aktif/disabled</div>
                </a>
            </div>
        </div>

        <!-- Kantin List -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-lg font-bold mb-6 text-slate-700">🏪 Daftar Kantin & Status</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-100 border-b-2 border-slate-300">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Kantin</th>
                            <th class="text-left px-4 py-3 font-bold">Mode</th>
                            <th class="text-center px-4 py-3 font-bold">Jam Operasional</th>
                            <th class="text-center px-4 py-3 font-bold">Status Sekarang</th>
                            <th class="text-left px-4 py-3 font-bold">Menu</th>
                            <th class="text-center px-4 py-3 font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kantins as $kantin): 
                            $badge = kk_kantin_status_badge($kantin);
                            $jam_label = kk_kantin_hours_label($kantin);
                            $mode = $kantin['tipe_operasi'] === 'manual' ? '🔘 Manual' : '⚙️ Otomatis';
                            $status_color = $badge['is_open'] ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-red-100 text-red-800 border-red-300';
                            $status_icon = $badge['icon'];
                            $detail_url = "../../app/pembeli/kantin_detail.php?id=" . $kantin['id_kantin'];
                        ?>
                        <tr class="border-b border-slate-200 hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-700"><?= htmlspecialchars($kantin['nama_kantin']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= $mode ?></td>
                            <td class="px-4 py-3 text-center font-mono text-sm text-slate-600"><?= $jam_label ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold border-2 <?= $status_color ?>">
                                    <span class="material-symbols-outlined text-base"><?= $status_icon ?></span>
                                    <?= $badge['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <span class="text-sm"><?= $kantin['total_menu'] ?> menu</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="<?= $detail_url ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm font-bold hover:bg-blue-600 transition">
                                    <span class="material-symbols-outlined text-base">open_in_new</span>
                                    Lihat
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Test Scenario -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <!-- Scenario 1 -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-bold text-slate-800 mb-3">✅ Scenario 1: Kantin BUKA</h3>
                <div class="space-y-2 text-sm text-slate-700 mb-4">
                    <p><strong>Expected:</strong></p>
                    <ul class="list-disc list-inside space-y-1 text-slate-600">
                        <li>Badge status: <span class="inline-block px-2 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">✓ BUKA</span></li>
                        <li>Tombol "Pesan" di menu: <strong>AKTIF</strong> (bisa diklik)</li>
                        <li>Bisa add ke keranjang & checkout</li>
                        <li>Jam operasional ditampilkan dengan warna hijau</li>
                    </ul>
                </div>
                <div class="bg-emerald-50 p-3 rounded border border-emerald-200">
                    <p class="text-xs font-mono">
                        <strong>Kondisi:</strong> Mode Manual dengan Status "Buka"<br/>
                        atau Mode Otomatis dengan waktu dalam jam operasional
                    </p>
                </div>
            </div>

            <!-- Scenario 2 -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-bold text-slate-800 mb-3">❌ Scenario 2: Kantin TUTUP</h3>
                <div class="space-y-2 text-sm text-slate-700 mb-4">
                    <p><strong>Expected:</strong></p>
                    <ul class="list-disc list-inside space-y-1 text-slate-600">
                        <li>Badge status: <span class="inline-block px-2 py-1 rounded bg-red-100 text-red-800 text-xs font-bold">✕ TUTUP</span></li>
                        <li>Tombol "Pesan" di menu: <strong>DISABLED</strong> (tidak bisa diklik)</li>
                        <li>Checkout ditolak dengan pesan error</li>
                        <li>Jam operasional ditampilkan dengan warna merah</li>
                    </ul>
                </div>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <p class="text-xs font-mono">
                        <strong>Kondisi:</strong> Mode Manual dengan Status "Tutup"<br/>
                        atau Mode Otomatis dengan waktu di luar jam operasional
                    </p>
                </div>
            </div>

        </div>

        <!-- Test Steps -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-lg font-bold mb-6 text-slate-700">🔄 Langkah-langkah Test</h2>
            
            <div class="space-y-6">
                
                <div class="border-l-4 border-blue-500 pl-6">
                    <h3 class="font-bold text-slate-800 mb-2">Step 1️⃣ : Lihat Dashboard</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 text-sm">
                        <li>Klik tombol <strong>"Dashboard Pembeli"</strong> di atas</li>
                        <li>Perhatikan badge status kantin di card menu</li>
                        <li>Verifikasi: Ada badge hijau "BUKA" atau merah "TUTUP"</li>
                    </ol>
                </div>

                <div class="border-l-4 border-green-500 pl-6">
                    <h3 class="font-bold text-slate-800 mb-2">Step 2️⃣ : Buka Detail Kantin</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 text-sm">
                        <li>Di table di atas, klik tombol <strong>"Lihat"</strong> untuk setiap kantin</li>
                        <li>Lihat halaman detail kantin</li>
                        <li>Verifikasi: 
                            <ul class="list-disc list-inside ml-4 mt-1 space-y-1">
                                <li>Badge BUKA/TUTUP ditampilkan prominent</li>
                                <li>Jam operasional ditampilkan (contoh: "07:00 - 15:00")</li>
                                <li>Warna badge sesuai status</li>
                            </ul>
                        </li>
                    </ol>
                </div>

                <div class="border-l-4 border-orange-500 pl-6">
                    <h3 class="font-bold text-slate-800 mb-2">Step 3️⃣ : Cek Detail Menu</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 text-sm">
                        <li>Di halaman detail kantin, klik salah satu menu</li>
                        <li>Verifikasi state tombol "Pesan":
                            <ul class="list-disc list-inside ml-4 mt-1 space-y-1">
                                <li>🟢 Jika kantin <strong>BUKA</strong>: Tombol warna orange, bisa diklik</li>
                                <li>🔴 Jika kantin <strong>TUTUP</strong>: Tombol disabled/gray, tidak bisa diklik</li>
                                <li>Info text menunjukkan jam operasional kantin</li>
                            </ul>
                        </li>
                    </ol>
                </div>

                <div class="border-l-4 border-purple-500 pl-6">
                    <h3 class="font-bold text-slate-800 mb-2">Step 4️⃣ : Test Checkout (Optional)</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 text-sm">
                        <li>Jika kantin <strong>BUKA</strong>:
                            <ul class="list-disc list-inside ml-4 mt-1">
                                <li>Klik tombol "Pesan" → add ke keranjang</li>
                                <li>Proceed checkout → harus BERHASIL ✅</li>
                            </ul>
                        </li>
                        <li>Jika kantin <strong>TUTUP</strong>:
                            <ul class="list-disc list-inside ml-4 mt-1">
                                <li>Tombol "Pesan" harus DISABLED</li>
                                <li>Jika dicoba add item (via console) → checkout GAGAL ❌</li>
                                <li>Error: "Kantin sedang tutup"</li>
                            </ul>
                        </li>
                    </ol>
                </div>

            </div>
        </div>

        <!-- Checklist -->
        <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-6 mt-8">
            <h2 class="text-lg font-bold mb-4 text-blue-900">✅ Test Checklist</h2>
            <div class="space-y-2 text-sm text-blue-900">
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Badge status visible di dashboard (✓ BUKA atau ✕ TUTUP)
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Badge BUKA = hijau, TUTUP = merah
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Jam operasional ditampilkan dengan benar (format HH:MM)
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Tombol "Pesan" aktif ketika kantin BUKA
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Tombol "Pesan" disabled ketika kantin TUTUP
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Bisa checkout ketika kantin BUKA
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Checkout ditolak ketika kantin TUTUP (error message muncul)
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Mode Manual: Status bisa berbeda di setiap waktu
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Mode Otomatis: Status berubah berdasarkan waktu sekarang
                </label>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-2 rounded">
                    <input type="checkbox" class="w-4 h-4"> Jam operasional konsisten di semua halaman
                </label>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-amber-50 border-l-4 border-amber-500 p-6 mt-8 rounded">
            <h3 class="font-bold text-amber-900 mb-2">💡 Tips Testing</h3>
            <ul class="text-sm text-amber-900 space-y-1">
                <li>✨ <strong>Timezone:</strong> Sistem menggunakan timezone Asia/Jakarta</li>
                <li>✨ <strong>Mode Manual:</strong> Penjual bisa set status secara manual (tidak tergantung waktu)</li>
                <li>✨ <strong>Mode Otomatis:</strong> Status otomatis berubah sesuai jam buka/tutup</li>
                <li>✨ <strong>Reload:</strong> Refresh halaman untuk melihat perubahan status terbaru</li>
                <li>✨ <strong>Dev Tools:</strong> Buka F12 Console untuk melihat debug info jika ada masalah</li>
            </ul>
        </div>

    </div>
</body>
</html>
