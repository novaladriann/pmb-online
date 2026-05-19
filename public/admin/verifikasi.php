<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user = $_SESSION['user'];

// Filter status
$filter = $_GET['filter'] ?? 'semua';

$whereClause = "";
if ($filter == 'menunggu') {
    $whereClause = "AND d.status_verifikasi = 'Menunggu Verifikasi'";
} elseif ($filter == 'terverifikasi') {
    $whereClause = "AND d.status_verifikasi = 'Terverifikasi'";
} elseif ($filter == 'ditolak') {
    $whereClause = "AND d.status_verifikasi = 'Ditolak'";
} elseif ($filter == 'belum') {
    $whereClause = "AND (d.status_verifikasi = 'Belum Upload' OR d.status_verifikasi IS NULL)";
}

$data = mysqli_query($conn, "
    SELECT
        u.id,
        u.fullname,
        u.email,
        u.created_at AS tgl_daftar,
        b.asal_sekolah,
        b.jurusan_pilihan,
        b.status_pendaftaran,
        COALESCE(d.status_verifikasi, 'Belum Upload') AS status_verifikasi
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
    $whereClause
    ORDER BY
        CASE COALESCE(d.status_verifikasi, 'Belum Upload')
            WHEN 'Menunggu Verifikasi' THEN 1
            WHEN 'Ditolak' THEN 2
            WHEN 'Belum Upload' THEN 3
            WHEN 'Terverifikasi' THEN 4
        END ASC,
        u.id DESC
");

// Hitung per status
$countQuery = mysqli_query($conn, "
    SELECT COALESCE(d.status_verifikasi, 'Belum Upload') AS sv, COUNT(*) AS total
    FROM users u
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
    GROUP BY sv
");
$counts = ['Menunggu Verifikasi' => 0, 'Terverifikasi' => 0, 'Ditolak' => 0, 'Belum Upload' => 0];
while ($c = mysqli_fetch_assoc($countQuery)) {
    $counts[$c['sv']] = $c['total'];
}

include '../../app/views/layouts/header.php';
?>

<div class="sidebar">

    <h4 class="text-center fw-bold mb-4">ADMIN PMB</h4>

    <a href="dashboard.php">
        <i class="bi bi-grid"></i> Dashboard
    </a>

    <a href="mahasiswa.php">
        <i class="bi bi-people"></i> Data Mahasiswa
    </a>

    <a href="verifikasi.php" style="background:rgba(255,255,255,0.2);">
        <i class="bi bi-file-earmark-check"></i> Verifikasi Berkas
    </a>

    <a href="pengumuman.php">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>

    <a href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>

<div class="main-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Verifikasi Berkas</h2>
            <p class="text-muted mb-0">Seleksi dan verifikasi dokumen calon mahasiswa</p>
        </div>
    </div>

    <!-- STATISTIK CEPAT -->
    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <a href="verifikasi.php?filter=menunggu" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-warning border-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                        <div>
                            <div class="fw-bold fs-4"><?= $counts['Menunggu Verifikasi']; ?></div>
                            <div class="text-muted small">Menunggu</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="verifikasi.php?filter=terverifikasi" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-success border-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-patch-check fs-2 text-success"></i>
                        <div>
                            <div class="fw-bold fs-4"><?= $counts['Terverifikasi']; ?></div>
                            <div class="text-muted small">Terverifikasi</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="verifikasi.php?filter=ditolak" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-danger border-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-x-circle fs-2 text-danger"></i>
                        <div>
                            <div class="fw-bold fs-4"><?= $counts['Ditolak']; ?></div>
                            <div class="text-muted small">Ditolak</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="verifikasi.php?filter=belum" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-secondary border-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-cloud-upload fs-2 text-secondary"></i>
                        <div>
                            <div class="fw-bold fs-4"><?= $counts['Belum Upload']; ?></div>
                            <div class="text-muted small">Belum Upload</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <!-- TABEL -->
    <div class="card card-modern p-4">

        <!-- Filter + Cari -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

            <div class="d-flex gap-2 flex-wrap">
                <a href="verifikasi.php?filter=semua"
                    class="btn btn-sm <?= $filter == 'semua' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Semua
                </a>
                <a href="verifikasi.php?filter=menunggu"
                    class="btn btn-sm <?= $filter == 'menunggu' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    Menunggu
                </a>
                <a href="verifikasi.php?filter=terverifikasi"
                    class="btn btn-sm <?= $filter == 'terverifikasi' ? 'btn-success' : 'btn-outline-success' ?>">
                    Terverifikasi
                </a>
                <a href="verifikasi.php?filter=ditolak"
                    class="btn btn-sm <?= $filter == 'ditolak' ? 'btn-danger' : 'btn-outline-danger' ?>">
                    Ditolak
                </a>
            </div>

            <input type="text"
                id="searchInput"
                class="form-control w-auto"
                placeholder="🔍 Cari nama / email..."
                style="min-width:220px;">

        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabelVerifikasi">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Mahasiswa</th>
                        <th>Email</th>
                        <th>Jurusan Pilihan</th>
                        <th>Status Dokumen</th>
                        <th>Tgl. Daftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++; ?></td>

                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['fullname']); ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($row['asal_sekolah'] ?? '-'); ?></div>
                        </td>

                        <td class="text-muted small"><?= htmlspecialchars($row['email']); ?></td>

                        <td><?= htmlspecialchars($row['jurusan_pilihan'] ?? '-'); ?></td>

                        <td>
                            <?php
                            $sv = $row['status_verifikasi'];
                            if ($sv == 'Terverifikasi') {
                                echo "<span class='badge bg-success'>✓ Terverifikasi</span>";
                            } elseif ($sv == 'Menunggu Verifikasi') {
                                echo "<span class='badge bg-warning text-dark'>⏳ Menunggu</span>";
                            } elseif ($sv == 'Ditolak') {
                                echo "<span class='badge bg-danger'>✗ Ditolak</span>";
                            } else {
                                echo "<span class='badge bg-secondary'>— Belum Upload</span>";
                            }
                            ?>
                        </td>

                        <td class="text-muted small">
                            <?= date('d/m/Y', strtotime($row['tgl_daftar'])); ?>
                        </td>

                        <td class="text-center">
                            <a href="verifikasi_detail.php?id=<?= $row['id']; ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Periksa
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
// Search filter
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tabelVerifikasi tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(keyword) ? '' : 'none';
    });
});
</script>

<?php include '../../app/views/layouts/footer.php'; ?>