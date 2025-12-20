<?php
require 'config.php';
require 'layout.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$_GET['page'] = 'login';

$loginError = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

$redirectPath = basename(parse_url($redirect, PHP_URL_PATH) ?? '');
$allowedRedirects = ['index.php', 'status.php', 'jadwal.php', 'admin_dashboard.php'];

if (!in_array($redirectPath, $allowedRedirects, true)) {
    $redirect = 'index.php';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $loginError = 'Username dan Password wajib diisi';
    } else {
    
        if (!isset($db) || !($db instanceof mysqli)) {
            $loginError = 'Koneksi database tidak tersedia. Cek config.php';
        } else {
            $stmt = $db->prepare("SELECT id, username, password, level FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) {
                $loginError = 'Query prepare gagal: ' . e($db->error);
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $user = $res ? $res->fetch_assoc() : null;
                $stmt->close();

                if (!$user) {
                    $loginError = 'Username atau Password salah';
                } else {
                    $stored = (string) ($user['password'] ?? '');

                    
                    $info = password_get_info($stored);
                    $isHashed = !empty($stored) && ($info['algo'] ?? 0) !== 0;

                    $ok = false;

                    if ($isHashed) {
                        
                        $ok = password_verify($password, $stored);
                    } else {
                        
                        $ok = hash_equals($stored, $password);
                    }

                    if (!$ok) {
                        $loginError = 'Username atau Password salah';
                    } else {
                        // Kalau masih plaintext, upgrade ke hash biar aman
                        if (!$isHashed) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            $up = $db->prepare("UPDATE users SET password = ? WHERE id = ? LIMIT 1");
                            if ($up) {
                                $uid = (int) $user['id'];
                                $up->bind_param("si", $newHash, $uid);
                                $up->execute();
                                $up->close();
                            }
                        }

                        $_SESSION['user'] = [
                            'id' => (int) $user['id'],
                            'username' => (string) $user['username'],
                            'level' => (string) $user['level'],
                        ];

                        if (($_SESSION['user']['level'] ?? '') === 'admin') {
                            $p = basename(parse_url($redirect, PHP_URL_PATH) ?? '');
                            if ($p === 'status.php') {
                                $redirect = 'admin_dashboard.php?tab=kelas';
                            }
                        }


                        header('Location: ' . $redirect);
                        exit;
                    }
                }
            }
        }
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
</div>

<?php
render_footer();
