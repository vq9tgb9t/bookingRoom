<?php
require 'layout.php';
require 'config.php';

$_GET['page'] = 'login';

$loginError = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

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
        $loginError = 'Username atau password salah.';
    }
}

render_header("Login - Booking Ruangan");
?>

<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="login-card">
    <div class="login-logo">UIN</div>
    <div class="login-title">Selamat Datang</div>
    <div class="login-subtitle">Silakan login untuk membooking ruangan</div>

    <?php if ($loginError): ?>
        <div style="color:#c62828; font-size:12px; margin-bottom:10px;">
            <?= htmlspecialchars($loginError) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php?page=login">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

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
