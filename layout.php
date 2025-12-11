<?php
// layout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function render_header($title = "UniSpace - Campus Booking System") {
    $currentPage = $_GET["page"] ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/layout">
    <?php
    if (in_array($currentPage, ['status', 'jadwal'], true)):?>
    <link rel="stylesheet" href="assets/css/status.css">
    <?php endif; ?>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">
        <div class="navbar-logo">UIN</div>
        <div>
            <div class="navbar-title-main">UIN SAIZU</div>
            <div class="navbar-title-sub">KAMPUS 2</div>
        </div>
    </div>
    <div class="navbar-center">
        <a href="index.php" class="<?= ($_GET['page'] ?? 'home') === 'home' ? 'active' : '' ?>">Beranda</a>
        <a href="status.php?page=status" class="<?= ($_GET['page'] ?? '') === 'status' ? 'active' : '' ?>">Status Ruang</a>
        <a href="jadwal.php?page=jadwal" class="<?= ($_GET['page'] ?? '') === 'jadwal' ? 'active' : '' ?>">Jadwal</a>
    </div>
    <div class="navbar-right">
        <?php if (!empty($_SESSION['user'])): ?>
            <span style="font-size:12px; margin-right:8px;">
                Halo, <?= htmlspecialchars($_SESSION['user']['username']) ?>
            </span>
            <a href="logout.php">LOGOUT</a>
        <?php else: ?>
            <a href="login.php?page=login">LOGIN</a>
        <?php endif; ?>
    </div>
</div>

<div class="page-container">
<?php
}

function render_footer() {
?>
</div>

<div class="footer">
    © 2025 UIN SAIZU Booking. Sistem Informasi Kampus.
</div>

</body>
</html>
<?php
}
