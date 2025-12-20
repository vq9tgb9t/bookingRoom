<?php
require 'config.php';
require 'layout.php';
$_GET['page'] = 'status';
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && (($_SESSION['user']['level'] ?? '') === 'admin')) {
    // Admin tetap bisa lihat menu Beranda/Jadwal/Admin, jadi arahkan ke Jadwal (atau Admin Dashboard)
    header('Location: jadwal.php?page=jadwal');
    exit;
}


$errors = [];
$info_message = null;

// ---- HELPERS ----
function timeToMin(string $t): int
{
    // terima format "HH:MM" atau "HH:MM:SS"
    $parts = explode(':', $t);
    $h = (int) ($parts[0] ?? 0);
    $m = (int) ($parts[1] ?? 0);
    return $h * 60 + $m;
}
function overlapMin(int $s1, int $e1, int $s2, int $e2): bool
{
    // overlap kalau [s1,e1) dan [s2,e2) beririsan
    return ($s1 < $e2) && ($e1 > $s2);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'save_booking' &&
    empty($_SESSION['user'])
) {
    if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && (($_SESSION['user']['level'] ?? '') === 'admin')) {
        http_response_code(403);
        die('Admin tidak diizinkan melakukan booking.');
    }
    // butuh login
    header('Location: login.php?page=login&redirect=status.php');
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'save_booking'
) {

    // ====== AMBIL DARI SESSION (WAJIB) ======
    $sessionUser = $_SESSION['user'] ?? null;

    $user_id = 0;
    $session_username = '';

    if (is_array($sessionUser)) {
        $user_id = (int) ($sessionUser['id'] ?? 0);
        $session_username = (string) ($sessionUser['username'] ?? '');
    } elseif (is_string($sessionUser)) {
        // kalau session lu cuma string (jarang sekarang), anggap itu username
        $session_username = $sessionUser;
    }

    // fallback: kalau id belum ada tapi username ada, ambil dari DB
    if (($user_id <= 0) && $session_username !== '') {
        $stmtU = $db->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $stmtU->bind_param("s", $session_username);
        $stmtU->execute();
        $ru = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();

        $user_id = (int) ($ru['id'] ?? 0);
    }

    // ====== AMBIL INPUT FORM (KECUALI NAMA PEMINJAM) ======
    $room_id = (int) ($_POST['room_id'] ?? 0);
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');

    // PAKSA nama_peminjam dari session, bukan dari POST
    $nama_peminjam = $session_username;

    $keperluan = trim($_POST['keperluan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai = trim($_POST['jam_selesai'] ?? '');

    // ====== VALIDASI ======
    if ($user_id <= 0 || $nama_peminjam === '')
        $errors[] = "Session login tidak valid. Login ulang.";

    if ($room_id <= 0)
        $errors[] = "Ruangan tidak valid.";
    if ($nama_kelas === '')
        $errors[] = "Nama kelas wajib diisi.";
    if ($keperluan === '')
        $errors[] = "Keperluan wajib diisi.";
    if ($tanggal === '')
        $errors[] = "Tanggal wajib diisi.";
    if ($jam_mulai === '' || $jam_selesai === '') {
        $errors[] = "Jam mulai dan jam selesai wajib diisi.";
    }

    if ($tanggal && $jam_mulai && $jam_selesai) {
        $start = DateTime::createFromFormat('Y-m-d H:i', $tanggal . ' ' . $jam_mulai);
        if (!$start)
            $start = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $jam_mulai);

        $end = DateTime::createFromFormat('Y-m-d H:i', $tanggal . ' ' . $jam_selesai);
        if (!$end)
            $end = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $jam_selesai);

        if (!$start || !$end || $end <= $start) {
            $errors[] = "Jam selesai harus lebih besar dari jam mulai.";
        } else {
            $startMin = timeToMin($jam_mulai);
            $endMin = timeToMin($jam_selesai);

            $OPEN = 6 * 60 + 45; // 06:45
            $CLOSE = 18 * 60;     // 18:00

            if ($startMin < $OPEN || $endMin > $CLOSE) {
                $errors[] = "Jam booking harus di antara 06:45 - 18:00.";
            }

            if (overlapMin($startMin, $endMin, 12 * 60, 12 * 60 + 30)) {
                $errors[] = "Booking tidak boleh melewati istirahat Dzuhur (12:00 - 12:30).";
            }
            if (overlapMin($startMin, $endMin, 15 * 60, 15 * 60 + 30)) {
                $errors[] = "Booking tidak boleh melewati istirahat Ashar (15:00 - 15:30).";
            }
        }
    }

    if (empty($errors)) {
        // ====== LIMIT pending aktif per user (anti spam) ======
        $limitPending = 3;

        $stP = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status='pending'");
        $stP->bind_param("i", $user_id);
        $stP->execute();
        $stP->bind_result($pendingCount);
        $stP->fetch();
        $stP->close();

        if ($pendingCount >= $limitPending) {
            $errors[] = "Kamu sudah punya {$pendingCount} booking pending. Selesaikan/batalkan dulu sebelum buat request baru.";
        }

        // ====== CEK BENTROK (hitung yang masih aktif aja) ======
        $cek = $db->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = ?
              AND tanggal = ?
              AND status IN ('disetujui','pending')
              AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
        ");
        $cek->bind_param('isss', $room_id, $tanggal, $jam_mulai, $jam_selesai);
        $cek->execute();
        $cek->bind_result($cnt);
        $cek->fetch();
        $cek->close();

        if ($cnt > 0) {
            $errors[] = "Jadwal bentrok dengan booking lain di ruangan ini.";
        } else {

            // ====== INSERT: MASUKIN user_id + approved_at ======
            $stmt = $db->prepare("
    INSERT INTO bookings
    (user_id, room_id, nama_kelas, nama_peminjam, keperluan, deskripsi,
     tanggal, jam_mulai, jam_selesai, status, approved_at)
    VALUES (?,?,?,?,?,?,?,?,?,'pending', NULL)
");

            $stmt->bind_param(
                'iisssssss',
                $user_id,
                $room_id,
                $nama_kelas,
                $nama_peminjam,
                $keperluan,
                $deskripsi,
                $tanggal,
                $jam_mulai,
                $jam_selesai
            );

            if ($stmt->execute()) {
                $qs = $_GET;
                $qs['page'] = 'status';
                $qs['info'] = 'booking_pending'; // <— biar UI bisa bedain pending vs approved
                header('Location: status.php?' . http_build_query($qs));
                exit;
            } else {
                $errors[] = "Gagal menyimpan booking: " . $stmt->error;
            }
            $stmt->close();

        }
    }
}


$info = $_GET['info'] ?? '';

if ($info === 'booking_ok') {
    $info_message = "Booking ruangan berhasil disimpan.";
} elseif ($info === 'booking_pending') {
    $info_message = "Permintaan booking berhasil dikirim dan sedang MENUNGGU persetujuan admin (status: pending).";
} elseif ($info === 'booking_rejected') {
    $info_message = "Booking ditolak oleh admin.";
} elseif ($info === 'booking_approved') {
    $info_message = "Booking disetujui oleh admin.";
}


$gedung = $_GET['gedung'] ?? 'D';
$lantai = isset($_GET['lantai']) ? (int) $_GET['lantai'] : 1;
if ($lantai < 1 || $lantai > 4)
    $lantai = 1;

// ---- INPUT FILTER (single source of truth) ----
$tanggal_param = $_GET['tanggal'] ?? date('Y-m-d');
$jam_mulai_param = $_GET['jam_mulai'] ?? date('H:i');
// default jam_selesai = +45 menit dari jam_mulai (fallback kalau kosong)
if (!empty($_GET['jam_selesai'])) {
    $jam_selesai_param = $_GET['jam_selesai'];
} else {
    $tmp = DateTime::createFromFormat('Y-m-d H:i', $tanggal_param . ' ' . $jam_mulai_param);
    if (!$tmp)
        $tmp = new DateTime('now');
    $jam_selesai_param = $tmp->modify('+45 minutes')->format('H:i');
}

$mode = $_GET['mode'] ?? 'all';
$view_mode = $_GET['view_mode'] ?? 'range'; // now | day | range

// status "sekarang" pakai waktu real server
$now = new DateTime('now');

// kalau mode 'now', pakai tanggal hari ini agar booking yang dibaca sesuai
if ($view_mode === 'now') {
    $tanggal_param = $now->format('Y-m-d');
}

// untuk mode range, kita butuh interval yang valid
$rangeStart = null;
$rangeEnd = null;
$rangeValid = false;
if ($view_mode === 'range') {
    $rs = DateTime::createFromFormat('Y-m-d H:i', $tanggal_param . ' ' . $jam_mulai_param);
    if (!$rs)
        $rs = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal_param . ' ' . $jam_mulai_param);
    $re = DateTime::createFromFormat('Y-m-d H:i', $tanggal_param . ' ' . $jam_selesai_param);
    if (!$re)
        $re = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal_param . ' ' . $jam_selesai_param);

    if ($rs && $re && $re > $rs) {
        $rangeStart = $rs;
        $rangeEnd = $re;
        $rangeValid = true;
    }
}

$todayDate = $tanggal_param;

// ambil rooms
$stmtRooms = $db->prepare("
    SELECT id, kode_ruang, gedung, lantai, nama_ruang, deskripsi, fasilitas, kapasitas
    FROM rooms
    WHERE gedung = ? AND lantai = ?
    ORDER BY kode_ruang
");
$stmtRooms->bind_param('si', $gedung, $lantai);
$stmtRooms->execute();
$resultRooms = $stmtRooms->get_result();

$rooms = [];
$roomIds = [];
while ($row = $resultRooms->fetch_assoc()) {
    $rooms[] = $row;
    $roomIds[] = (int) $row['id'];
}
$stmtRooms->close();

// ambil bookings untuk tanggal yang sedang dilihat
$bookingsByRoom = [];
if (!empty($roomIds)) {
    $inIds = implode(',', array_fill(0, count($roomIds), '?'));

    $types = str_repeat('i', count($roomIds));
    $sql = "
        SELECT b.*
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        WHERE r.id IN ($inIds)
  AND b.tanggal = ?
  AND b.status IN ('disetujui','pending')

        ORDER BY b.jam_mulai
    ";

    $stmtB = $db->prepare($sql);

    $params = [];
    $params[] = &$types;
    foreach ($roomIds as $k => $id) {
        $params[] = &$roomIds[$k];
    }
    $params[] = &$todayDate;
    $types .= 's';

    call_user_func_array([$stmtB, 'bind_param'], $params);
    $stmtB->execute();
    $resultB = $stmtB->get_result();

    while ($b = $resultB->fetch_assoc()) {
        $rid = (int) $b['room_id'];
        if (!isset($bookingsByRoom[$rid]))
            $bookingsByRoom[$rid] = [];
        $bookingsByRoom[$rid][] = $b;
    }
    $stmtB->close();
}

render_header("Status Ruangan");
?>

<?php if ($info_message): ?>
    <div class="alert"><?= e($info_message) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
            • <?= e($e) ?><br>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="get" class="filter-row" id="filterForm">
    <input type="hidden" name="page" value="status">
    <input type="hidden" name="gedung" value="<?= e($gedung) ?>">
    <input type="hidden" name="lantai" value="<?= (int) $lantai ?>">

    <div class="filter-group">
        <div class="filter-item">
            <span>Tanggal</span>
            <input type="date" name="tanggal" value="<?= e($tanggal_param) ?>">
        </div>
        <div class="filter-item">
            <span>Jam Mulai</span>
            <input type="time" name="jam_mulai" value="<?= e($jam_mulai_param) ?>">
        </div>
        <div class="filter-item">
            <span>Jam Selesai</span>
            <input type="time" name="jam_selesai" value="<?= e($jam_selesai_param) ?>">
        </div>
    </div>

    <div class="filter-group">
        <div class="filter-item">
            <span>Mode waktu</span>
            <select name="view_mode" id="viewModeSelect">
                <option value="range" <?= $view_mode === 'range' ? 'selected' : '' ?>>Cek ketersediaan (rentang waktu)
                </option>
                <option value="now" <?= $view_mode === 'now' ? 'selected' : '' ?>>Status sekarang (otomatis)</option>
                <option value="day" <?= $view_mode === 'day' ? 'selected' : '' ?>>Ringkasan booking di tanggal ini</option>
            </select>
        </div>

        <div class="filter-item">
            <span>Tampilkan</span>
            <select name="mode">
                <option value="all" <?= $mode === 'all' ? 'selected' : '' ?>>Semua ruangan</option>
                <option value="available" <?= $mode === 'available' ? 'selected' : '' ?>>Hanya yang kosong</option>
                <option value="occupied" <?= $mode === 'occupied' ? 'selected' : '' ?>>Hanya yang terpakai/bentrok</option>
            </select>
        </div>
    </div>

    <div class="legend">
        * Hitam = bentrok dengan rentang waktu (Mode: rentang waktu). Untuk booking, gunakan Mode: rentang waktu.
    </div>

    <button type="submit" class="btn-outline">Perbarui</button>
</form>

<?php
// supaya parameter filter kebawa saat pindah gedung/lantai
$qsCommon = [
    'page' => 'status',
    'tanggal' => $tanggal_param,
    'jam_mulai' => $jam_mulai_param,
    'jam_selesai' => $jam_selesai_param,
    'mode' => $mode,
    'view_mode' => $view_mode,
];
?>

<!-- TAB GEDUNG -->
<div class="tab-row">
    <a href="?<?= e(http_build_query(array_merge($qsCommon, ['gedung' => 'D', 'lantai' => (int) $lantai]))) ?>"
        class="tab <?= $gedung === 'D' ? 'active' : '' ?>">GEDUNG D</a>
    <a href="?<?= e(http_build_query(array_merge($qsCommon, ['gedung' => 'S', 'lantai' => (int) $lantai]))) ?>"
        class="tab <?= $gedung === 'S' ? 'active' : '' ?>">GEDUNG S</a>
</div>

<!-- TAB LANTAI -->
<div class="floor-tabs">
    <?php for ($i = 1; $i <= 4; $i++): ?>
        <a href="?<?= e(http_build_query(array_merge($qsCommon, ['gedung' => $gedung, 'lantai' => $i]))) ?>"
            class="floor-tab <?= $lantai === $i ? 'active' : '' ?>">
            LANTAI <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

<!-- GRID RUANGAN -->
<div class="room-grid">
    <?php
    if (empty($rooms)):
        ?>
        <div class="room-grid-empty">
            Belum ada data ruangan untuk gedung <?= e($gedung) ?> lantai <?= (int) $lantai ?>.
        </div>
        <?php
    else:
        foreach ($rooms as $room):
            $rid = (int) $room['id'];
            $roomBookings = $bookingsByRoom[$rid] ?? [];

            $state = 'free';
            $badgeClass = 'badge-status-free';
            $badgeText = 'KOSONG';
            $cardExtraClass = '';
            $infoLine = '';

            // =========================================================
            // PENENTUAN STATUS CARD (BERDASARKAN view_mode)
            // =========================================================
            if ($view_mode === 'range') {
                if (!$rangeValid) {
                    // belum ada rentang waktu yang valid
                    $state = 'free';
                    $badgeClass = 'badge-status-upcoming';
                    $badgeText = 'ISI RENTANG';
                    $cardExtraClass = 'upcoming';
                    $infoLine = 'Masukkan jam mulai & jam selesai yang valid.';
                } else {
                    $conflict = false;

                    foreach ($roomBookings as $b) {
                        $start = DateTime::createFromFormat('Y-m-d H:i:s', $b['tanggal'] . ' ' . $b['jam_mulai']);
                        if (!$start)
                            $start = DateTime::createFromFormat('Y-m-d H:i', $b['tanggal'] . ' ' . $b['jam_mulai']);

                        $end = DateTime::createFromFormat('Y-m-d H:i:s', $b['tanggal'] . ' ' . $b['jam_selesai']);
                        if (!$end)
                            $end = DateTime::createFromFormat('Y-m-d H:i', $b['tanggal'] . ' ' . $b['jam_selesai']);

                        if (!$start || !$end)
                            continue;

                        // overlap interval: (start < rangeEnd) && (end > rangeStart)
                        if ($start < $rangeEnd && $end > $rangeStart) {
                            $conflict = true;
                            break;
                        }
                    }

                    if ($conflict) {
                        $state = 'busy';
                        $badgeClass = 'badge-status-busy';
                        $badgeText = 'BENTROK';
                        $cardExtraClass = 'busy';
                        $infoLine = 'Tidak tersedia pada rentang waktu ini.';
                    } else {
                        $state = 'free';
                        $badgeClass = 'badge-status-free';
                        $badgeText = 'KOSONG';
                        $cardExtraClass = '';
                        $infoLine = '';
                    }
                }
            } elseif ($view_mode === 'day') {
                $cntDay = count($roomBookings);
                if ($cntDay > 0) {
                    $state = 'upcoming';
                    $badgeClass = 'badge-status-upcoming';
                    $badgeText = 'ADA JADWAL';
                    $cardExtraClass = 'upcoming';
                    $infoLine = 'Ada ' . $cntDay . ' booking di tanggal ini.';
                } else {
                    $state = 'free';
                    $badgeClass = 'badge-status-free';
                    $badgeText = 'KOSONG';
                    $cardExtraClass = '';
                    $infoLine = '';
                }
            } else { // view_mode === 'now'
                $nearestStart = null;
                $nearestEnd = null;

                foreach ($roomBookings as $b) {
                    $start = DateTime::createFromFormat('Y-m-d H:i:s', $b['tanggal'] . ' ' . $b['jam_mulai']);
                    $end = DateTime::createFromFormat('Y-m-d H:i:s', $b['tanggal'] . ' ' . $b['jam_selesai']);
                    if (!$start || !$end)
                        continue;

                    // booking yang sudah lewat semuanya, skip
                    if ($end <= $now)
                        continue;

                    if ($now >= $start && $now < $end) {
                        $state = 'busy';
                        $nearestStart = $start;
                        $nearestEnd = $end;
                        break;
                    } elseif ($now < $start) {
                        if ($nearestStart === null || $start < $nearestStart) {
                            $nearestStart = $start;
                            $nearestEnd = $end;
                        }
                    }
                }

                if ($state !== 'busy' && $nearestStart !== null && $nearestStart > $now) {
                    $state = 'upcoming';
                }

                if ($state === 'busy') {
                    $badgeClass = 'badge-status-busy';
                    $badgeText = 'SEDANG DIPAKAI';
                    $cardExtraClass = 'busy';
                    $infoLine = 'Sedang dipakai.';
                } elseif ($state === 'upcoming') {
                    $badgeClass = 'badge-status-upcoming';
                    $badgeText = 'AKAN DIPAKAI';
                    $cardExtraClass = 'upcoming';
                    $infoLine = 'Akan dipakai.';
                } else {
                    $badgeClass = 'badge-status-free';
                    $badgeText = 'KOSONG';
                    $cardExtraClass = '';
                    $infoLine = '';
                }
            }

            // filter tampilan
            $skip = false;
            if ($mode === 'available' && $state !== 'free')
                $skip = true;
            if ($mode === 'occupied' && $state === 'free')
                $skip = true;

            if ($skip)
                continue;
            ?>
            <div class="room-card <?= $cardExtraClass ?>">
                <div class="room-title-row">
                    <div class="room-name"><?= e($room['kode_ruang']) ?></div>
                    <div class="<?= $badgeClass ?>"><?= $badgeText ?></div>
                </div>
                <div class="room-desc">
                    <?= e($room['deskripsi'] ?: 'Ruang kelas besar, akses mudah, dekat lobby utama.') ?>
                </div>
                <div class="room-tags">
                    <?php
                    $fList = array_filter(array_map('trim', explode(',', (string) $room['fasilitas'])));
                    if (empty($fList))
                        $fList = ['AC', 'Whiteboard', 'WiFi'];
                    foreach ($fList as $f): ?>
                        <span class="tag"><?= e($f) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($infoLine): ?>
                    <div class="info-text"><?= e($infoLine) ?></div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user'])): ?>
                    <?php if ($view_mode === 'range' && $rangeValid && $state === 'free'): ?>
                        <button class="btn-primary btn-book" data-room-id="<?= $rid ?>"
                            data-room-name="<?= e($room['kode_ruang']) ?>">Booking Ruangan</button>
                    <?php else: ?>
                        <button class="btn-primary btn-book" type="button" disabled
                            title="Untuk booking, pilih Mode: rentang waktu dan pastikan ruangan KOSONG.">
                            Booking Ruangan
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn-outline" href="login.php?page=login&redirect=status.php">Login untuk booking</a>
                <?php endif; ?>
            </div>
            <?php
        endforeach;
    endif;
    ?>
</div>

<!-- MODAL/CARD BOOKING -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal-box">
        <div class="modal-header-row">
            <div class="modal-title" id="modalTitle">Booking Ruangan</div>
            <button type="button" class="modal-close-btn" id="modalCloseBtn">&times;</button>
        </div>

        <form method="post" id="bookingForm">
            <input type="hidden" name="action" value="save_booking">
            <input type="hidden" name="room_id" id="modalRoomId">

            <div class="modal-form-group">
                <label>Nama Kelas</label>
                <input type="text" name="nama_kelas" required placeholder="Contoh: Kelas A / RPL 2">
            </div>

            <div class="slot-preview">
                <input type="text" name="nama_peminjam"
                    value="<?= e(is_array($_SESSION['user'] ?? null) ? ($_SESSION['user']['username'] ?? '') : '') ?>"
                    readonly>
            </div>

            <div class="modal-form-group">
                <label>Keperluan / Digunakan untuk apa</label>
                <input type="text" name="keperluan" required placeholder="Contoh: Belajar kelompok, rapat, dll">
            </div>

            <input type="hidden" name="tanggal" id="modalTanggal">
            <input type="hidden" name="jam_mulai" id="modalJamMulai">
            <input type="hidden" name="jam_selesai" id="modalJamSelesai">

            <div class="slot-preview" id="modalSlotPreview">
                <!-- diisi lewat JS: tanggal & jam dari form cek -->
            </div>

            <div class="modal-form-group">
                <label>Deskripsi (opsional)</label>
                <textarea name="deskripsi" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-outline" id="modalCancelBtn">Batal</button>
                <button type="submit" class="btn-primary">Simpan Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
    // buka modal (hanya tombol yang tidak disabled)
    document.querySelectorAll('.btn-book:not([disabled])').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.dataset.roomId;
            const roomName = this.dataset.roomName;

            // ambil slot waktu dari form filter (single source of truth)
            const tanggalEl = document.querySelector('input[name="tanggal"]');
            const mulaiEl = document.querySelector('input[name="jam_mulai"]');
            const selesaiEl = document.querySelector('input[name="jam_selesai"]');

            const tgl = tanggalEl ? tanggalEl.value : '';
            const mulai = mulaiEl ? mulaiEl.value : '';
            const selesai = selesaiEl ? selesaiEl.value : '';

            if (!tgl || !mulai || !selesai) {
                alert('Isi Tanggal, Jam Mulai, dan Jam Selesai dulu.');
                return;
            }

            const modal = document.getElementById('bookingModal');
            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalTitle').innerText = 'Booking Ruangan ' + roomName;

            // set hidden input modal
            document.getElementById('modalTanggal').value = tgl;
            document.getElementById('modalJamMulai').value = mulai;
            document.getElementById('modalJamSelesai').value = selesai;

            // preview (read-only)
            const preview = document.getElementById('modalSlotPreview');
            preview.innerText = `Tanggal: ${tgl} | Waktu: ${mulai} - ${selesai}`;

            modal.classList.add('open');
        });
    });

    function closeModal() {
        document.getElementById('bookingModal').classList.remove('open');
    }

    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);

    document.getElementById('bookingModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
</script>

<?php
render_footer();
