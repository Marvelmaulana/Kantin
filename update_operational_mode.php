<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'config/config.php';

echo "🔧 UPDATE: Mengubah semua kantin ke mode OTOMATIS...\n\n";

// Update all kantins to use automatic mode
$result = mysqli_query($koneksi, "UPDATE kantin SET tipe_operasi = 'otomatis'");
$affected = mysqli_affected_rows($koneksi);
echo "✅ Perubahan database selesai!\n";
echo "Jumlah kantin yang diupdate: $affected\n\n";

// Verify
$check = mysqli_query($koneksi, "SELECT COUNT(*) as total, 
  SUM(CASE WHEN tipe_operasi = 'otomatis' THEN 1 ELSE 0 END) as otomatis,
  SUM(CASE WHEN tipe_operasi = 'manual' THEN 1 ELSE 0 END) as manual
  FROM kantin");
$data = mysqli_fetch_assoc($check);

echo "📊 STATISTIK DATABASE:\n";
echo "├─ Total kantin: " . $data['total'] . "\n";
echo "├─ Mode Otomatis: " . $data['otomatis'] . "\n";
echo "└─ Mode Manual: " . ($data['manual'] ?? 0) . "\n";

echo "\n✅ Semua kantin sekarang hanya menggunakan mode OTOMATIS!\n";
echo "   Status akan berubah otomatis berdasarkan jam operasional (07:00 - 15:00)\n";
?>
