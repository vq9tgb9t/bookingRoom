<?php
// jadwal.php
require 'config.php';
require 'layout.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$_GET['page'] = 'jadwal';

if (!function_exists('e')) {
    function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}

function current_user(mysqli $db): array
{
    $u = $_SESSION['user'] ?? null;

    $id = 0;
    $username = '';
    $level = '';
    $kelas_id = null;

    if (is_array($u)) {
        $id = (int) ($u['id'] ?? 0);
        $username = (string) ($u['username'] ?? $u['nama'] ?? $u['name'] ?? '');
        $level = (string) ($u['level'] ?? '');
        $kelas_id = isset($u['kelas_id']) ? (int) $u['kelas_id'] : null;
    } elseif (is_string($u)) {
        $username = $u;
    }

    // Lengkapi dari DB jika ada username tapi id/level/kelas_id kosong
    if ($username !== '' && ($id === 0 || $level === '' || $kelas_id === null)) {
        $stmt = $db->prepare("SELECT id, username, level, kelas_id FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $id = (int) $row['id'];
            $username = (string) $row['username'];
            $level = (string) $row['level'];
            $kelas_id = $row['kelas_id'] !== null ? (int) $row['kelas_id'] : null;
        }
    }

    return [
        'id' => $id,
        'username' => $username,
        'level' => $level,
        'kelas_id' => $kelas_id
    ];
}

$me = current_user($db);
$kelasNameUser = '';
if (!empty($me['kelas_id'])) {
    $stmtK = $db->prepare("SELECT nama FROM kelas WHERE id=? LIMIT 1");
    $kid = (int) $me['kelas_id'];
    $stmtK->bind_param("i", $kid);
    $stmtK->execute();
    $kelasNameUser = (string) ($stmtK->get_result()->fetch_assoc()['nama'] ?? '');
    $stmtK->close();
}

$isLogin = !empty($me['username']);
$isAdmin = ($me['level'] === 'admin');
$isMahasiswa = ($me['level'] === 'mahasiswa');

$now_ts = time();
$CANCEL_DELAY_SEC = 3600;
$CANCEL_DELAY_MIN = (int) ceil($CANCEL_DELAY_SEC / 60);

$view = $_GET['view'] ?? ($isAdmin ? 'booking' : 'kelas');
$mode_view = $_GET['mode_view'] ?? 'active';

$errors = [];
$info_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_booking') {
    if (!$isLogin) {
        $errors[] = "Harus login untuk membatalkan booking.";
    } else {
        $booking_id = (int) ($_POST['booking_id'] ?? 0);

        $stmt = $db->prepare("
            SELECT id, status, approved_at, created_at, approved_by, user_id, nama_peminjam, peminjam, tanggal, jam_mulai
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$bk) {
            $errors[] = "Data booking tidak ditemukan.";

        } elseif (!in_array(($bk['status'] ?? ''), ['pending', 'disetujui'], true)) {
            $errors[] = "Booking ini tidak bisa dibatalkan (status: " . ($bk['status'] ?? '-') . ").";

        } elseif (($bk['approved_by'] ?? '') === 'system_jadwal' && !$isAdmin) {
            $errors[] = "Jadwal kuliah (system) hanya bisa dibatalkan admin.";

        } else {
            //ownership
            $ownerOk = false;

            if ($isAdmin) {
                $ownerOk = true;
            } elseif ($isMahasiswa) {
                $uid = (int) ($bk['user_id'] ?? 0);

                if ($uid && (int) ($me['id'] ?? 0) && $uid === (int) $me['id']) {
                    $ownerOk = true;
                } else {
                    $u1 = strtolower((string) ($bk['nama_peminjam'] ?? ''));
                    $u2 = strtolower((string) ($bk['peminjam'] ?? ''));
                    $meu = strtolower((string) ($me['username'] ?? ''));
                    if ($meu !== '' && ($u1 === $meu || $u2 === $meu)) {
                        $ownerOk = true;
                    }
                }
            }

            if (!$ownerOk) {
                $errors[] = "Akses ditolak. Kamu hanya bisa membatalkan booking milikmu.";
            } else {
                //pending: boleh cancel langsung
                if (($bk['status'] ?? '') === 'pending') {
                    $by = $isAdmin ? 'admin' : 'mahasiswa';
                    $u = (string) ($me['username'] ?? '');
                    $ket = "Pending dibatalkan oleh {$by} ({$u}) " . date('Y-m-d H:i:s');

                    $st = $db->prepare("
                        UPDATE bookings
                        SET status='dibatalkan', keterangan=?
                        WHERE id=? AND status='pending'
                    ");
                    $st->bind_param("si", $ket, $booking_id);
                    $st->execute();
                    $aff = $st->affected_rows;
                    $st->close();

                    if ($aff <= 0) {
                        $errors[] = "Gagal membatalkan. Status booking sudah berubah (mungkin sudah diproses admin).";
                    } else {
                        $qs = http_build_query([
                            'view' => $view,
                            'mode_view' => $mode_view,
                            'info' => 'cancel_ok'
                        ]);
                        header("Location: jadwal.php?{$qs}");
                        exit;
                    }

                } else {
                    //disetujui: rule 1 jam + belum mulai       
                    $approvedBase = $bk['approved_at'] ?: $bk['created_at'];
                    $approved_ts = $approvedBase ? strtotime($approvedBase) : 0;

                    if (!$approved_ts) {
                        $errors[] = "Waktu persetujuan tidak valid. Pastikan kolom approved_at terisi.";

                    } elseif ($now_ts < ($approved_ts + $CANCEL_DELAY_SEC)) {
                        $remain = ($approved_ts + $CANCEL_DELAY_SEC) - $now_ts;
                        $min = (int) ceil($remain / 60);
                        $errors[] = "Belum bisa dibatalkan. Tunggu sekitar {$min} menit lagi (minimal {$CANCEL_DELAY_MIN} menit setelah disetujui).";


                    } else {
                        $start_ts = strtotime(($bk['tanggal'] ?? '') . ' ' . ($bk['jam_mulai'] ?? ''));
                        if ($start_ts && $start_ts <= $now_ts) {
                            $errors[] = "Booking sudah dimulai/berjalan. Tidak bisa dibatalkan.";
                        } else {
                            $by = $isAdmin ? 'admin' : 'mahasiswa';
                            $u = (string) ($me['username'] ?? '');
                            $ket = "Dibatalkan oleh {$by} ({$u}) " . date('Y-m-d H:i:s');

                            $st = $db->prepare("
                                UPDATE bookings
                                SET status='dibatalkan', keterangan=?
                                WHERE id=? AND status='disetujui'
                            ");
                            $st->bind_param("si", $ket, $booking_id);
                            $st->execute();
                            $aff = $st->affected_rows;
                            $st->close();

                            if ($aff <= 0) {
                                $errors[] = "Gagal membatalkan. Status booking sudah berubah.";
                            } else {
                                $qs = http_build_query([
                                    'view' => $view,
                                    'mode_view' => $mode_view,
                                    'info' => 'cancel_ok'
                                ]);
                                header("Location: jadwal.php?{$qs}");
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }
}

//DATA VIEW: JADWAL KELAS
$kelasRows = [];
$kelasName = null;

if ($view === 'kelas') {
    if (!$isLogin) {
    } elseif (!$isMahasiswa) {
    } else {
        if (empty($me['kelas_id'])) {

        } else {
            $sqlKelas = "
                SELECT jm.*, r.kode_ruang, r.gedung, r.lantai, k.nama AS kelas
                FROM jadwal_mingguan jm
                JOIN rooms r ON r.id = jm.room_id
                JOIN kelas k ON k.id = jm.kelas_id
                WHERE jm.kelas_id = ?
                ORDER BY jm.day_of_week ASC, jm.jam_mulai ASC
            ";
            $stmt = $db->prepare($sqlKelas);
            $stmt->bind_param("i", $me['kelas_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $kelasName = $row['kelas'] ?? $kelasName;
                $kelasRows[] = $row;
            }
            $stmt->close();
        }
    }
}

//data view boking
$bookingRows = [];

if ($view === 'booking') {
    if (!$isLogin) {

    } else {
        if ($isAdmin) {
            $sql = "
                SELECT b.*, r.kode_ruang, r.gedung, r.lantai
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.status IN ('pending','disetujui','ditolak','dibatalkan')
                ORDER BY b.tanggal ASC, b.jam_mulai ASC
            ";
            $res = $db->query($sql);
            while ($b = $res->fetch_assoc()) {
                $start_ts = strtotime($b['tanggal'] . ' ' . $b['jam_mulai']);
                $end_ts = strtotime($b['tanggal'] . ' ' . $b['jam_selesai']);

                $include = true;
                if ($mode_view === 'active' && $end_ts < $now_ts)
                    $include = false;
                if ($mode_view === 'history' && $end_ts >= $now_ts)
                    $include = false;

                if ($include) {
                    $b['_start_ts'] = $start_ts;
                    $b['_end_ts'] = $end_ts;
                    $bookingRows[] = $b;
                }
            }
        } else {
            // mahasiswa: filter booking
            $sql = "
                SELECT b.*, r.kode_ruang, r.gedung, r.lantai
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.status IN ('pending','disetujui','ditolak','dibatalkan')
                AND (
                    (b.user_id IS NOT NULL AND b.user_id = ?)
                    OR b.nama_peminjam = ?
                    OR b.peminjam = ?
                    OR (b.approved_by = 'system_jadwal' AND b.nama_kelas = ?)
                )

                ORDER BY b.tanggal ASC, b.jam_mulai ASC
            ";
            $stmt = $db->prepare($sql);
            $uid = (int) $me['id'];
            $un = (string) $me['username'];
            $kn = (string) $kelasNameUser;
            $stmt->bind_param("isss", $uid, $un, $un, $kn);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($b = $res->fetch_assoc()) {
                $start_ts = strtotime($b['tanggal'] . ' ' . $b['jam_mulai']);
                $end_ts = strtotime($b['tanggal'] . ' ' . $b['jam_selesai']);

                $include = true;
                if ($mode_view === 'active' && $end_ts < $now_ts)
                    $include = false;
                if ($mode_view === 'history' && $end_ts >= $now_ts)
                    $include = false;

                if ($include) {
                    $b['_start_ts'] = $start_ts;
                    $b['_end_ts'] = $end_ts;
                    $bookingRows[] = $b;
                }
            }
            $stmt->close();
        }
    }
}

$dayMap = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu'
];

render_header("Jadwal");
?>

<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="page-title">Jadwal</div>
<div class="page-subtitle">
    <strong>Jadwal Kelas</strong> (template mingguan) & <strong>Booking Ruangan</strong> (disetujui).
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?= e(implode(" | ", $errors)) ?>
    </div>
<?php endif; ?>

<?php if (($_GET['info'] ?? '') === 'cancel_ok'): ?>
    <div class="alert">Booking berhasil dibatalkan.</div>
<?php endif; ?>


<!-- FILTER / MODE -->
<div class="filter-row jadwal-filter-row">
    <div class="filter-item">
        <span>Tampilan</span>

        <form method="get" class="jadwal-filter-form">
            <input type="hidden" name="page" value="jadwal">

            <select name="view">
                <option value="kelas" <?= $view === 'kelas' ? 'selected' : '' ?>>Jadwal Kelas Saya</option>
                <option value="booking" <?= $view === 'booking' ? 'selected' : '' ?>>
                    <?= $isAdmin ? 'Booking (Semua)' : 'Booking (Saya)' ?>
                </option>
            </select>

            <?php if ($view === 'booking'): ?>
                <select name="mode_view">
                    <option value="active" <?= $mode_view === 'active' ? 'selected' : '' ?>>Aktif & akan datang</option>
                    <option value="all" <?= $mode_view === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="history" <?= $mode_view === 'history' ? 'selected' : '' ?>>Riwayat</option>
                </select>
            <?php endif; ?>

            <button type="submit" class="btn-outline">Terapkan</button>
        </form>
    </div>
</div>

<?php if ($view === 'kelas'): ?>

    <div class="table-wrapper">
        <div class="table-header">
            Jadwal Kelas <?= $isMahasiswa ? e($kelasName ?: '-') : '(khusus mahasiswa)' ?>
        </div>

        <?php if (!$isLogin): ?>
            <div class="empty-message">Silakan login untuk melihat jadwal kelas.</div>

        <?php elseif (!$isMahasiswa): ?>
            <div class="empty-message">Untuk versi simple tugas, “Jadwal Kelas” ditampilkan untuk mahasiswa berdasarkan
                kelas_id.</div>

        <?php elseif (empty($me['kelas_id'])): ?>
            <div class="empty-message">
                Akun mahasiswa belum punya <strong>kelas_id</strong>. Assign dulu di tabel <code>users</code>.
            </div>

        <?php elseif (empty($kelasRows)): ?>
            <div class="empty-message">
                Jadwal mingguan kelas kamu belum diisi di <code>jadwal_mingguan</code>.
            </div>

        <?php else: ?>
            <table class="jadwal-table">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Matkul</th>
                        <th>Ruang</th>
                        <th>Dosen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kelasRows as $it): ?>
                        <tr>
                            <td><?= e($dayMap[(int) $it['day_of_week']] ?? $it['day_of_week']) ?></td>
                            <td><?= substr($it['jam_mulai'], 0, 5) ?> - <?= substr($it['jam_selesai'], 0, 5) ?></td>
                            <td><?= e($it['matkul']) ?></td>
                            <td><?= e($it['kode_ruang']) ?> (Gedung <?= e($it['gedung']) ?> Lt.<?= (int) $it['lantai'] ?>)</td>
                            <td><?= e($it['dosen'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php else:?>

    <div class="table-wrapper">
        <div class="table-header">
            <?= $isAdmin ? 'Daftar Booking Disetujui' : 'Daftar Booking Saya (Pending/Disetujui/Ditolak/Dibatalkan)' ?>
        </div>

        <?php if (!$isLogin): ?>
            <div class="empty-message">Silakan login untuk melihat booking.</div>

        <?php elseif (empty($bookingRows)): ?>
            <div class="empty-message">
                Belum ada booking yang <?= $mode_view === 'history' ? 'masuk riwayat.' : 'aktif atau akan datang.' ?>
            </div>

        <?php else: ?>
            <table class="jadwal-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Ruang</th>
                        <th>Peminjam</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($bookingRows as $row): ?>
                        <?php
                        $approvedBase = $row['approved_at'] ?: ($row['created_at'] ?? null);
                        $approved_ts = $approvedBase ? strtotime($approvedBase) : 0;

                        $start_ts = $row['_start_ts'] ?? strtotime($row['tanggal'] . ' ' . $row['jam_mulai']);
                        $isSchedule = (($row['approved_by'] ?? '') === 'system_jadwal');

                        // di list mahasiswa, ini harusnya selalu miliknya, tapi kita tetap aman
                        $ownerOk = $isSchedule
                            ? $isAdmin
                            : ($isAdmin
                                || (
                                    ((int) ($row['user_id'] ?? 0) && (int) ($row['user_id'] ?? 0) === (int) ($me['id'] ?? 0))
                                    || strtolower((string) ($row['nama_peminjam'] ?: $row['peminjam'] ?: '')) === strtolower((string) ($me['username'] ?? ''))
                                )
                            );


                        $statusRow = $row['status'] ?? '';

                        if ($statusRow === 'pending') {
                            $canCancel = $ownerOk; // pending: langsung boleh
                        } elseif ($statusRow === 'disetujui') {
                            $canCancel = $ownerOk
                                && $approved_ts
                                && ($now_ts >= ($approved_ts + $CANCEL_DELAY_SEC))
                                && ($start_ts > $now_ts);
                        } else {
                            $canCancel = false;
                        }


                        $remainMin = 0;
                        if (($row['status'] ?? '') === 'disetujui' && $approved_ts) {
                            $remainSec = ($approved_ts + $CANCEL_DELAY_SEC) - $now_ts;
                            $remainMin = (int) ceil($remainSec / 60);
                        }

                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($row['tanggal']) ?></td>
                            <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                            <td>
                                <?= e($row['kode_ruang']) ?>
                                (Gedung <?= e($row['gedung']) ?> Lt.<?= (int) $row['lantai'] ?>)
                            </td>
                            <td><?= e($row['nama_peminjam'] ?: $row['peminjam'] ?: '-') ?></td>
                            <td>
                                <?php
                                $ketParts = [];
                                if (!empty($row['nama_kelas']))
                                    $ketParts[] = 'Kelas: ' . $row['nama_kelas'];
                                if (!empty($row['keperluan']))
                                    $ketParts[] = 'Keperluan: ' . $row['keperluan'];
                                if (!empty($row['deskripsi']))
                                    $ketParts[] = $row['deskripsi'];
                                echo e(implode('; ', $ketParts) ?: '-');
                                ?>
                            </td>

                            <td class="jadwal-status-cell">
                                <div class="status-action-container">
                                    <?php
                                    $st = (string) ($row['status'] ?? '');
                                    $badgeClass = 'status-badge-inline';

                                    if ($st === 'pending')
                                        $badgeClass .= ' badge-pending';
                                    elseif ($st === 'disetujui')
                                        $badgeClass .= ' badge-approved';
                                    elseif ($st === 'ditolak')
                                        $badgeClass .= ' badge-rejected';
                                    elseif ($st === 'dibatalkan')
                                        $badgeClass .= ' badge-canceled';
                                    ?>
                                    <span class="<?= e($badgeClass) ?>"><?= e(strtoupper($st)) ?></span>


                                    <?php if ($ownerOk && in_array(($row['status'] ?? ''), ['pending', 'disetujui'], true)): ?>

                                        <div class="cancel-wrapper">
                                            <form method="post" class="cancel-form-inline"
                                                onsubmit="return confirm('Batalkan booking ini?');">
                                                <input type="hidden" name="action" value="cancel_booking">
                                                <input type="hidden" name="booking_id" value="<?= (int) $row['id'] ?>">

                                                <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                    <button type="submit" class="btn-cancel-small">BATALKAN</button>

                                                <?php elseif ($canCancel): ?>
                                                    <button type="submit" class="btn-cancel-small">BATALKAN</button>

                                                <?php else: ?>
                                                    <div class="disabled-group">
                                                        <button type="button" class="btn-cancel-small disabled" disabled
                                                            title="Tunggu minimal 1 jam setelah disetujui, dan sebelum jam mulai">
                                                            BATALKAN
                                                        </button>
                                                        <span class="hint-inline">(<?= max(0, $remainMin) ?>m)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </form>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php render_footer(); ?>