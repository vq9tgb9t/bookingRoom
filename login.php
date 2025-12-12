<?php
require 'config.php';
require 'layout.php';

if (!empty($_SESSION['user'])) {
    header('location: index.php');
    exit;
}

$_GET['page'] = 'login';

$loginError = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

$allowedRedirects = ['index.php','status.php','jadwal.php'];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'index.php';
    
}

// proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // akun demo
    if ($username === 'dosen' && $password === '123') {
        $_SESSION['user'] = [
            'username' => $username
        ];
        header('Location: ' . $redirect);
        exit;
    } else {
        $loginError = 'Username atau Password salah';
    }
}

render_header("Login - Booking Ruangan");
?>

<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="login-card">
    <div class="login-logo">
        <img src="assets/img/logoUwin.png" alt="Logo UIN Saizu" class="navbar-logo-img">
    </div>
    <div class="login-title">Selamat Datang</div>
    <div class="login-subtitle">Silakan login untuk membooking ruangan</div>

    <?php if ($loginError): ?>
        <div class="login-error">
            <?= e($loginError) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php?page=login">
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-primary">Masuk</button>
    </form>

    <a href="index.php" class="link-small">Kembali ke Beranda</a>

    <div class="demo-account">
        Gunakan akun demo:<br>
        User: <b>dosen</b> &nbsp; Pass: <b>123</b>
    </div>
</div>

<?php
render_footer();
