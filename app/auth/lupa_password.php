<form action="proses_lupa.php" method="POST" class="space-y-6">
    <div class="space-y-2">
        <label class="text-[11px] font-bold uppercase text-gray-500">Username Anda</label>
        <input type="text" name="username" class="w-full bg-gray-100 rounded-xl py-4 px-4" required/>
    </div>

    <div class="space-y-2">
        <label class="text-[11px] font-bold uppercase text-gray-500">Siapa Nama Ibu Kandung Anda? (Verifikasi)</label>
        <input type="text" name="jawaban" class="w-full bg-gray-100 rounded-xl py-4 px-4" required/>
    </div>

    <button type="submit" name="cek_lupa" class="w-full bg-primary text-white font-bold py-4 rounded-full">
        Verifikasi & Reset
    </button>
</form>