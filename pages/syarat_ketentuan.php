<?php
session_start();
include(__DIR__ . '/../config/config.php');
include(__DIR__ . '/../includes/language_helper.php');

// Ambil konten syarat ketentuan
$sk_file = __DIR__ . '/../pages/syarat_ketentuan.txt';
$sk_content = '';

if (file_exists($sk_file)) {
    $sk_content = file_get_contents($sk_file);
} else {
    $sk_content = "# Syarat dan Ketentuan Kantin Kita\n\n" .
                 "1. Pengguna harus terdaftar sebagai siswa atau guru untuk menggunakan aplikasi ini.\n\n" .
                 "2. Data pribadi pengguna akan dijaga kerahasiaannya sesuai dengan kebijakan privasi kami.\n\n" .
                 "3. Pengguna bertanggung jawab menjaga keamanan akun pribadi mereka.\n\n" .
                 "4. Aplikasi ini tidak bertanggung jawab atas kesalahan atau kerusakan data pengguna.\n\n" .
                 "5. Pengguna dilarang melakukan transaksi yang mencurigakan atau ilegal.\n\n" .
                 "6. Admin memiliki hak untuk menghapus akun pengguna yang melanggar peraturan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Syarat & Ketentuan - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { 
            font-family: 'Be Vietnam Pro', sans-serif; 
            background: linear-gradient(135deg, #FFF5F0 0%, #F4FBF9 100%);
        }
        .headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .prose-content h1 { 
            font-size: 1.875rem; 
            font-weight: bold; 
            margin: 1.5rem 0 1rem 0; 
            color: #1f2937; 
        }
        .prose-content h2 { 
            font-size: 1.25rem; 
            font-weight: bold; 
            margin: 1.25rem 0 0.75rem 0; 
            color: #374151; 
        }
        .prose-content p { 
            margin: 1rem 0; 
            line-height: 1.6; 
            color: #4b5563; 
        }
        .prose-content ol, .prose-content ul { 
            margin: 1rem 0; 
            padding-left: 2rem; 
        }
        .prose-content li { 
            margin: 0.5rem 0; 
            line-height: 1.6; 
        }
    </style>
</head>
<body class="text-stone-800 min-h-screen">

<!-- Header -->
<header class="bg-white/80 backdrop-blur-xl sticky top-0 z-40 shadow-sm border-b border-stone-200">
    <div class="max-w-4xl mx-auto px-6 py-4 flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg hover:bg-stone-100 text-stone-400">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="text-xl font-bold headline text-stone-900">Syarat & Ketentuan</h1>
            <p class="text-xs text-stone-500">Kantin Kita</p>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="max-w-4xl mx-auto px-6 py-12">
    
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
        <div class="prose-content">
            <?php 
            // Render konten dengan formatting dasar
            $lines = explode("\n", $sk_content);
            $in_list = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line)) {
                    continue;
                }
                
                // Heading 1
                if (substr($line, 0, 2) === '# ') {
                    echo '<h1>' . htmlspecialchars(substr($line, 2)) . '</h1>';
                }
                // Heading 2
                elseif (substr($line, 0, 3) === '## ') {
                    echo '<h2>' . htmlspecialchars(substr($line, 3)) . '</h2>';
                }
                // Numbered list
                elseif (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                    if (!$in_list) {
                        echo '<ol>';
                        $in_list = true;
                    }
                    echo '<li>' . htmlspecialchars($matches[2]) . '</li>';
                }
                // Bullet list
                elseif (substr($line, 0, 2) === '- ') {
                    if (!$in_list) {
                        echo '<ul>';
                        $in_list = true;
                    }
                    echo '<li>' . htmlspecialchars(substr($line, 2)) . '</li>';
                }
                // Regular paragraph
                else {
                    if ($in_list) {
                        echo ($in_list && substr($line, 0, 2) !== '- ' && !preg_match('/^\d+\./', $line)) ? '</ol></ul>' : '';
                        $in_list = false;
                    }
                    echo '<p>' . htmlspecialchars($line) . '</p>';
                }
            }
            
            if ($in_list) {
                echo '</ol></ul>';
            }
            ?>
        </div>

        <!-- Acceptance Section -->
        <div class="mt-12 pt-8 border-t border-stone-200">
            <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-lg">
                <span class="material-symbols-outlined text-blue-600 mt-0.5">info</span>
                <div class="text-sm text-blue-700">
                    <p class="font-bold">Penting</p>
                    <p>Dengan mendaftar di aplikasi Kantin Kita, Anda secara otomatis menerima Syarat & Ketentuan dan Kebijakan Privasi ini.</p>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg font-bold hover:shadow-lg transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
                Kembali
            </a>
        </div>
    </div>

</main>

<!-- Footer -->
<footer class="py-8 text-center text-xs text-stone-500 mt-12">
    <p>&copy; <?= date('Y') ?> Kantin Kita Sekolah Digital</p>
</footer>

</body>
</html>
