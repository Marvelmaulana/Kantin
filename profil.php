<?php
session_start();
include 'config.php';

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// AMBIL DATA USER
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'"));
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Profil</title>

<style>
body { font-family:sans-serif; margin:0; background:#f5f5f5; }

/* HEADER */
.header {
    background:#50c8ff;
    padding:20px;
    color:white;
    text-align:center;
}

/* CONTAINER */
.container {
    padding:20px;
}

/* CARD */
.card {
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    text-align:center;
}

/* AVATAR */
.avatar {
    width:80px;
    height:80px;
    border-radius:50%;
    background:#ddd;
    margin:auto;
    margin-bottom:15px;
}

/* TEXT */
.nama { font-size:18px; font-weight:bold; }
.email { font-size:13px; color:#666; }
.role { margin-top:5px; font-size:12px; color:#888; }

/* BUTTON */
.btn {
    display:block;
    margin:10px 0;
    padding:10px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-size:14px;
}

.logout { background:red; }
.back { background:#50c8ff; }
</style>
</head>

<body>

<div class="header">
    <h3>Profil Saya</h3>
</div>

<div class="container">
    <div class="card">
        
        <div class="avatar"></div>

        <div class="nama"><?= $user['username'] ?></div>
        <div class="email"><?= $user['email'] ?></div>
        <div class="role"><?= $user['role'] ?></div>

        <a href="dashboard.php" class="btn back">← Kembali</a>
        <a href="logout.php" class="btn logout">Logout</a>

    </div>
</div>

</body>
</html>