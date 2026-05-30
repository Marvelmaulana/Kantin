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
    <title>Kantin Kita - Loading</title>

    <meta http-equiv="refresh" content="2;url=<?= $tujuan; ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #FFD89B 0%, #FF9D6B 50%, #FF6B35 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi background */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .loading-container {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            padding: 20px;
            max-width: 500px;
            width: 100%;
        }

        .logo-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .kantin-logo {
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        .kantin-logo img {
            width: 80%;
            height: auto;
            object-fit: contain;
        }

        .text-container h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2C3E50;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .text-container p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 10px;
            font-weight: 500;
        }

        /* Loading spinner */
        .spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: white;
            animation: pulse-dot 1.4s infinite ease-in-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        .dot:nth-child(3) {
            animation-delay: 0s;
        }

        /* Animations */
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes pulse-dot {
            0%, 60%, 100% {
                opacity: 0.3;
                transform: scale(0.8);
            }
            30% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-wrapper {
                width: 120px;
                height: 120px;
            }

            .text-container h1 {
                font-size: 2rem;
            }

            .loading-container {
                gap: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .logo-wrapper {
                width: 100px;
                height: 100px;
            }

            .text-container h1 {
                font-size: 1.5rem;
            }

            .text-container p {
                font-size: 0.9rem;
            }

            .dot {
                width: 10px;
                height: 10px;
            }

            .loading-container {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="loading-container">
        <div class="logo-wrapper">
            <div class="kantin-logo">
                <img src="/kantin/uploads/logo/logo_kantin_kita.png" alt="Kantin Kita Logo">
            </div>
        </div>

        <!-- <div class="text-container">
            <h1>KANTIN KITA</h1>
            <p>Memuat aplikasi...</p>
        </div> -->

        <!-- <div class="spinner">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div> -->
    </div>

</body>
</html>