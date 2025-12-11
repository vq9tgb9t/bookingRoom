<?php
require 'layout.php';
$_GET['page'] = 'home';
render_header("Sistem Booking Kelas - Beranda");
?>

<div class="hero-card">
    <div class="hero-title">Sistem Booking Kelas</div>
    <div class="hero-subtitle">Universitas Islam Negeri Prof. K.H. Saifuddin Zuhri</div>
    <div class="hero-desc">
        Kampus 2 Purbalingga. Cek ketersediaan Gedung D &amp; S secara real-time.
    </div>
</div>

<div class="cards-row">
    <!-- Card Cek Ruangan -->
    <div class="card">
        <div class="card-header">
            <div class="icon-badge">🔒</div>
            <div class="card-title">Cek Ruangan</div>
        </div>
        <div class="card-desc">
            Lihat status ruang kelas yang kosong dan sedang digunakan di Gedung D dan Gedung S.
        </div>
        <a href="status.php?page=status" class="card-footer-link">
            Buka Dashboard →
        </a>
    </div>

    <!-- Card Jadwal Kuliah -->
    <div class="card">
        <div class="card-header">
            <div class="icon-badge">📅</div>
            <div class="card-title">Jadwal Kuliah</div>
        </div>
        <div class="card-desc">
            Lihat daftar booking yang sudah terdaftar, detail peminjam, dan mata kuliah.
        </div>
        <a href="jadwal.php?page=jadwal" class="card-footer-link">
            Lihat Jadwal →
        </a>
    </div>
</div>

<!-- Banner Buat Booking Baru -->
<div class="green-banner">
    <div>
        <div class="green-banner-text-title">Ingin meminjam ruangan?</div>
        <div class="green-banner-text-desc">
            Login dan isi formulir peminjaman untuk mengamankan ruangan kelas.
        </div>
    </div>
    <a href="login.php?page=login" class="btn-secondary">Buat Booking Baru</a>
</div>

<?php
render_footer();
