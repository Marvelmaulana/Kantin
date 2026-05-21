<?php
session_start();
if (!isset($_SESSION['id_kantin'])) { header("Location: ../auth/login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Menu Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700&family=Be+Vietnam+Pro:wght@400;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#b22204'}}}}</script>
</head>
<body class="bg-[#fff8f6] p-8 font-['Be+Vietnam+Pro']">

    <div class="max-w-xl mx-auto">
        <a href="kelola_menu_penjual.php" class="text-stone-400 hover:text-primary flex items-center gap-2 mb-6 transition-all">
            <span class="material-symbols-outlined">arrow_back</span> Kembali
        </a>

        <div class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-orange-50">
            <h2 class="text-2xl font-black mb-8 text-stone-800 font-['Plus+Jakarta+Sans']">Tambah Menu Jualan</h2>

            <form action="proses_menu.php?aksi=tambah" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Nama Menu</label>
                    <input type="text" name="nama_menu" required class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Harga (Rp)</label>
                        <input type="number" name="harga" required class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Stok</label>
                        <input type="number" name="stok" required min="0" value="0" class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Kategori</label>
                        <select name="kategori" class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500">
                            <option value="Makanan">Makanan</option>
                            <option value="Minuman">Minuman</option>
                            <option value="Camilan">Camilan</option>
                        </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Deskripsi Singkat</label>
                    <textarea name="deskripsi" rows="3" class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Opsi Level / Pilihan</label>
                    <textarea name="opsi_pilihan" rows="3" placeholder="Contoh: Original, Pedas Level 1, Pedas Level 2, Tanpa Es" class="w-full bg-stone-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-orange-500"></textarea>
                    <p class="text-[11px] text-stone-400 mt-2 ml-2">Pisahkan dengan koma atau baris baru.</p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 mb-2 ml-2">Foto Menu</label>
                    <input type="file" name="foto" required class="w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-primary hover:file:bg-orange-100">
                </div>

                <button type="submit" name="simpan" class="w-full bg-primary text-white font-bold py-5 rounded-3xl shadow-lg shadow-red-900/20 active:scale-95 transition-all mt-4">
                    Simpan Menu Sekarang
                </button>
            </form>
        </div>
    </div>
</body>
</html>
