# Frontend Dashboard Kantin - Tailwind CSS

```php
<?php
// Contoh data backend
$nama_kantin = "Eni's Kitchen";
$jam_buka = "08.00 - 16.00";
$rating = "4.8";
$total_ulasan = 128;

$pesanan_hari_ini = 12;
$pendapatan_hari_ini = 245000;
$produk_aktif = 24;

$pesanan_masuk = [
    [
        'kode' => 'AORD-8921',
        'nama' => 'Andrean',
        'menu' => '3 menu',
        'harga' => 45000,
        'warna' => 'bg-pink-100 text-pink-600',
        'inisial' => 'AN'
    ],
    [
        'kode' => 'AORD-8922',
        'nama' => 'Salsa',
        'menu' => '2 menu',
        'harga' => 28000,
        'warna' => 'bg-green-100 text-green-600',
        'inisial' => 'SA'
    ],
    [
        'kode' => 'AORD-8923',
        'nama' => 'Ricky',
        'menu' => '4 menu',
        'harga' => 60000,
        'warna' => 'bg-purple-100 text-purple-600',
        'inisial' => 'RI'
    ],
    [
        'kode' => 'AORD-8924',
        'nama' => 'Dania',
        'menu' => '1 menu',
        'harga' => 15000,
        'warna' => 'bg-yellow-100 text-yellow-600',
        'inisial' => 'DA'
    ]
];

$produk = [
    [
        'nama' => 'Nasi Goreng Spesial',
        'harga' => 15000,
        'status' => 'Tersedia',
        'gambar' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?q=80&w=300',
        'warna' => 'bg-green-100 text-green-600'
    ],
    [
        'nama' => 'Mie Goreng',
        'harga' => 12000,
        'status' => 'Tersedia',
        'gambar' => 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?q=80&w=300',
        'warna' => 'bg-green-100 text-green-600'
    ],
    [
        'nama' => 'Es Teh Manis',
        'harga' => 5000,
        'status' => 'Tersedia',
        'gambar' => 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?q=80&w=300',
        'warna' => 'bg-green-100 text-green-600'
    ],
    [
        'nama' => 'Bakso Kuah',
        'harga' => 12000,
        'status' => 'Habis',
        'gambar' => 'https://images.unsplash.com/photo-1547592180-85f173990554?q=80&w=300',
        'warna' => 'bg-red-100 text-red-500'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kantin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f8f8;
        }

        .sidebar-shadow {
            box-shadow: 0 0 20px rgba(0,0,0,0.04);
        }

        .card-shadow {
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-[250px] bg-white sidebar-shadow px-5 py-6 flex flex-col justify-between fixed h-full">

        <div>
            <div class="flex items-center gap-3 mb-10">
                <div class="w-11 h-11 rounded-xl bg-orange-500 flex items-center justify-center text-white text-xl">
                    <i class="fa-solid fa-store"></i>
                </div>

                <div>
                    <h1 class="font-extrabold text-orange-500 text-2xl leading-none">KANTIN</h1>
                    <p class="font-bold text-gray-800 text-xl leading-none mt-1">KITA</p>
                </div>
            </div>

            <nav class="space-y-3">

                <a href="#" class="bg-orange-500 text-white flex items-center gap-3 px-4 py-3 rounded-xl font-medium">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>

                <a href="#" class="text-gray-600 hover:bg-orange-50 flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                    <i class="fa-regular fa-clipboard"></i>
                    Pesanan
                </a>

                <a href="#" class="text-gray-600 hover:bg-orange-50 flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                    <i class="fa-solid fa-box"></i>
                    Produk
                </a>

                <a href="#" class="text-gray-600 hover:bg-orange-50 flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                    <i class="fa-regular fa-user"></i>
                    Profil Kantin
                </a>

                <a href="#" class="text-gray-600 hover:bg-orange-50 flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                    <i class="fa-solid fa-gear"></i>
                    Pengaturan
                </a>
            </nav>
        </div>

        <a href="#" class="text-gray-500 flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 hover:text-red-500 transition">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Keluar
        </a>
    </aside>


    <!-- CONTENT -->
    <main class="flex-1 ml-[250px] p-6">

        <!-- TOPBAR -->
        <div class="flex justify-end items-center mb-6">
            <div class="flex items-center gap-5">

                <button class="relative text-gray-600 text-xl">
                    <i class="fa-regular fa-bell"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center">
                        3
                    </span>
                </button>

                <div class="flex items-center gap-3">
                    <img src="https://i.pravatar.cc/100" class="w-12 h-12 rounded-full object-cover">

                    <div>
                        <h2 class="font-semibold text-sm">Eni's Kitchen</h2>
                        <p class="text-xs text-gray-500">Penjual</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- HERO -->
        <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-3xl px-8 py-7 flex items-center justify-between overflow-hidden relative card-shadow">

            <div class="z-10">
                <p class="text-gray-700 mb-1">Selamat datang di</p>
                <h1 class="text-5xl font-extrabold text-orange-500 mb-3">
                    <?= $nama_kantin ?>
                </h1>

                <p class="text-gray-600 mb-4">
                    Makan enak, harga bersahabat!
                </p>

                <div class="flex items-center gap-5 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="fa-regular fa-clock"></i>
                        <?= $jam_buka ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-star text-yellow-400"></i>
                        <?= $rating ?> (<?= $total_ulasan ?> ulasan)
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-5 z-10">
                <img src="https://images.unsplash.com/photo-1512058564366-18510be2db19?q=80&w=400" class="w-56 rounded-2xl object-cover shadow-lg">

                <img src="https://images.unsplash.com/photo-1512058454905-6b841e7ad132?q=80&w=300" class="w-40 rounded-2xl object-cover shadow-lg">
            </div>
        </div>


        <!-- CARD STAT -->
        <div class="grid grid-cols-3 gap-5 mt-6">

            <div class="bg-white rounded-2xl p-5 card-shadow flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pesanan Hari Ini</p>
                    <h2 class="text-4xl font-bold mt-2">
                        <?= $pesanan_hari_ini ?>
                    </h2>
                    <span class="text-green-500 text-sm font-medium">pesanan</span>
                </div>

                <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center text-green-600 text-xl">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>


            <div class="bg-white rounded-2xl p-5 card-shadow flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pendapatan Hari Ini</p>
                    <h2 class="text-4xl font-bold mt-2">
                        Rp <?= number_format($pendapatan_hari_ini,0,',','.') ?>
                    </h2>
                    <span class="text-orange-500 text-sm font-medium">total</span>
                </div>

                <div class="w-14 h-14 rounded-2xl bg-yellow-100 flex items-center justify-center text-yellow-500 text-xl">
                    Rp
                </div>
            </div>


            <div class="bg-white rounded-2xl p-5 card-shadow flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Produk Aktif</p>
                    <h2 class="text-4xl font-bold mt-2">
                        <?= $produk_aktif ?>
                    </h2>
                    <span class="text-purple-500 text-sm font-medium">menu</span>
                </div>

                <div class="w-14 h-14 rounded-2xl bg-purple-100 flex items-center justify-center text-purple-600 text-xl">
                    <i class="fa-solid fa-cube"></i>
                </div>
            </div>
        </div>


        <!-- CONTENT GRID -->
        <div class="grid grid-cols-2 gap-5 mt-6">

            <!-- PESANAN -->
            <div class="bg-white rounded-2xl p-5 card-shadow">

                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-lg">Pesanan Masuk</h2>
                    <a href="#" class="text-orange-500 text-sm font-medium">Lihat Semua</a>
                </div>

                <div class="space-y-4">

                    <?php foreach($pesanan_masuk as $item): ?>

                    <div class="flex items-center justify-between border-b pb-4">

                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold <?= $item['warna'] ?>">
                                <?= $item['inisial'] ?>
                            </div>

                            <div>
                                <h3 class="font-semibold text-sm">
                                    <?= $item['kode'] ?>
                                </h3>

                                <p class="text-sm text-gray-500">
                                    <?= $item['nama'] ?>
                                </p>
                            </div>
                        </div>

                        <div class="text-sm text-gray-600 text-right">
                            <p><?= $item['menu'] ?></p>
                            <p class="font-semibold">
                                Rp <?= number_format($item['harga'],0,',','.') ?>
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-100">
                                Tolak
                            </button>

                            <button class="px-4 py-2 rounded-lg bg-orange-500 text-white text-sm hover:bg-orange-600">
                                Terima
                            </button>
                        </div>
                    </div>

                    <?php endforeach; ?>
                </div>

                <button class="w-full mt-5 border rounded-xl py-3 font-medium hover:bg-gray-50 transition">
                    Lihat Semua Pesanan
                </button>
            </div>


            <!-- PRODUK -->
            <div class="bg-white rounded-2xl p-5 card-shadow">

                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-lg">Produk Saya</h2>
                    <a href="#" class="text-orange-500 text-sm font-medium">Kelola Produk</a>
                </div>

                <div class="space-y-4">

                    <?php foreach($produk as $item): ?>

                    <div class="flex items-center justify-between border-b pb-4">

                        <div class="flex items-center gap-3">
                            <img src="<?= $item['gambar'] ?>" class="w-16 h-16 rounded-xl object-cover">

                            <div>
                                <h3 class="font-semibold text-sm">
                                    <?= $item['nama'] ?>
                                </h3>

                                <p class="text-gray-500 text-sm">
                                    Rp <?= number_format($item['harga'],0,',','.') ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $item['warna'] ?>">
                                <?= $item['status'] ?>
                            </span>

                            <button>
                                <i class="fa-solid fa-ellipsis-vertical text-gray-500"></i>
                            </button>
                        </div>
                    </div>

                    <?php endforeach; ?>
                </div>

                <button class="w-full mt-5 border rounded-xl py-3 font-medium hover:bg-gray-50 transition">
                    Lihat Semua Produk
                </button>
            </div>
        </div>


        <!-- GRAFIK -->
        <div class="bg-white rounded-2xl p-6 mt-6 card-shadow">

            <div class="flex items-center justify-between mb-6">

                <div>
                    <h2 class="font-bold text-lg mb-1">Grafik Penjualan</h2>

                    <div>
                        <p class="text-gray-500 text-sm">Total Pendapatan</p>

                        <div class="flex items-center gap-3 mt-1">
                            <h3 class="text-3xl font-bold">
                                Rp 1.245.000
                            </h3>

                            <span class="text-green-500 text-sm font-medium">
                                +18%
                            </span>
                        </div>
                    </div>
                </div>

                <select class="border rounded-xl px-4 py-2 text-sm outline-none">
                    <option>7 Hari Terakhir</option>
                    <option>30 Hari</option>
                </select>
            </div>

            <canvas id="salesChart" height="100"></canvas>
        </div>
    </main>
</div>


<script>
const ctx = document.getElementById('salesChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['16 Mei', '17 Mei', '18 Mei', '19 Mei', '20 Mei', '21 Mei', '22 Mei'],
        datasets: [{
            label: 'Pendapatan',
            data: [100000, 180000, 120000, 150000, 220000, 300000, 110000],
            borderColor: '#f97316',
            backgroundColor: 'rgba(249,115,22,0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f97316',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f1f1'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

</body>
</html>
```

# Cara Gabungkan Dengan Backend Kamu

## 1. Simpan File

Contoh:

```bash
app/penjual/dashboard.php
```

---

## 2. Ambil Data Dari Database

Ganti data dummy:

```php
$produk = [];
$pesanan_masuk = [];
```

Dengan query database milikmu.

Contoh:

```php
$query = mysqli_query($koneksi, "SELECT * FROM produk");
while($row = mysqli_fetch_assoc($query)){
    $produk[] = $row;
}
```

---

## 3. Kalau Sudah Punya Sidebar

Ambil bagian:

```html
<aside>
```

Sampai:

```html
</aside>
```

Lalu ganti dengan:

```php
<?php include '../../includes/sidebar_penjual.php'; ?>
```

---

## 4. Install Tailwind Kalau Mau Lebih Profesional

Kalau sementara cukup pakai CDN:

```html
<script src="https://cdn.tailwindcss.com"></script>
```

Sudah cukup.

---

## 5. Yang Bisa Ditambah Lagi

Fitur yang cocok buat tugas akhir:

* Search menu
* Filter kategori
* Upload banner kantin
* Edit profil kantin
* Status buka/tutup
* Grafik mingguan
* Notifikasi pesanan realtime
* Rating pembeli
* Riwayat transaksi
* QR pembayaran
* Export laporan PDF
* Dark mode
* Responsive mobile
