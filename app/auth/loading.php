<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// 🔥 pakai path absolut
if ($role == 'penjual') {
    $tujuan = "/kantin/app/penjual/dashboard_penjual.php";
} elseif ($role == 'admin') {
    $tujuan = "/kantin/app/admin/dashboard_admin.php";
} else {
    $tujuan = "/kantin/app/pembeli/dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Kita</title>

    <meta http-equiv="refresh" content="1;url=<?= $tujuan; ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Comic Sans MS', cursive, sans-serif; /* Sesuaikan font logo kamu */
        }

        body {
            background-color: #ff8056; /* Warna biru sesuai gambar figma kamu */
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .logo-container img {
            width: 70%;
            height: auto;
        }

        h1 {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .footer-text {
            position: absolute;
            bottom: 40px;
            font-size: 14px;
            opacity: 0.8;
        }

        /* Animasi sedikit agar logo berdenyut pelan */
        .logo-container {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <div class="logo-container">
        <!-- Ganti src dengan path logo kantin kamu -->
        <img src="https://cdn-icons-png.flaticon.com/512/3448/3448609.png" alt="Logo">
    </div>

    <h1>Kantin Kita</h1>

    <div class="footer-text">
        K2 Project
    </div>

</body>
</html>