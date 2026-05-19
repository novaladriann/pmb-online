<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

$query = mysqli_query(
    $conn,
    "SELECT * FROM biodata_mahasiswa WHERE user_id='{$user['id']}'"
);

$docQuery = mysqli_query(
    $conn,
    "SELECT * FROM documents WHERE user_id='{$user['id']}'"
);

$documents = mysqli_fetch_assoc($docQuery);

$biodata = mysqli_fetch_assoc($query);

include '../../app/views/layouts/header.php';

?>

<div class="sidebar">

    <h4 class="text-center fw-bold mb-4">
        PMB ONLINE
    </h4>

    <a href="dashboard.php">
        <i class="bi bi-grid"></i> Dashboard
    </a>

    <a href="biodata.php">
        <i class="bi bi-person"></i> Biodata
    </a>

    <a href="upload.php">
        <i class="bi bi-file-earmark-arrow-up"></i> Upload Berkas
    </a>

    <a href="pengumuman.php">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>

    <?php if (isset($biodata) && ($biodata['status_hasil'] ?? '') === 'Diterima' && ($biodata['is_published'] ?? 0)): ?>
    <a href="daftar_ulang.php">
        <i class="bi bi-clipboard-check"></i> Daftar Ulang
    </a>
    <?php endif; ?>

    <a href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>

<div class="main-content">

    <h2 class="fw-bold mb-4">
        Selamat Datang,
        <?= $user['fullname']; ?>
    </h2>

    <div class="row">

        <div class="col-md-4 mb-4">

            <div class="card card-modern p-4">

                <h5>Status Pendaftaran</h5>

                <?php if ($biodata): ?>

                    <span class="badge bg-warning">
                        <?= $biodata['status_pendaftaran']; ?>
                    </span>

                <?php else: ?>

                    <span class="badge bg-danger">
                        Belum Isi Biodata
                    </span>

                <?php endif; ?>

            </div>
        </div>

        <div class="col-md-4 mb-4">

            <div class="card card-modern p-4">

                <h5>Biodata</h5>

                <p class="text-muted">
                    Lengkapi biodata mahasiswa
                </p>

                <a href="biodata.php" class="btn btn-primary">
                    Isi Biodata
                </a>

            </div>
        </div>

        <div class="col-md-4 mb-4">

            <div class="card card-modern p-4">

                <h5>Progress</h5>


                <?php

                $progress = 25;

                if ($biodata) {
                    $progress += 35;
                }

                if ($documents) {
                    $progress += 40;
                }

                ?>

                <div class="progress mt-3">

                    <div class="progress-bar"
                        style="width:<?= $progress; ?>%">

                        <?= $progress; ?>%

                    </div>

                </div>

            </div>
        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>