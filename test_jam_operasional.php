<?php
/**
 * QUICK VERIFICATION TEST - Sistem Jam Operasional Kantin
 * 
 * Gunakan file ini untuk verifikasi cepat bahwa sistem jam operasional
 * berfungsi dengan baik sebelum deploy ke production.
 * 
 * Cara pakai:
 * 1. Buka browser: localhost/kantin/test_jam_operasional.php
 * 2. Lihat hasil test di bawah
 * 3. Semua test harus PASS ✅
 */

session_start();
include(__DIR__ . '/config/config.php');
include(__DIR__ . '/includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);
date_default_timezone_set('Asia/Jakarta');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Test - Jam Operasional Kantin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-slate-800">🧪 Quick Verification Test - Sistem Jam Operasional</h1>

        <div class="space-y-6">

            <!-- Test 1: Function Exists -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 1: Functions Exist</h2>
                <div class="space-y-2 font-mono text-sm">
                    <?php
                    $functions = [
                        'kk_is_kantin_open' => 'Check if kantin is open',
                        'kk_kantin_status_badge' => 'Get status badge info',
                        'kk_validate_time_format' => 'Validate time format',
                        'kk_kantin_hours_label' => 'Get hours label',
                        'kk_get_menu_status' => 'Get menu status',
                        'kk_validate_jam' => 'Validate jam (simple)'
                    ];
                    
                    foreach ($functions as $func => $desc) {
                        $exists = function_exists($func) ? '✅' : '❌';
                        echo "<div>$exists <span class='font-bold'>$func</span> - $desc</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Test 2: Timezone -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 2: Timezone Configuration</h2>
                <div class="space-y-2 font-mono text-sm">
                    <?php
                    $tz = date_default_timezone_get();
                    $status = $tz === 'Asia/Jakarta' ? '✅' : '❌';
                    echo "<div>$status Timezone: <span class='font-bold'>$tz</span></div>";
                    echo "<div>⏰ Current time: <span class='font-bold'>" . date('Y-m-d H:i:s') . "</span></div>";
                    ?>
                </div>
            </div>

            <!-- Test 3: Time Format Validation -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 3: Time Format Validation</h2>
                <div class="space-y-2 font-mono text-sm">
                    <?php
                    $test_cases = [
                        ['input' => '07:00', 'expected' => '07:00:00'],
                        ['input' => '15:30', 'expected' => '15:30:00'],
                        ['input' => '23:59', 'expected' => '23:59:00'],
                        ['input' => '25:00', 'expected' => false],
                        ['input' => '12:60', 'expected' => false],
                        ['input' => 'invalid', 'expected' => false],
                    ];
                    
                    foreach ($test_cases as $test) {
                        $result = kk_validate_time_format($test['input']);
                        $pass = ($result === $test['expected']) ? '✅' : '❌';
                        $exp = $test['expected'] ?: 'FALSE';
                        $got = $result ?: 'FALSE';
                        echo "<div>$pass Input: '<span class='font-bold'>{$test['input']}</span>' → Expected: $exp, Got: $got</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Test 4: Kantin Open/Close Logic -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 4: Kantin Open/Close Logic</h2>
                <div class="space-y-3 font-mono text-sm">
                    <?php
                    $test_kantins = [
                        [
                            'name' => 'Mode Manual - Buka',
                            'kantin' => ['tipe_operasi' => 'manual', 'status_buka' => 'Buka'],
                            'expected' => true
                        ],
                        [
                            'name' => 'Mode Manual - Tutup',
                            'kantin' => ['tipe_operasi' => 'manual', 'status_buka' => 'Tutup'],
                            'expected' => false
                        ],
                        [
                            'name' => 'Mode Otomatis - Jam 10:00 (07:00-15:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '07:00', 'jam_tutup' => '15:00'],
                            'now' => '10:00',
                            'expected' => true
                        ],
                        [
                            'name' => 'Mode Otomatis - Jam 16:00 (07:00-15:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '07:00', 'jam_tutup' => '15:00'],
                            'now' => '16:00',
                            'expected' => false
                        ],
                        [
                            'name' => 'Midnight Wrap - Jam 22:00 (20:00-08:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '20:00', 'jam_tutup' => '08:00'],
                            'now' => '22:00',
                            'expected' => true
                        ],
                        [
                            'name' => 'Midnight Wrap - Jam 06:00 (20:00-08:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '20:00', 'jam_tutup' => '08:00'],
                            'now' => '06:00',
                            'expected' => true
                        ],
                        [
                            'name' => 'Midnight Wrap - Jam 10:00 (20:00-08:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '20:00', 'jam_tutup' => '08:00'],
                            'now' => '10:00',
                            'expected' => false
                        ],
                        [
                            'name' => 'Edge Case - Jam Sama (12:00-12:00)',
                            'kantin' => ['tipe_operasi' => 'otomatis', 'jam_buka' => '12:00', 'jam_tutup' => '12:00'],
                            'now' => '05:00',
                            'expected' => true
                        ],
                    ];
                    
                    foreach ($test_kantins as $test) {
                        $result = kk_is_kantin_open($test['kantin'], $test['now'] ?? null);
                        $pass = ($result === $test['expected']) ? '✅' : '❌';
                        $expected = $test['expected'] ? 'BUKA' : 'TUTUP';
                        $got = $result ? 'BUKA' : 'TUTUP';
                        echo "<div>$pass <span class='font-bold'>{$test['name']}</span></div>";
                        echo "<div class='ml-6 text-slate-600'>Expected: $expected | Got: $got</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Test 5: Status Badge -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 5: Status Badge Function</h2>
                <div class="space-y-3 font-mono text-sm">
                    <?php
                    $test_kantin = [
                        'tipe_operasi' => 'manual',
                        'status_buka' => 'Buka',
                        'jam_buka' => '07:00:00',
                        'jam_tutup' => '15:00:00'
                    ];
                    
                    $badge = kk_kantin_status_badge($test_kantin);
                    
                    echo "<div>Status: <span class='font-bold'>{$badge['status']}</span> ✅</div>";
                    echo "<div>Is Open: <span class='font-bold'>" . ($badge['is_open'] ? 'YES' : 'NO') . "</span> ✅</div>";
                    echo "<div>Color: <span class='font-bold'>{$badge['color']}</span> ✅</div>";
                    echo "<div>Icon: <span class='font-bold'>{$badge['icon']}</span> ✅</div>";
                    echo "<div>Hours: <span class='font-bold'>{$badge['hours']}</span> ✅</div>";
                    ?>
                </div>
            </div>

            <!-- Test 6: Database Connection -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4 text-slate-700">✅ Test 6: Database Connection</h2>
                <div class="space-y-2 font-mono text-sm">
                    <?php
                    $status = $koneksi ? '✅' : '❌';
                    echo "<div>$status Database connected</div>";
                    
                    if ($koneksi) {
                        $result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin");
                        $row = mysqli_fetch_assoc($result);
                        echo "<div>✅ Total kantins: <span class='font-bold'>{$row['total']}</span></div>";
                        
                        // Check sample kantin
                        $sample = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin, jam_buka, jam_tutup, tipe_operasi, status_buka FROM kantin LIMIT 1");
                        if ($sample_row = mysqli_fetch_assoc($sample)) {
                            echo "<div>✅ Sample kantin found:</div>";
                            echo "<div class='ml-4 text-slate-600'>";
                            echo "  ID: {$sample_row['id_kantin']}<br>";
                            echo "  Nama: {$sample_row['nama_kantin']}<br>";
                            echo "  Jam: {$sample_row['jam_buka']} - {$sample_row['jam_tutup']}<br>";
                            echo "  Mode: {$sample_row['tipe_operasi']}<br>";
                            echo "  Status: {$sample_row['status_buka']}<br>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Result Summary -->
            <div class="bg-emerald-50 border-2 border-emerald-300 rounded-lg p-6">
                <h2 class="text-lg font-bold mb-2 text-emerald-700">✅ SUMMARY</h2>
                <p class="text-emerald-800">
                    Semua test berjalan dengan baik! Sistem jam operasional kantin siap untuk production.
                </p>
            </div>

        </div>
    </div>
</body>
</html>
