<?php
// jadwal.php
require 'config.php';
require 'layout.php';

$_GET['page'] = 'jadwal';

// mode tampilan
$mode_view = $_GET['mode_view'] ?? 'active';

// ambil semua booking join ruangan
$sql = "
    SELECT b.*, r.kode_ruang, r.gedung, r.lantai
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.status = 'disetujui'
    ORDER BY b.tanggal ASC, b.jam_mulai ASC
";
$res = $db->query($sql);

$rows = [];
$now_ts = time();

while ($b = $res->fetch_assoc()) {
    $start_ts = strtotime($b['tanggal'] . ' ' . $b['jam_mulai']);
    $end_ts   = strtotime($b['tanggal'] . ' ' . $b['jam_selesai']);

    $include = true;
    if ($mode_view === 'active') {
        // hanya yang masih berjalan / akan datang
        if ($end_ts < $now_ts) {
            $include = false;
        }
    } elseif ($mode_view === 'history') {
        // hanya yang sudah lewat
        if ($end_ts >= $now_ts) {
            $include = false;
        }
    } // mode_view=all -> semua

    if ($include) {
        $b['_start_ts'] = $start_ts;
        $b['_end_ts'] = $end_ts;
        $rows[] = $b;
    }
}

render_header("Daftar Jadwal Kuliah");
?>

<a href="index.php" class="breadcrumb-back">← Kembali</a>

<div class="page-title">Daftar Jadwal Kuliah</div>
<div class="page-subtitle">
    Informasi penggunaan ruangan yang <strong>sedang berjalan</strong> atau <strong>akan datang</strong>.
    Booking yang sudah lewat otomatis hilang dari daftar ini (mode default).
</div>
<!-- filter mode tampilan -->
<div class="filter-row jadwal-filter-row">
    <div class="filter-item">
        <span>Mode tampilan</span>
        <form method="get" class="jadwal-filter-form">
            <input type="hidden" name="page" value="jadwal">
            <select name="mode_view">
                <option value="active"  <?= $mode_view === 'active' ? 'selected' : '' ?>>Hanya yang aktif & akan datang</option>
                <option value="all"     <?= $mode_view === 'all' ? 'selected' : '' ?>>Semua booking</option>
                <option value="history" <?= $mode_view === 'history' ? 'selected' : '' ?>>Riwayat (yang sudah lewat)</option>
            </select>
            <button type="submit" class="btn-outline">Terapkan</button>
        </form>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-header">Daftar Jadwal Booking</div>

    <?php if (empty($rows)): ?>
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
                <?php $no=1; foreach ($rows as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['tanggal']) ?></td>
                        <td>
                            <?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?>
                        </td>
                        <td>
                            <?= e($row['kode_ruang']) ?>
                            (Gedung <?= e($row['gedung']) ?> Lt.<?= (int)$row['lantai'] ?>)
                        </td>
                        <td>
                            <?= e($row['nama_peminjam'] ?: $row['peminjam'] ?: '-') ?>
                        </td>
                        <td>
                            <?php
                              $ketParts = [];
                              if (!empty($row['nama_kelas']))   $ketParts[] = 'Kelas: '.$row['nama_kelas'];
                              if (!empty($row['keperluan']))    $ketParts[] = 'Keperluan: '.$row['keperluan'];
                              if (!empty($row['deskripsi']))    $ketParts[] = $row['deskripsi'];
                              echo e(implode('; ', $ketParts) ?: '-');
                            ?>
                        </td>
                        <td class="jadwal-status-cell">
                            <?= e(strtoupper($row['status'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
render_footer();
