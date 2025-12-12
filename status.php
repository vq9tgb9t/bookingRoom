<?php
require 'config.php';
require 'layout.php';
$_GET['page'] = 'status';



$errors = [];
$info_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    ($_POST['action'] ?? '') === 'save_booking' && 
    empty($_SESSION['user'])) {
    // butuh login
    header('Location: login.php?page=login&redirect=status.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    ($_POST['action'] ?? '') === 'save_booking'
) {

    $room_id = (int) ($_POST['room_id'] ?? 0);
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');
    $nama_peminjam = trim($_POST['nama_peminjam'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';

    // Validasi sederhana
    if ($room_id <= 0)
        $errors[] = "Ruangan tidak valid.";
    if ($nama_kelas === '')
        $errors[] = "Nama kelas wajib diisi.";
    if ($nama_peminjam === '')
        $errors[] = "Nama peminjam wajib diisi.";
    if ($keperluan === '')
        $errors[] = "Keperluan wajib diisi.";
    if ($tanggal === '')
        $errors[] = "Tanggal wajib diisi.";
    if ($jam_mulai === '' || $jam_selesai === '') {
        $errors[] = "Jam mulai dan jam selesai wajib diisi.";
    }

    if ($tanggal && $jam_mulai && $jam_selesai) {
        $start = DateTime::createFromFormat('Y-m-d H:i', $tanggal . ' ' . $jam_mulai);
        $end = DateTime::createFromFormat('Y-m-d H:i', $tanggal . ' ' . $jam_selesai);
        if (!$start || !$end || $end <= $start) {
            $errors[] = "Jam selesai harus lebih besar dari jam mulai.";
        }
    }

    if (empty($errors)) {
        // Cek bentrok jadwal di ruangan yang sama
        $cek = $db->prepare("
            SELECT COUNT(*) 
            FROM bookings 
            WHERE room_id = ?
              AND tanggal = ?
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
            $stmt = $db->prepare("
                INSERT INTO bookings
                (room_id, nama_kelas, nama_peminjam, keperluan, deskripsi,
                 tanggal, jam_mulai, jam_selesai, status)
                VALUES (?,?,?,?,?,?,?,?,'disetujui')
            ");
            $stmt->bind_param(
                'isssssss',
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
                $qs['info'] = 'booking_ok';
                header('Location: status.php?' . http_build_query($qs));
                exit;
            } else {
                $errors[] = "Gagal menyimpan booking: " . $db->error;
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['info']) && $_GET['info'] === 'booking_ok') {
    $info_message = "Booking ruangan berhasil disimpan.";
}

$gedung = $_GET['gedung'] ?? 'D';
$lantai = isset($_GET['lantai']) ? (int) $_GET['lantai'] : 1;
if ($lantai < 1 || $lantai > 4)
    $lantai = 1;

$tanggal_param = $_GET['tanggal'] ?? date('Y-m-d');
$jam_param = $_GET['jam'] ?? date('H:i');

$mode = $_GET['mode'] ?? 'available';
$view_mode = $_GET['view_mode'] ?? 'now';
$apply_dt = isset($_GET['apply_dt']) ? 1 : 0;


if ($apply_dt) {

    $now = DateTime::createFromFormat('Y-m-d H:i', $tanggal_param . ' ' . $jam_param);
    if (!$now) {
        $now = new DateTime('now');
        $tanggal_param = $now->format('Y-m-d');
        $jam_param = $now->format('H:i');
    }
} else {

    $now = new DateTime('now');
    $tanggal_param = $now->format('Y-m-d');
    $jam_param = $now->format('H:i');
}

$todayDate = $tanggal_param;


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


$bookingsByRoom = [];
if (!empty($roomIds)) {

    $inIds = implode(',', array_fill(0, count($roomIds), '?'));
    $types = str_repeat('i', count($roomIds));

    $sql = "
        SELECT b.*, r.kode_ruang, r.gedung, r.lantai
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        WHERE r.id IN ($inIds)
          AND b.tanggal = ?
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

render_header("Status Ruang Kelas");
?>


<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="page-title">Status Ruang Kelas</div>
<div class="page-subtitle">
    Cek ketersediaan ruangan di Gedung D &amp; S.
</div>

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


<form method="get" class="filter-row">
    <input type="hidden" name="page" value="status">
    <input type="hidden" name="gedung" value="<?= e($gedung) ?>">
    <input type="hidden" name="lantai" value="<?= (int) $lantai ?>">

    <div class="filter-group">
        <div class="filter-item">
            <span>Tanggal Cek Status</span>
            <input type="date" name="tanggal" value="<?= e($tanggal_param) ?>">
        </div>
        <div class="filter-item">
            <span>Jam Cek Status</span>
            <input type="time" name="jam" value="<?= e($jam_param) ?>">
        </div>
        <div class="filter-item filter-item-top">
            <label class="filter-checkbox-label">
                <input type="checkbox" name="apply_dt" value="1" <?= $apply_dt ? 'checked' : '' ?>>
                Gunakan tanggal & jam di atas
            </label>
        </div>
    </div>

    <div class="filter-group">
        <div class="filter-item">
            <span>Tampilkan</span>
            <select name="mode">
                <option value="available" <?= $mode === 'available' ? 'selected' : '' ?>>Hanya ruangan kosong</option>
                <option value="all" <?= $mode === 'all' ? 'selected' : '' ?>>Semua ruangan</option>
                <option value="occupied" <?= $mode === 'occupied' ? 'selected' : '' ?>>Hanya yang terpakai</option>
            </select>
        </div>
        <div class="filter-item">
            <span>Mode waktu</span>
            <select name="view_mode">
                <option value="now" <?= $view_mode === 'now' ? 'selected' : '' ?>>Status sekarang (otomatis)</option>
                <option value="day" <?= $view_mode === 'day' ? 'selected' : '' ?>>Semua booking di hari ini</option>
            </select>
        </div>
    </div>

    <div class="legend">
        * Hitam = Sedang Digunakan
    </div>

    <button type="submit" class="btn-outline">Perbarui</button>
</form>

<!-- TAB GEDUNG -->
<div class="tab-row">
    <a href="?page=status&gedung=D&lantai=<?= (int) $lantai ?>&tanggal=<?= e($tanggal_param) ?>&jam=<?= e($jam_param) ?>&mode=<?= e($mode) ?>&view_mode=<?= e($view_mode) ?><?= $apply_dt ? '&apply_dt=1' : '' ?>"
        class="tab <?= $gedung === 'D' ? 'active' : '' ?>">GEDUNG D</a>
    <a href="?page=status&gedung=S&lantai=<?= (int) $lantai ?>&tanggal=<?= e($tanggal_param) ?>&jam=<?= e($jam_param) ?>&mode=<?= e($mode) ?>&view_mode=<?= e($view_mode) ?><?= $apply_dt ? '&apply_dt=1' : '' ?>"
        class="tab <?= $gedung === 'S' ? 'active' : '' ?>">GEDUNG S</a>
</div>

<!-- TAB LANTAI -->
<div class="floor-tabs">
    <?php for ($i = 1; $i <= 4; $i++): ?>
        <a href="?page=status&gedung=<?= e($gedung) ?>&lantai=<?= (int)$i ?>&tanggal=<?= e($tanggal_param) ?>&jam=<?= e($jam_param) ?>&mode=<?= e($mode) ?>&view_mode=<?= e($view_mode) ?><?= $apply_dt ? '&apply_dt=1' : '' ?>"
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

            $nearestStart = null;
            $nearestEnd = null;

            foreach ($roomBookings as $b) {
                $start = DateTime::createFromFormat('Y-m-d H:i', $b['tanggal'] . ' ' . $b['jam_mulai']);
                $end = DateTime::createFromFormat('Y-m-d H:i', $b['tanggal'] . ' ' . $b['jam_selesai']);
                if (!$start || !$end)
                    continue;

                // booking yang sudah lewat semuanya, skip
                if ($end <= $now) {
                    continue;
                }

                if ($now >= $start && $now < $end) {
                    // sedang dipakai
                    $state = 'busy';
                    $nearestStart = $start;
                    $nearestEnd = $end;
                    break;
                } elseif ($now < $start) {
                    // akan datang
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
                if ($nearestStart && $nearestEnd) {
                    $infoLine = 'Dipakai ' . $nearestStart->format('H:i') . ' - ' . $nearestEnd->format('H:i');
                }
            } elseif ($state === 'upcoming') {
                $badgeClass = 'badge-status-upcoming';
                $badgeText = 'AKAN DIPAKAI';
                $cardExtraClass = 'upcoming';
                if ($nearestStart && $nearestEnd) {
                    $infoLine = 'Akan dipakai ' . $nearestStart->format('H:i') . ' - ' . $nearestEnd->format('H:i');
                }
            } else {
                $badgeClass = 'badge-status-free';
                $badgeText = 'KOSONG';
                $cardExtraClass = '';
                if ($view_mode === 'day' && $nearestStart && $nearestEnd) {
                    $infoLine = 'Ada jadwal ' . $nearestStart->format('H:i') . ' - ' . $nearestEnd->format('H:i');
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
                <button class="btn-primary btn-book" data-room-id="<?= $rid ?>"
                    data-room-name="<?= e($room['kode_ruang']) ?>">Booking Ruangan</button>
                <?php else: ?>
                    <a href="login.php?page=login&redirect=status.php" class="btn-outline btn-login-booking">Login untuk booking</a>
                <?php endif; ?>
            </div>
            <?php
        endforeach;
    endif;
    ?>
</div>

<!-- MODAL BOOKING -->
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

            <div class="modal-form-group">
                <label>Nama Peminjam</label>
                <input type="text" name="nama_peminjam" required placeholder="Nama penanggung jawab">
            </div>

            <div class="modal-form-group">
                <label>Keperluan / Digunakan untuk apa</label>
                <input type="text" name="keperluan" required placeholder="Contoh: Belajar kelompok, rapat, dll">
            </div>

            <div class="modal-form-group">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="modalTanggal" required>
            </div>

            <div class="modal-form-group">
                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai" id="modalJamMulai" required>
            </div>

            <div class="modal-form-group">
                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai" id="modalJamSelesai" required>
            </div>

            <div class="modal-form-group">
                <label>Deskripsi (opsional)</label>
                <textarea name="deskripsi" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
            </div>

            <div class="modal-footer-row">
                <button type="button" class="btn-outline" id="modalCancelBtn">Batal</button>
                <button type="submit" class="btn-primary">Simpan Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
    // buka modal
    document.querySelectorAll('.btn-book').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.dataset.roomId;
            const roomName = this.dataset.roomName;

            const modal = document.getElementById('bookingModal');
            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalTitle').innerText = 'Booking Ruangan ' + roomName;

            // set default tanggal & jam sesuai filter sekarang
            const tgl = '<?= $tanggal_param ?>';
            const jam = '<?= $jam_param ?>';
            document.getElementById('modalTanggal').value = tgl;
            document.getElementById('modalJamMulai').value = jam;
            document.getElementById('modalJamSelesai').value = jam;

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
