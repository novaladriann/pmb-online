<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user = $_SESSION['user'];

// ============================================================
// STATISTIK UTAMA
// ============================================================
$stat = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT u.id)                                                           AS total_mahasiswa,
        SUM(CASE WHEN COALESCE(d.status_verifikasi,'Belum Upload') = 'Menunggu Verifikasi' THEN 1 ELSE 0 END) AS menunggu_verifikasi,
        SUM(CASE WHEN COALESCE(d.status_verifikasi,'Belum Upload') = 'Terverifikasi'       THEN 1 ELSE 0 END) AS berkas_terverifikasi,
        SUM(CASE WHEN b.status_hasil = 'Diterima'       THEN 1 ELSE 0 END)            AS diterima,
        SUM(CASE WHEN b.status_hasil = 'Tidak Diterima' THEN 1 ELSE 0 END)            AS tidak_diterima
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
"));

// Statistik pembayaran
$statBayar = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                                                AS total_bayar,
        SUM(CASE WHEN status_pembayaran = 'Menunggu Konfirmasi' THEN 1 ELSE 0 END)        AS menunggu_bayar,
        SUM(CASE WHEN status_pembayaran = 'Dikonfirmasi'        THEN 1 ELSE 0 END)        AS lunas
    FROM daftar_ulang
")) ?: ['total_bayar' => 0, 'menunggu_bayar' => 0, 'lunas' => 0];

// 5 pendaftar terbaru
$terbaru = mysqli_query($conn, "
    SELECT
        u.id, u.fullname, u.email, u.created_at,
        b.jurusan_pilihan,
        COALESCE(d.status_verifikasi, 'Belum Upload') AS status_verifikasi
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
    ORDER BY u.created_at DESC
    LIMIT 5
");

include '../../app/views/layouts/header.php';
include '../../app/views/layouts/sidebar_admin.php';
?>

<div class="main-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">Dashboard Admin</h2>
            <p class="text-muted mb-0">
                Selamat datang, <strong><?= htmlspecialchars($user['fullname']); ?></strong> —
                <small><?= date('l, d F Y'); ?></small>
            </p>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="row mb-4 g-3">

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto">
                        <i class="bi bi-people fs-4 text-primary"></i>
                    </div>
                    <div class="fw-bold fs-3"><?= $stat['total_mahasiswa']; ?></div>
                    <div class="text-muted small">Total Pendaftar</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="verifikasi.php?filter=menunggu" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-warning bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto">
                        <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                    </div>
                    <div class="fw-bold fs-3 text-warning"><?= $stat['menunggu_verifikasi']; ?></div>
                    <div class="text-muted small">Menunggu Verifikasi</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="verifikasi.php?filter=terverifikasi" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-info bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto">
                        <i class="bi bi-patch-check fs-4 text-info"></i>
                    </div>
                    <div class="fw-bold fs-3 text-info"><?= $stat['berkas_terverifikasi']; ?></div>
                    <div class="text-muted small">Berkas Terverifikasi</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="pengumuman.php?filter=diterima" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-success bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <div class="fw-bold fs-3 text-success"><?= $stat['diterima']; ?></div>
                    <div class="text-muted small">Diterima</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="pengumuman.php?filter=tidak_diterima" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-danger bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto">
                        <i class="bi bi-x-circle fs-4 text-danger"></i>
                    </div>
                    <div class="fw-bold fs-3 text-danger"><?= $stat['tidak_diterima']; ?></div>
                    <div class="text-muted small">Tidak Diterima</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="verifikasi_pembayaran.php" class="text-decoration-none">
                <div class="card card-modern p-3 text-center h-100">
                    <div class="bg-purple bg-opacity-10 rounded-3 p-2 mb-2 d-inline-block mx-auto"
                         style="background:rgba(111,66,193,0.1)!important;">
                        <i class="bi bi-credit-card-2-front fs-4" style="color:#6f42c1;"></i>
                    </div>
                    <div class="fw-bold fs-3" style="color:#6f42c1;"><?= $statBayar['menunggu_bayar']; ?></div>
                    <div class="text-muted small">Menunggu Konfirmasi Bayar</div>
                </div>
            </a>
        </div>

    </div>

    <!-- AKSES CEPAT + PENDAFTAR TERBARU -->
    <div class="row g-4">

        <!-- AKSES CEPAT -->
        <div class="col-lg-4">
            <div class="card card-modern p-4 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-lightning-charge text-warning me-2"></i>Akses Cepat
                </h6>
                <div class="d-grid gap-2">
                    <a href="verifikasi.php?filter=menunggu" class="btn btn-outline-warning text-start">
                        <i class="bi bi-file-earmark-check me-2"></i>
                        Periksa Berkas Menunggu
                        <?php if ($stat['menunggu_verifikasi'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?= $stat['menunggu_verifikasi']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="pengumuman.php?filter=menunggu" class="btn btn-outline-primary text-start">
                        <i class="bi bi-megaphone me-2"></i>
                        Proses Pengumuman
                    </a>
                    <a href="verifikasi_pembayaran.php" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-credit-card-2-front me-2"></i>
                        Verifikasi Pembayaran
                        <?php if ($statBayar['menunggu_bayar'] > 0): ?>
                            <span class="badge bg-secondary float-end"><?= $statBayar['menunggu_bayar']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="mahasiswa.php" class="btn btn-outline-success text-start">
                        <i class="bi bi-people me-2"></i>
                        Lihat Semua Mahasiswa
                    </a>
                </div>
            </div>
        </div>

        <!-- PENDAFTAR TERBARU -->
        <div class="col-lg-8">
            <div class="card card-modern p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Pendaftar Terbaru
                    </h6>
                    <a href="mahasiswa.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Jurusan</th>
                                <th>Status Berkas</th>
                                <th>Tgl. Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($terbaru)): ?>
                            <tr style="cursor:pointer;"
                                onclick="location.href='verifikasi_detail.php?id=<?= $row['id']; ?>'">
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars($row['fullname']); ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($row['jurusan_pilihan'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $sv = $row['status_verifikasi'];
                                    if ($sv == 'Terverifikasi')
                                        echo "<span class='badge bg-success'>✓ Terverifikasi</span>";
                                    elseif ($sv == 'Menunggu Verifikasi')
                                        echo "<span class='badge bg-warning text-dark'>⏳ Menunggu</span>";
                                    elseif ($sv == 'Ditolak')
                                        echo "<span class='badge bg-danger'>✗ Ditolak</span>";
                                    else
                                        echo "<span class='badge bg-secondary'>— Belum Upload</span>";
                                    ?>
                                </td>
                                <td class="text-muted small"><?= date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>