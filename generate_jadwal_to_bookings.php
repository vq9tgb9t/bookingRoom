<?php
require 'config.php';
require 'layout.php';

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

date_default_timezone_set('Asia/Jakarta');

function current_level(): string {
  $u = $_SESSION['user'] ?? null;
  return is_array($u) ? (string)($u['level'] ?? '') : '';
}

function current_username(): string {
  $u = $_SESSION['user'] ?? null;
  if (is_array($u)) return (string)($u['username'] ?? '');
  return is_string($u) ? $u : '';
}

if (current_level() !== 'admin') {
  die("Admin only.");
}

$week = (int)($_GET['week'] ?? 0);
if ($week < 0) $week = 0;
if ($week > 52) $week = 52;

$weekStart = new DateTime('monday this week');
$weekStart->modify("+{$week} week");
$weekEnd = (clone $weekStart)->modify("+6 day");

$startDate = $weekStart->format('Y-m-d');
$endDate   = $weekEnd->format('Y-m-d');

$systemTag = 'system_jadwal';

$del = $db->prepare("DELETE FROM bookings WHERE approved_by = ? AND tanggal BETWEEN ? AND ?");
if (!$del) die("Prepare delete gagal: " . $db->error);
$del->bind_param("sss", $systemTag, $startDate, $endDate);
$del->execute();
$deleted = $del->affected_rows;
$del->close();


$sql = "
  SELECT jm.kelas_id, k.nama AS kelas_nama,
         jm.room_id, jm.matkul, jm.dosen,
         jm.day_of_week, jm.jam_mulai, jm.jam_selesai
  FROM jadwal_mingguan jm
  JOIN kelas k ON k.id = jm.kelas_id
  ORDER BY jm.kelas_id, jm.day_of_week, jm.jam_mulai
";
$res = $db->query($sql);
if (!$res) die("Query jadwal_mingguan gagal: " . $db->error);

$inserted = 0;
$skipped  = 0;


$ins = $db->prepare("
  INSERT INTO bookings
  (user_id, room_id, nama_kelas, nama_peminjam, keperluan, deskripsi,
   tanggal, jam_mulai, jam_selesai, status, approved_at, approved_by, keterangan)
  VALUES
  (NULL, ?, ?, 'SISTEM', 'Jadwal Kuliah', ?, ?, ?, ?, 'disetujui', NOW(), ?, ?)
");
if (!$ins) die("Prepare insert gagal: " . $db->error);

while ($r = $res->fetch_assoc()) {
  $dow = (int)$r['day_of_week']; // 1..7
  $tanggal = (clone $weekStart)->modify('+' . ($dow - 1) . ' day')->format('Y-m-d');

  $room_id = (int)$r['room_id'];
  $nama_kelas = (string)$r['kelas_nama'];

  $desc = trim(($r['matkul'] ?? '') . (empty($r['dosen']) ? '' : ' - ' . $r['dosen']));
  $jam_mulai = (string)$r['jam_mulai'];
  $jam_selesai = (string)$r['jam_selesai'];

 
  $cek = $db->prepare("
    SELECT COUNT(*) AS c
    FROM bookings
    WHERE room_id = ?
      AND tanggal = ?
      AND status IN ('disetujui','pending')
      AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
      AND (approved_by IS NULL OR approved_by <> ?)
  ");
  if (!$cek) die("Prepare cek bentrok gagal: " . $db->error);

  $cek->bind_param("issss", $room_id, $tanggal, $jam_mulai, $jam_selesai, $systemTag);
  $cek->execute();
  $cnt = (int)($cek->get_result()->fetch_assoc()['c'] ?? 0);
  $cek->close();

  if ($cnt > 0) { $skipped++; continue; }

  $ket = "AUTO-JADWAL ({$startDate} s/d {$endDate})";

  
  $ins->bind_param("isssssss", $room_id, $nama_kelas, $desc, $tanggal, $jam_mulai, $jam_selesai, $systemTag, $ket);
  $ins->execute();
  $inserted++;
}

$ins->close();

render_header("Generate Jadwal Mingguan -> Bookings");
?>
<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="page-title">Generate Jadwal Mingguan → Bookings</div>
<div class="page-subtitle">
  Range: <strong><?= e($startDate) ?></strong> s/d <strong><?= e($endDate) ?></strong>
</div>

<div class="table-wrapper">
  <div class="table-header">Hasil</div>
  <div style="padding:12px;">
    Deleted jadwal lama: <strong><?= (int)$deleted ?></strong><br>
    Inserted jadwal: <strong><?= (int)$inserted ?></strong><br>
    Skipped (bentrok booking): <strong><?= (int)$skipped ?></strong><br><br>

    <a class="btn-outline" href="generate_jadwal_to_bookings.php?week=0">Generate Minggu Ini</a>
    <a class="btn-outline" href="generate_jadwal_to_bookings.php?week=1">Generate Minggu Depan</a>
    <a class="btn-outline" href="jadwal.php?page=jadwal&view=booking">Lihat Booking</a>
  </div>
</div>

<?php render_footer(); ?>
