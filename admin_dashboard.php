<?php
require 'config.php';
require 'layout.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$_GET['page'] = 'admin_dashboard';

if (!function_exists('e')) {
    function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
}

date_default_timezone_set('Asia/Jakarta');

function current_user(mysqli $db): array {
    $u = $_SESSION['user'] ?? null;

    $id = 0; $username=''; $level=''; $kelas_id=null;

    if (is_array($u)) {
        $id = (int)($u['id'] ?? 0);
        $username = (string)($u['username'] ?? '');
        $level = (string)($u['level'] ?? '');
        $kelas_id = isset($u['kelas_id']) ? (int)$u['kelas_id'] : null;
    } elseif (is_string($u)) {
        $username = $u;
    }

    if ($username !== '' && ($id === 0 || $level === '')) {
        $stmt = $db->prepare("SELECT id, username, level, kelas_id FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $id = (int)$row['id'];
            $username = (string)$row['username'];
            $level = (string)$row['level'];
            $kelas_id = $row['kelas_id'] !== null ? (int)$row['kelas_id'] : null;
        }
    }

    return ['id'=>$id,'username'=>$username,'level'=>$level,'kelas_id'=>$kelas_id];
}

$me = current_user($db);
if (empty($me['username'])) {
    header('Location: login.php?page=login&redirect=admin_dashboard.php');
    exit;
}
if (($me['level'] ?? '') !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk admin.");
}


$allowedTabs = ['kelas','jadwal', 'booking'];
$tab = $_GET['tab'] ?? 'kelas';
if (!in_array($tab, $allowedTabs, true)) $tab = 'kelas';

$errors = [];
$info = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== Tambah kelas =====
    if ($action === 'add_kelas') {
        $nama  = trim($_POST['nama'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');

        if ($nama === '' || $prodi === '') {
            $errors[] = "Nama kelas dan prodi wajib diisi.";
        } else {
            $stmt = $db->prepare("INSERT INTO kelas (nama, prodi, is_active) VALUES (?,?,1)");
            $stmt->bind_param("ss", $nama, $prodi);
            $stmt->execute();
            $stmt->close();

            $info = "Kelas berhasil ditambahkan.";
            $tab = 'kelas';
        }
    }

    //Tambah jadwal mingguan
    if ($action === 'add_jadwal') {
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        $room_id  = (int)($_POST['room_id'] ?? 0);
        $matkul   = trim($_POST['matkul'] ?? '');
        $dosen    = trim($_POST['dosen'] ?? '');
        $day      = (int)($_POST['day_of_week'] ?? 0);
        $mulai    = trim($_POST['jam_mulai'] ?? '');
        $selesai  = trim($_POST['jam_selesai'] ?? '');

        if ($kelas_id<=0 || $room_id<=0 || $day<1 || $day>7) $errors[] = "Input kelas/ruang/hari tidak valid.";
        if ($matkul==='') $errors[] = "Matkul wajib diisi.";
        if ($mulai==='' || $selesai==='') $errors[] = "Jam mulai & selesai wajib diisi.";
        if ($mulai !== '' && $selesai !== '' && $selesai <= $mulai) $errors[] = "Jam selesai harus lebih besar dari jam mulai.";


        if (empty($errors)) {
            $st = $db->prepare("SELECT is_active FROM kelas WHERE id=? LIMIT 1");
            $st->bind_param("i", $kelas_id);
            $st->execute();
            $k = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$k) $errors[] = "Kelas tidak ditemukan.";
            elseif ((int)$k['is_active'] !== 1) $errors[] = "Kelas nonaktif. Aktifkan dulu untuk input jadwal.";
        }

        if (empty($errors)) {
            $st = $db->prepare("SELECT is_active FROM rooms WHERE id=? LIMIT 1");
            $st->bind_param("i", $room_id);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$r) $errors[] = "Ruangan tidak ditemukan.";
            elseif ((int)$r['is_active'] !== 1) $errors[] = "Ruangan nonaktif. Aktifkan dulu.";
        }


        if (empty($errors)) {
            $cek = $db->prepare("
                SELECT COUNT(*) AS c
                FROM jadwal_mingguan
                WHERE kelas_id = ?
                  AND day_of_week = ?
                  AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
            ");
            $cek->bind_param("iiss", $kelas_id, $day, $mulai, $selesai);
            $cek->execute();
            $cnt = (int)($cek->get_result()->fetch_assoc()['c'] ?? 0);
            $cek->close();
            if ($cnt > 0) $errors[] = "Bentrok: kelas ini sudah punya jadwal di jam tersebut.";
        }
        if (empty($errors)) {
            $cek = $db->prepare("
                SELECT COUNT(*) AS c
                FROM jadwal_mingguan
                WHERE room_id = ?
                  AND day_of_week = ?
                  AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
            ");
            $cek->bind_param("iiss", $room_id, $day, $mulai, $selesai);
            $cek->execute();
            $cnt = (int)($cek->get_result()->fetch_assoc()['c'] ?? 0);
            $cek->close();
            if ($cnt > 0) $errors[] = "Bentrok: ruangan ini sudah dipakai di jam tersebut.";
        }

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO jadwal_mingguan
                (kelas_id, room_id, matkul, dosen, day_of_week, jam_mulai, jam_selesai)
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("iississ", $kelas_id, $room_id, $matkul, $dosen, $day, $mulai, $selesai);
            $stmt->execute();
            $stmt->close();

            $info = "Jadwal berhasil ditambahkan.";
            $tab = 'jadwal';
        }
    }

    if ($action === 'delete_jadwal') {
        $jid = (int)($_POST['jadwal_id'] ?? 0);
        if ($jid <= 0) {
            $errors[] = "ID jadwal tidak valid.";
        } else {
            $stmt = $db->prepare("DELETE FROM jadwal_mingguan WHERE id=?");
            $stmt->bind_param("i", $jid);
            $stmt->execute();
            $stmt->close();

            $info = "Jadwal dihapus.";
            $tab = 'jadwal';
        }
    }

    
    if ($action === 'approve_booking' || $action === 'reject_booking') {
        $tab = 'booking';
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($booking_id <= 0) {
            $errors[] = "ID booking tidak valid.";
        } else {
            $st = $db->prepare("
                SELECT b.*, r.is_active AS room_active
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                WHERE b.id = ?
                LIMIT 1
            ");
            $st->bind_param("i", $booking_id);
            $st->execute();
            $bk = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$bk) {
                $errors[] = "Booking tidak ditemukan.";
            } elseif (($bk['status'] ?? '') !== 'pending') {
                $errors[] = "Booking ini sudah diproses (status: {$bk['status']}).";
            } elseif ((int)($bk['room_active'] ?? 1) !== 1) {
                $errors[] = "Ruangan nonaktif. Tidak bisa diproses.";
            } else {
                $tanggal = (string)$bk['tanggal'];
                $mulai   = (string)$bk['jam_mulai'];
                $selesai = (string)$bk['jam_selesai'];

                if ($tanggal === '' || $mulai === '' || $selesai === '' || $selesai <= $mulai) {
                    $errors[] = "Data waktu booking tidak valid (jam selesai harus > jam mulai).";
                }

              
                if (empty($errors) && $action === 'approve_booking') {
                    $room_id = (int)$bk['room_id'];

                    
                    $cek1 = $db->prepare("
                        SELECT COUNT(*) AS c
                        FROM bookings
                        WHERE status = 'disetujui'
                          AND room_id = ?
                          AND tanggal = ?
                          AND id <> ?
                          AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
                    ");
                    $cek1->bind_param("isiss", $room_id, $tanggal, $booking_id, $mulai, $selesai);
                    $cek1->execute();
                    $c1 = (int)($cek1->get_result()->fetch_assoc()['c'] ?? 0);
                    $cek1->close();

                    if ($c1 > 0) {
                        $errors[] = "Bentrok: sudah ada booking disetujui di ruangan & jam tersebut.";
                    }

                  
                    if (empty($errors)) {
                        $day_of_week = (int)date('N', strtotime($tanggal)); // 1..7

                        $cek2 = $db->prepare("
                            SELECT COUNT(*) AS c
                            FROM jadwal_mingguan
                            WHERE room_id = ?
                              AND day_of_week = ?
                              AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
                        ");
                        $cek2->bind_param("iiss", $room_id, $day_of_week, $mulai, $selesai);
                        $cek2->execute();
                        $c2 = (int)($cek2->get_result()->fetch_assoc()['c'] ?? 0);
                        $cek2->close();

                        if ($c2 > 0) {
                            $errors[] = "Bentrok: ruangan dipakai jadwal mingguan di hari & jam tersebut.";
                        }
                    }
                }

                if (empty($errors)) {
                    $adminName = (string)($me['username'] ?? 'admin');

                    if ($action === 'approve_booking') {
                        $ket = trim(($bk['keterangan'] ?? '') . " | Disetujui admin {$adminName} " . date('Y-m-d H:i:s'));

                        $up = $db->prepare("
                            UPDATE bookings
                            SET status='disetujui',
                                approved_by=?,
                                approved_at=NOW(),
                                keterangan=?
                            WHERE id=? AND status='pending'
                        ");
                        $up->bind_param("ssi", $adminName, $ket, $booking_id);
                        $up->execute();
                        $affected = $up->affected_rows;
                        $up->close();

                        if ($affected <= 0) $errors[] = "Gagal approve (kemungkinan sudah diproses admin lain).";
                        else $info = "Booking #{$booking_id} disetujui.";
                    }

                    if ($action === 'reject_booking') {
                        $ket = trim(($bk['keterangan'] ?? '') . " | Ditolak admin {$adminName} " . date('Y-m-d H:i:s')
                            . ($reason !== '' ? " | Alasan: {$reason}" : ''));

                        $up = $db->prepare("
                            UPDATE bookings
                            SET status='ditolak',
                                approved_by=?,
                                approved_at=NOW(),
                                keterangan=?
                            WHERE id=? AND status='pending'
                        ");
                        $up->bind_param("ssi", $adminName, $ket, $booking_id);
                        $up->execute();
                        $affected = $up->affected_rows;
                        $up->close();

                        if ($affected <= 0) $errors[] = "Gagal reject (kemungkinan sudah diproses admin lain).";
                        else $info = "Booking #{$booking_id} ditolak.";
                    }
                }
            }
        }
    }

}


   //DATA LOAD

$kelas = [];
$res = $db->query("SELECT id, nama, prodi, is_active FROM kelas ORDER BY prodi, nama");
while ($r = $res->fetch_assoc()) $kelas[] = $r;

$rooms = [];
$res = $db->query("SELECT id, kode_ruang, gedung, lantai, is_active FROM rooms ORDER BY gedung, lantai, kode_ruang");
while ($r = $res->fetch_assoc()) $rooms[] = $r;

$jadwal = [];
$pendingBookings = [];
$res = $db->query("
    SELECT b.*, r.kode_ruang, r.gedung, r.lantai
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE b.status = 'pending'
    ORDER BY b.created_at ASC, b.tanggal ASC, b.jam_mulai ASC
");
while ($r = $res->fetch_assoc()) $pendingBookings[] = $r;


$dayMap = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];

render_header("Admin Dashboard");
?>

<a href="index.php" class="breadcrumb-back">← Kembali</a>
<div class="page-title">Dashboard Admin</div>
<div class="page-subtitle">
  Kelola <strong>kelas</strong> dan <strong>jadwal mingguan</strong>.
</div>

<style>

.admin-tabs{display:flex;gap:8px;margin:12px 0 18px 0;flex-wrap:wrap}
.admin-tabs a{padding:8px 12px;border:1px solid #ddd;border-radius:10px;text-decoration:none}
.admin-tabs a.active{background:#111;color:#fff;border-color:#111}


.table-body{padding:12px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
.form-grid .full{grid-column:1/-1}

.field label{display:block;font-size:11px;color:#444;margin-bottom:6px}
.field input,.field select{
  width:100%;
  padding:10px 12px;
  border-radius:10px;
  border:1px solid #ccc;
  background:#fff;
  color:#222;
  font-size:13px;
}
.field input:focus,.field select:focus{
  outline:none;
  border-color:#00695c;
  box-shadow:0 0 0 3px rgba(0,105,92,.12);
}
.time-row{display:flex;gap:10px}
.time-row input{flex:1}
.actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}


.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #ddd}
.badge.on{background:#eaffea;border-color:#b6e6b6}
.badge.off{background:#ffecec;border-color:#f0b6b6}


.jadwal-table{width:100%;border-collapse:collapse}
.jadwal-table th,.jadwal-table td{padding:10px 10px;border-top:1px solid #eee;text-align:left;font-size:12px;vertical-align:top}
.jadwal-table thead th{border-top:none;background:#fafafa;font-size:12px}


@media (max-width: 720px){
  .form-grid{grid-template-columns:1fr}
  .form-grid .full{grid-column:auto}
}
</style>

<?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?= e(implode(" | ", $errors)) ?></div>
<?php elseif (!empty($info)): ?>
  <div class="alert alert-success"><?= e($info) ?></div>
<?php endif; ?>

<div class="admin-tabs">
    <a class="<?= $tab==='kelas'?'active':'' ?>" href="admin_dashboard.php?tab=kelas">Kelola Kelas</a>
    <a class="<?= $tab==='jadwal'?'active':'' ?>" href="admin_dashboard.php?tab=jadwal">Kelola Jadwal</a>
    <a class="<?= $tab==='booking'?'active':'' ?>" href="admin_dashboard.php?tab=booking">Approval Booking</a>

</div>

<?php if ($tab === 'kelas'): ?>

  <div class="table-wrapper">
    <div class="table-header">Tambah Kelas</div>
    <div class="table-body">
      <form method="post" class="form-grid">
        <input type="hidden" name="action" value="add_kelas">

        <div class="field">
          <label>Nama Kelas</label>
          <input type="text" name="nama" placeholder="IF-2A" required>
        </div>

        <div class="field">
          <label>Prodi</label>
          <input type="text" name="prodi" placeholder="Informatika" required>
        </div>

        <div class="actions full">
          <button class="btn-outline" type="submit">Tambah</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-wrapper" style="margin-top:14px;">
    <div class="table-header">Daftar Kelas</div>

    <?php if (empty($kelas)): ?>
      <div class="empty-message">Belum ada kelas.</div>
    <?php else: ?>
      <table class="jadwal-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Kelas</th>
            <th>Prodi</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($kelas as $k): ?>
          <tr>
            <td><?= (int)$k['id'] ?></td>
            <td><?= e($k['nama']) ?></td>
            <td><?= e($k['prodi']) ?></td>
            <td>
              <?php if ((int)$k['is_active'] === 1): ?>
                <span class="badge on">AKTIF</span>
              <?php else: ?>
                <span class="badge off">NONAKTIF</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'jadwal'): ?>

  <div class="table-wrapper">
    <div class="table-header">Input Jadwal Mingguan</div>

    <div class="table-body">
      <form method="post" class="form-grid">
        <input type="hidden" name="action" value="add_jadwal">

        <div class="field">
          <label>Kelas</label>
          <select name="kelas_id" required>
            <option value="">-- pilih kelas --</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= (int)$k['id'] ?>">
                <?= e($k['nama']) ?> (<?= e($k['prodi']) ?>) <?= ((int)$k['is_active']===1 ? '' : ' - NONAKTIF') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Ruang (hanya yang aktif)</label>
          <select name="room_id" required>
            <option value="">-- pilih ruangan --</option>
            <?php foreach ($rooms as $r): ?>
              <?php if ((int)$r['is_active'] !== 1) continue; ?>
              <option value="<?= (int)$r['id'] ?>">
                <?= e($r['kode_ruang']) ?> (G<?= e($r['gedung']) ?> Lt.<?= (int)$r['lantai'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label>Matkul</label>
          <input type="text" name="matkul" placeholder="Basis Data" required>
        </div>

        <div class="field full">
          <label>Dosen (opsional)</label>
          <input type="text" name="dosen" placeholder="Dosen A">
        </div>

        <div class="field">
          <label>Hari</label>
          <select name="day_of_week" required>
            <option value="1">Senin</option>
            <option value="2">Selasa</option>
            <option value="3">Rabu</option>
            <option value="4">Kamis</option>
            <option value="5">Jumat</option>
            <option value="6">Sabtu</option>
            <option value="7">Minggu</option>
          </select>
        </div>

        <div class="field">
          <label>Jam</label>
          <div class="time-row">
            <input type="time" name="jam_mulai" required>
            <input type="time" name="jam_selesai" required>
          </div>
        </div>

        <div class="actions full">
          <button class="btn-outline" type="submit">Simpan Jadwal</button>

          <?php if (file_exists(__DIR__ . '/generate_jadwal_to_bookings.php')): ?>
            <a class="btn-outline" href="generate_jadwal_to_bookings.php?week=0">Generate Minggu Ini</a>
            <a class="btn-outline" href="generate_jadwal_to_bookings.php?week=1">Generate Minggu Depan</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($tab === 'booking'): ?>

  <div class="table-wrapper">
    <div class="table-header">Permintaan Booking (Pending)</div>

    <?php if (empty($pendingBookings)): ?>
      <div class="empty-message">Tidak ada booking pending.</div>
    <?php else: ?>
      <table class="jadwal-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Ruang</th>
            <th>Peminjam</th>
            <th>Kelas</th>
            <th>Keperluan</th>
            <th>Dibuat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingBookings as $b): ?>
            <tr>
              <td><?= (int)$b['id'] ?></td>
              <td><?= e($b['tanggal']) ?></td>
              <td><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?></td>
              <td><?= e($b['kode_ruang']) ?> (G<?= e($b['gedung']) ?> Lt.<?= (int)$b['lantai'] ?>)</td>
              <td><?= e($b['nama_peminjam'] ?: $b['peminjam'] ?: '-') ?></td>
              <td><?= e($b['nama_kelas'] ?: '-') ?></td>
              <td><?= e($b['keperluan'] ?: '-') ?></td>
              <td><?= e($b['created_at'] ?? '-') ?></td>
              <td>
                <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center"
                      onsubmit="return confirm('Proses aksi ini?');">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">

                  <button class="btn-outline" type="submit" name="action" value="approve_booking">Setujui</button>

                  <input type="text" name="reason" placeholder="Alasan tolak (opsional)"
                         style="padding:8px 10px;border:1px solid #ccc;border-radius:10px;min-width:180px">

                  <button class="btn-outline" type="submit" name="action" value="reject_booking">Tolak</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="table-wrapper" style="margin-top:14px;">
    <div class="table-header">Daftar Jadwal Mingguan</div>

    <?php if (empty($jadwal)): ?>
      <div class="empty-message">Belum ada jadwal mingguan.</div>
    <?php else: ?>
      <table class="jadwal-table">
        <thead>
          <tr>
            <th>Kelas</th>
            <th>Hari</th>
            <th>Jam</th>
            <th>Matkul</th>
            <th>Dosen</th>
            <th>Ruang</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jadwal as $j): ?>
            <tr>
              <td>
                <?= e($j['kelas_nama']) ?>
                <?php if ((int)$j['is_active'] !== 1): ?>
                  <span class="badge off">KELAS NONAKTIF</span>
                <?php endif; ?>
              </td>
              <td><?= e($dayMap[(int)$j['day_of_week']] ?? $j['day_of_week']) ?></td>
              <td><?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?></td>
              <td><?= e($j['matkul']) ?></td>
              <td><?= e($j['dosen'] ?: '-') ?></td>
              <td>
                <?= e($j['kode_ruang']) ?> (G<?= e($j['gedung']) ?> Lt.<?= (int)$j['lantai'] ?>)
                <?php if ((int)($j['room_active'] ?? 1) !== 1): ?>
                  <span class="badge off">RUANG NONAKTIF</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" onsubmit="return confirm('Hapus jadwal ini?');">
                  <input type="hidden" name="action" value="delete_jadwal">
                  <input type="hidden" name="jadwal_id" value="<?= (int)$j['id'] ?>">
                  <button class="btn-outline" type="submit">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php render_footer(); ?>
