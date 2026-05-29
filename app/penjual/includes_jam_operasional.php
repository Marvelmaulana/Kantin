<!-- ====================================
     SECTION: PENGATURAN JAM OPERASIONAL
     Merupakan partial HTML untuk dashboard penjual
==================================== -->

<?php
// File ini di-include dari dashboard_penjual.php
// Variabel yang tersedia: $data_kantin, $id_kantin, $kantin_buka
?>

<div class="bg-gradient-to-br from-white to-orange-50 rounded-3xl p-6 shadow-lg border border-orange-100 mb-8">
    
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-orange-100 flex items-center justify-center">
                <i class="fa-solid fa-clock text-orange-600 text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-gray-900">Jam Operasional</h2>
                <p class="text-xs text-gray-500 mt-0.5">Atur waktu buka dan tutup kantin</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-bold
            <?= $kantin_buka ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>">
            <span class="w-2.5 h-2.5 rounded-full <?= $kantin_buka ? 'bg-emerald-500' : 'bg-red-500' ?> animate-pulse"></span>
            <?= $kantin_buka ? 'BUKA' : 'TUTUP' ?>
        </span>
    </div>

    <!-- Grid: Jam Buka/Tutup + Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- FORM PENGATURAN JAM -->
        <div class="md:col-span-2 space-y-4">
            <form id="formJamOperasional" class="space-y-4">

                <!-- Jam Buka -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fa-solid fa-arrow-right-to-bracket text-orange-500 mr-1.5"></i>
                        Jam Buka
                    </label>
                    <div class="relative">
                        <input 
                            type="time" 
                            id="jamBuka"
                            name="jam_buka"
                            value="<?= isset($data_kantin['jam_buka']) ? substr($data_kantin['jam_buka'], 0, 5) : '07:00' ?>"
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-0 font-semibold text-gray-900 bg-white"
                            required
                        />
                        <i class="fa-solid fa-clock absolute right-4 top-3.5 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Jam Tutup -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fa-solid fa-arrow-right-from-bracket text-red-500 mr-1.5"></i>
                        Jam Tutup
                    </label>
                    <div class="relative">
                        <input 
                            type="time" 
                            id="jamTutup"
                            name="jam_tutup"
                            value="<?= isset($data_kantin['jam_tutup']) ? substr($data_kantin['jam_tutup'], 0, 5) : '15:00' ?>"
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-0 font-semibold text-gray-900 bg-white"
                            required
                        />
                        <i class="fa-solid fa-clock absolute right-4 top-3.5 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Tipe Operasi: SELALU OTOMATIS -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fa-solid fa-gears text-purple-500 mr-1.5"></i>
                        Mode Operasional
                    </label>
                    <div class="p-3 rounded-xl bg-purple-50 border-2 border-purple-200">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full bg-purple-500"></div>
                            <span class="font-semibold text-purple-700">✅ OTOMATIS</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-2">Kantin akan otomatis buka pada jam 07:00 dan tutup pada jam 15:00. Tidak ada mode manual.</p>
                    </div>
                    <!-- Hidden input untuk ensure 'otomatis' selalu dikirim -->
                    <input type="hidden" name="tipe_operasi" value="otomatis" />
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-2">
                    <button 
                        type="submit"
                        id="btnSimpanJam"
                        class="flex-1 px-4 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Pengaturan
                    </button>
                    <button 
                        type="reset"
                        class="px-4 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all active:scale-95"
                    >
                        Reset
                    </button>
                </div>

                <!-- Status Alert -->
                <div id="alertJamOperasional" class="hidden rounded-xl p-3 text-sm font-semibold"></div>

            </form>
        </div>

        <!-- INFO BOX -->
        <div class="space-y-3">
            
            <!-- Current Time -->
            <div class="bg-white rounded-2xl p-4 border border-gray-200">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Waktu Sekarang</p>
                <p class="text-3xl font-black text-gray-900" id="currentTime">--:--:--</p>
                <p class="text-xs text-gray-400 mt-1">Zona: Asia/Jakarta</p>
            </div>

            <!-- Status Display -->
            <div class="bg-white rounded-2xl p-4 border border-gray-200">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Status</p>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Kantin:</span>
                        <span class="px-2.5 py-1 rounded-lg font-bold text-xs whitespace-nowrap
                            <?= $kantin_buka ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $kantin_buka ? 'BUKA' : 'TUTUP' ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Mode:</span>
                        <span class="px-2.5 py-1 rounded-lg font-bold text-xs whitespace-nowrap bg-purple-100 text-purple-700">
                            ✅ OTOMATIS
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl p-4 border border-gray-200 space-y-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Aksi Cepat</p>
                
                <!-- Reset Stok -->
                <button 
                    type="button"
                    onclick="resetStokHarian()"
                    class="w-full px-3 py-2 rounded-lg font-bold text-sm text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 transition-all active:scale-95 flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    Reset Stok Harian
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ====================================
     JAVASCRIPT UNTUK JAM OPERASIONAL
==================================== -->
<script>
const idKantin = <?= (int)$id_kantin ?>;
const tipoOperasiCurrent = '<?= ($data_kantin['tipe_operasi'] ?? 'manual') ?>';

// Update current time
function updateCurrentTime() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('currentTime').textContent = `${hh}:${mm}:${ss}`;
}
setInterval(updateCurrentTime, 1000);
updateCurrentTime();

// Form submission
document.getElementById('formJamOperasional').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const jamBuka = document.getElementById('jamBuka').value;
    const jamTutup = document.getElementById('jamTutup').value;
    const tipoOperasi = document.querySelector('input[name="tipe_operasi"]:checked').value;
    
    if (!jamBuka || !jamTutup) {
        showAlert('alertJamOperasional', 'Jam buka dan jam tutup harus diisi', 'error');
        return;
    }
    
    const btn = document.getElementById('btnSimpanJam');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
    
    try {
        const formData = new FormData();
        formData.append('jam_buka', jamBuka);
        formData.append('jam_tutup', jamTutup);
        formData.append('tipe_operasi', tipoOperasi);
        
        const response = await fetch('./api_update_jam.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('alertJamOperasional', '✓ Pengaturan jam operasional berhasil disimpan!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('alertJamOperasional', '✗ ' + (data.message || 'Gagal menyimpan'), 'error');
        }
    } catch (error) {
        showAlert('alertJamOperasional', '✗ Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan';
    }
});

// Reset stok harian
async function resetStokHarian() {
    if (!confirm('⚠️ Reset stok semua menu hari ini? Stok akan menjadi 0 untuk semua menu.')) return;
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mereset...';
    
    try {
        const formData = new FormData();
        
        const response = await fetch('./api_reset_stok.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('alertJamOperasional', 
                '✓ Stok berhasil direset untuk ' + data.data.affected_rows + ' menu', 
                'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('alertJamOperasional', '✗ ' + (data.message || 'Gagal mereset stok'), 'error');
        }
    } catch (error) {
        showAlert('alertJamOperasional', '✗ Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Reset Stok Harian';
    }
}

// Helper: Show alert
function showAlert(elementId, message, type) {
    const alert = document.getElementById(elementId);
    alert.textContent = message;
    alert.className = 'rounded-xl p-3 text-sm font-semibold ' + 
        (type === 'success' 
            ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' 
            : 'bg-red-100 text-red-800 border border-red-300');
    alert.classList.remove('hidden');
    
    setTimeout(() => alert.classList.add('hidden'), 5000);
}
</script>
