<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Lupa Password</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fff8f6}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center px-5">
<form action="proses_lupa.php" method="POST" class="w-full max-w-md bg-white rounded-[2rem] border border-orange-100 shadow-xl shadow-red-900/5 p-6 space-y-5">
    <div>
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#b22204]">Reset Password</p>
        <h1 class="headline text-2xl font-black mt-1">Masukkan email akun</h1>
        <p class="text-sm text-stone-500 mt-2">Sistem akan membuat token reset. Di server lokal, token ditampilkan langsung supaya bisa dites tanpa SMTP.</p>
    </div>
    <?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 text-red-600 rounded-2xl p-3 text-sm font-bold">Email tidak ditemukan.</div>
    <?php endif; ?>
    <div>
        <label class="text-[11px] font-black uppercase tracking-wider text-stone-400">Email</label>
        <input type="email" name="email" class="mt-2 w-full bg-stone-100 rounded-2xl border-none py-4 px-4 text-sm" required/>
    </div>
    <button type="submit" name="cek_lupa" class="w-full bg-[#b22204] text-white headline font-black py-4 rounded-2xl">Buat Token Reset</button>
    <a href="login.php" class="block text-center text-sm font-bold text-stone-400">Kembali ke Login</a>
</form>
</body>
</html>
