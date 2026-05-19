<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <h4 class="text-center fw-bold mb-4 px-3">ADMIN PMB</h4>

    <a href="dashboard.php"
       class="<?= $currentPage == 'dashboard.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-grid"></i> Dashboard
    </a>

    <a href="mahasiswa.php"
       class="<?= $currentPage == 'mahasiswa.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-people"></i> Data Mahasiswa
    </a>

    <a href="verifikasi.php"
       class="<?= ($currentPage == 'verifikasi.php' || $currentPage == 'verifikasi_detail.php') ? 'sidebar-active' : '' ?>">
        <i class="bi bi-file-earmark-check"></i> Verifikasi Berkas
    </a>

    <a href="pengumuman.php"
       class="<?= $currentPage == 'pengumuman.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>

    <a href="verifikasi_pembayaran.php"
       class="<?= $currentPage == 'verifikasi_pembayaran.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-credit-card-2-front"></i> Verifikasi Pembayaran
    </a>

    <a href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>