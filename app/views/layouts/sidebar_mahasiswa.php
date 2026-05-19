<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar">

    <h4 class="text-center fw-bold mb-4 px-3">PMB ONLINE</h4>

    <a href="dashboard.php"
       class="<?= $currentPage == 'dashboard.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-grid"></i> Dashboard
    </a>

    <a href="biodata.php"
       class="<?= $currentPage == 'biodata.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-person"></i> Biodata
    </a>

    <a href="upload.php"
       class="<?= $currentPage == 'upload.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-file-earmark-arrow-up"></i> Upload Berkas
    </a>

    <a href="pengumuman.php"
       class="<?= $currentPage == 'pengumuman.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>

    <?php if (isset($biodata) && ($biodata['status_hasil'] ?? '') === 'Diterima' && ($biodata['is_published'] ?? 0)): ?>
    <a href="daftar_ulang.php"
       class="<?= $currentPage == 'daftar_ulang.php' ? 'sidebar-active' : '' ?>">
        <i class="bi bi-clipboard-check"></i> Daftar Ulang
    </a>
    <?php endif; ?>

    <a href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>