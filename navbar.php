<nav class="bottom-nav">
    <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
    </a>
    <a href="keranjang.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'keranjang.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-bag"></i>
    </a>
    <a href="notifikasi.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'notifikasi.php' ? 'active' : '' ?>">
        <i class="fas fa-comment-dots"></i>
    </a>
    <a href="profil.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i>
    </a>
</nav>