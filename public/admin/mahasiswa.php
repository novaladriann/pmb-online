<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user = $_SESSION['user'];

// Filter
$filter  = $_GET['filter']  ?? 'semua';
$search  = $_GET['search']  ?? '';
$jurusan = $_GET['jurusan'] ?? '';

// Build WHERE
$conditions = ["u.role = 'mahasiswa'"];

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(u.fullname LIKE '%$s%' OR u.email LIKE '%$s%' OR b.nik LIKE '%$s%' OR b.asal_sekolah LIKE '%$s%')";
}

if ($jurusan) {
    $j = mysqli_real_escape_string($conn, $jurusan);
    $conditions[] = "b.jurusan_pilihan = '$j'";
}

if ($filter === 'lengkap') {
    $conditions[] = "b.status_pendaftaran = 'Terverifikasi'";
} elseif ($filter === 'belum') {
    $conditions[] = "(b.status_pendaftaran = 'Belum Lengkap' OR b.id IS NULL)";
} elseif ($filter === 'menunggu') {
    $conditions[] = "b.status_pendaftaran = 'Menunggu Verifikasi'";
} elseif ($filter === 'diterima') {
    $conditions[] = "b.status_hasil = 'Diterima'";
} elseif ($filter === 'tidak_diterima') {
    $conditions[] = "b.status_hasil = 'Tidak Diterima'";
}

$whereSQL = implode(' AND ', $conditions);

// Ambil data mahasiswa
$data = mysqli_query($conn, "
    SELECT
        u.id,
        u.fullname,
        u.email,
        u.created_at                                            AS tgl_daftar,
        b.nik,
        b.nisn,
        b.tempat_lahir,
        b.tanggal_lahir,
        b.jenis_kelamin,
        b.alamat,
        b.no_hp,
        b.asal_sekolah,
        b.jurusan_pilihan,
        COALESCE(b.status_pendaftaran, 'Belum Lengkap')        AS status_pendaftaran,
        COALESCE(b.status_hasil, 'Menunggu')                   AS status_hasil,
        COALESCE(b.is_published, 0)                            AS is_published,
        COALESCE(d.status_verifikasi, 'Belum Upload')          AS status_verifikasi,
        b.created_at                                            AS tgl_biodata
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE $whereSQL
    ORDER BY u.id DESC
");

// Statistik
$stat = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT u.id)                                                        AS total,
        SUM(CASE WHEN b.status_pendaftaran = 'Terverifikasi' THEN 1 ELSE 0 END)    AS terverifikasi,
        SUM(CASE WHEN b.status_pendaftaran = 'Menunggu Verifikasi' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN b.status_hasil = 'Diterima' THEN 1 ELSE 0 END)               AS diterima,
        SUM(CASE WHEN b.status_hasil = 'Tidak Diterima' THEN 1 ELSE 0 END)         AS tidak_diterima,
        SUM(CASE WHEN b.jenis_kelamin = 'Laki-laki' THEN 1 ELSE 0 END)             AS laki,
        SUM(CASE WHEN b.jenis_kelamin = 'Perempuan' THEN 1 ELSE 0 END)             AS perempuan
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    WHERE u.role = 'mahasiswa'
"));

// Daftar jurusan unik untuk filter
$jurusanList = mysqli_query($conn, "
    SELECT DISTINCT jurusan_pilihan
    FROM biodata_mahasiswa
    WHERE jurusan_pilihan IS NOT NULL AND jurusan_pilihan != ''
    ORDER BY jurusan_pilihan ASC
");

include '../../app/views/layouts/header.php';
?>

<style>
/* ===== SIDEBAR ACTIVE ===== */
.sidebar-active { background: rgba(255,255,255,0.2) !important; }

/* ===== STAT CARDS ===== */
.stat-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    color: inherit;
}
.stat-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}

/* ===== AVATAR ===== */
.avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; color: white;
    flex-shrink: 0;
}

/* ===== BADGE STATUS ===== */
.badge-status {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}

/* ===== TABLE ===== */
.tbl-mahasiswa th {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-top: none;
    white-space: nowrap;
}
.tbl-mahasiswa td { vertical-align: middle; }

/* ===== MODAL DETAIL ===== */
.detail-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    margin-bottom: 2px;
}
.detail-value {
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
}
.detail-group { margin-bottom: 16px; }

/* ===== PROGRESS BAR ===== */
.mini-progress {
    height: 5px;
    border-radius: 10px;
    background: #e9ecef;
    overflow: hidden;
}
.mini-progress-bar {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, #0d6efd, #0dcaf0);
    transition: width 0.6s ease;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #adb5bd;
}
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

/* ===== FILTER CHIPS ===== */
.filter-chip {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid #dee2e6;
    background: white;
    color: #6c757d;
    cursor: pointer;
    transition: 0.2s;
    text-decoration: none;
    white-space: nowrap;
}
.filter-chip:hover { border-color: #0d6efd; color: #0d6efd; }
.filter-chip.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
}
.filter-chip.active-success { background: #198754; border-color: #198754; color: white; }
.filter-chip.active-danger  { background: #dc3545; border-color: #dc3545; color: white; }
.filter-chip.active-warning { background: #ffc107; border-color: #ffc107; color: #212529; }
</style>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4 class="text-center fw-bold mb-4">ADMIN PMB</h4>
    <a href="dashboard.php"><i class="bi bi-grid me-2"></i> Dashboard</a>
    <a href="mahasiswa.php" class="sidebar-active"><i class="bi bi-people me-2"></i> Data Mahasiswa</a>
    <a href="verifikasi.php"><i class="bi bi-file-earmark-check me-2"></i> Verifikasi Berkas</a>
    <a href="pengumuman.php"><i class="bi bi-megaphone me-2"></i> Pengumuman</a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
</div>

<div class="main-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">Data Mahasiswa</h2>
            <p class="text-muted mb-0">Kelola dan pantau seluruh calon mahasiswa terdaftar</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="exportCSV()">
                <i class="bi bi-download me-1"></i> Export CSV
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="printTable()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- STATISTIK CARDS -->
    <div class="row mb-4 g-3">

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php?filter=semua" class="stat-card card p-3 <?= $filter=='semua'?'border-2 border-primary':'' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="bi bi-people text-primary"></i>
                    </div>
                </div>
                <div class="fw-bold fs-4"><?= $stat['total'] ?></div>
                <div class="text-muted" style="font-size:12px;">Total</div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php?filter=lengkap" class="stat-card card p-3 <?= $filter=='lengkap'?'border-2 border-success':'' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="bi bi-patch-check text-success"></i>
                    </div>
                </div>
                <div class="fw-bold fs-4"><?= $stat['terverifikasi'] ?></div>
                <div class="text-muted" style="font-size:12px;">Terverifikasi</div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php?filter=menunggu" class="stat-card card p-3 <?= $filter=='menunggu'?'border-2 border-warning':'' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-warning bg-opacity-10">
                        <i class="bi bi-hourglass-split text-warning"></i>
                    </div>
                </div>
                <div class="fw-bold fs-4"><?= $stat['menunggu'] ?></div>
                <div class="text-muted" style="font-size:12px;">Menunggu</div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php?filter=diterima" class="stat-card card p-3 <?= $filter=='diterima'?'border-2 border-success':'' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-success bg-opacity-25">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                </div>
                <div class="fw-bold fs-4 text-success"><?= $stat['diterima'] ?></div>
                <div class="text-muted" style="font-size:12px;">Diterima</div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <a href="mahasiswa.php?filter=tidak_diterima" class="stat-card card p-3 <?= $filter=='tidak_diterima'?'border-2 border-danger':'' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-danger bg-opacity-10">
                        <i class="bi bi-x-circle text-danger"></i>
                    </div>
                </div>
                <div class="fw-bold fs-4 text-danger"><?= $stat['tidak_diterima'] ?></div>
                <div class="text-muted" style="font-size:12px;">Tidak Diterima</div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="stat-icon bg-info bg-opacity-10">
                        <i class="bi bi-gender-ambiguous text-info"></i>
                    </div>
                </div>
                <div class="fw-bold fs-5">
                    <span class="text-primary"><?= $stat['laki'] ?>L</span>
                    <span class="text-muted mx-1">/</span>
                    <span class="text-danger"><?= $stat['perempuan'] ?>P</span>
                </div>
                <div class="text-muted" style="font-size:12px;">Gender</div>
            </div>
        </div>

    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="card card-modern p-3 mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

            <!-- Search -->
            <div class="input-group" style="max-width:280px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0 ps-0"
                    placeholder="Cari nama, email, NIK..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Filter Jurusan -->
            <select name="jurusan" class="form-select" style="max-width:200px;" onchange="this.form.submit()">
                <option value="">Semua Jurusan</option>
                <?php while ($j = mysqli_fetch_assoc($jurusanList)): ?>
                    <option value="<?= htmlspecialchars($j['jurusan_pilihan']) ?>"
                        <?= $jurusan == $j['jurusan_pilihan'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['jurusan_pilihan']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit" class="btn btn-primary px-3">Cari</button>

            <?php if ($search || $jurusan): ?>
                <a href="mahasiswa.php?filter=<?= $filter ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            <?php endif; ?>

        </form>
    </div>

    <!-- FILTER CHIPS -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php
        $chips = [
            'semua'         => ['label' => 'Semua Mahasiswa', 'class' => 'active'],
            'belum'         => ['label' => 'Belum Lengkap',   'class' => 'active'],
            'menunggu'      => ['label' => 'Menunggu Verifikasi', 'class' => 'active-warning'],
            'lengkap'       => ['label' => 'Terverifikasi',   'class' => 'active-success'],
            'diterima'      => ['label' => 'Diterima',        'class' => 'active-success'],
            'tidak_diterima'=> ['label' => 'Tidak Diterima',  'class' => 'active-danger'],
        ];
        foreach ($chips as $key => $chip):
            $isActive = $filter === $key;
            $activeClass = $isActive ? $chip['class'] : '';
        ?>
            <a href="mahasiswa.php?filter=<?= $key ?>&search=<?= urlencode($search) ?>&jurusan=<?= urlencode($jurusan) ?>"
               class="filter-chip <?= $activeClass ?>">
                <?= $chip['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- TABEL -->
    <div class="card card-modern p-0 overflow-hidden">

        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">
                Daftar Mahasiswa
                <?php if ($search || $jurusan || $filter !== 'semua'): ?>
                    <span class="badge bg-primary ms-2" id="rowCount"></span>
                <?php endif; ?>
            </h6>
            <small class="text-muted">Klik baris untuk detail</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tbl-mahasiswa" id="tableMahasiswa">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Nama Mahasiswa</th>
                        <th>Kontak</th>
                        <th>Asal Sekolah</th>
                        <th>Jurusan Pilihan</th>
                        <th>Status Berkas</th>
                        <th>Hasil Seleksi</th>
                        <th>Tgl. Daftar</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $rows = [];
                    while ($row = mysqli_fetch_assoc($data)) {
                        $rows[] = $row;
                    }
                    ?>

                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <p class="mb-0 fw-semibold">Tidak ada data mahasiswa</p>
                                <small>Coba ubah filter atau kata kunci pencarian</small>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row):
                        // Avatar warna berdasarkan nama
                        $colors = ['#4361ee','#7209b7','#f72585','#3a86ff','#06d6a0','#ef476f','#ffd166'];
                        $colorIdx = crc32($row['fullname']) % count($colors);
                        $avatarColor = $colors[abs($colorIdx)];
                        $initials = strtoupper(substr($row['fullname'], 0, 1));
                        if (strpos($row['fullname'], ' ') !== false) {
                            $parts = explode(' ', $row['fullname']);
                            $initials = strtoupper(substr($parts[0],0,1) . substr(end($parts),0,1));
                        }

                        // Status verifikasi badge
                        $sv = $row['status_verifikasi'];
                        $svBadge = match($sv) {
                            'Terverifikasi'     => '<span class="badge" style="background:#d1fae5;color:#065f46;">✓ Terverifikasi</span>',
                            'Menunggu Verifikasi'=> '<span class="badge" style="background:#fef3c7;color:#92400e;">⏳ Menunggu</span>',
                            'Ditolak'           => '<span class="badge" style="background:#fee2e2;color:#991b1b;">✗ Ditolak</span>',
                            default             => '<span class="badge" style="background:#f3f4f6;color:#6b7280;">— Belum Upload</span>',
                        };

                        // Hasil seleksi badge
                        $hasil = $row['status_hasil'];
                        $hasilBadge = match($hasil) {
                            'Diterima'      => '<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Diterima</span>',
                            'Tidak Diterima'=> '<span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Tidak Diterima</span>',
                            default         => '<span class="badge" style="background:#f3f4f6;color:#9ca3af;">Menunggu</span>',
                        };

                        // Progress
                        $progress = 25;
                        if ($row['nik']) $progress = 50;
                        if ($sv === 'Menunggu Verifikasi') $progress = 65;
                        if ($sv === 'Terverifikasi') $progress = 80;
                        if ($hasil !== 'Menunggu') $progress = 100;
                    ?>
                    <tr style="cursor:pointer;"
                        onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>)"
                        title="Klik untuk lihat detail">

                        <td class="ps-4 text-muted"><?= $no++ ?></td>

                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar" style="background:<?= $avatarColor ?>">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:14px;">
                                        <?= htmlspecialchars($row['fullname']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:12px;">
                                        <?= htmlspecialchars($row['email']) ?>
                                    </div>
                                    <div class="mini-progress mt-1" style="width:100px;">
                                        <div class="mini-progress-bar" style="width:<?= $progress ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:13px;"><?= htmlspecialchars($row['no_hp'] ?? '-') ?></div>
                            <div class="text-muted" style="font-size:11px;">
                                <?= $row['jenis_kelamin'] ?? '-' ?>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:13px;"><?= htmlspecialchars($row['asal_sekolah'] ?? '-') ?></div>
                            <?php if ($row['tempat_lahir']): ?>
                            <div class="text-muted" style="font-size:11px;">
                                <?= htmlspecialchars($row['tempat_lahir']) ?>
                                <?= $row['tanggal_lahir'] ? ', ' . date('d/m/Y', strtotime($row['tanggal_lahir'])) : '' ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span style="font-size:13px;font-weight:500;">
                                <?= htmlspecialchars($row['jurusan_pilihan'] ?? '-') ?>
                            </span>
                        </td>

                        <td><?= $svBadge ?></td>

                        <td><?= $hasilBadge ?></td>

                        <td class="text-muted" style="font-size:12px;">
                            <?= date('d/m/Y', strtotime($row['tgl_daftar'])) ?><br>
                            <span><?= date('H:i', strtotime($row['tgl_daftar'])) ?></span>
                        </td>

                        <td class="text-center pe-4" onclick="event.stopPropagation()">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li>
                                        <a class="dropdown-item"
                                           href="#"
                                           onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>); return false;">
                                            <i class="bi bi-eye me-2 text-primary"></i> Lihat Detail
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                           href="verifikasi_detail.php?id=<?= $row['id'] ?>">
                                            <i class="bi bi-file-earmark-check me-2 text-success"></i> Verifikasi Berkas
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                           href="pengumuman.php">
                                            <i class="bi bi-megaphone me-2 text-warning"></i> Atur Hasil Seleksi
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER TABLE -->
        <div class="px-4 py-2 border-top d-flex justify-content-between align-items-center bg-light">
            <small class="text-muted">
                Total <strong id="visibleCount"><?= count($rows) ?></strong> mahasiswa
                <?= ($search || $jurusan) ? 'ditemukan' : 'terdaftar' ?>
            </small>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Klik baris untuk melihat detail lengkap
            </small>
        </div>

    </div>

</div><!-- end main-content -->


<!-- ======================================================== -->
<!-- MODAL DETAIL MAHASISWA                                   -->
<!-- ======================================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0" id="modalHeaderBg" style="background:#f8f9fa;">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="avatar fs-4" id="modalAvatar" style="width:52px;height:52px;background:#4361ee;">A</div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-0" id="modalNama"></h5>
                        <small class="text-muted" id="modalEmail"></small>
                    </div>
                    <div id="modalHasilBadge"></div>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-3">
                <div class="row g-3">

                    <!-- Data Pribadi -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light rounded-3 p-3 h-100">
                            <h6 class="fw-bold mb-3 text-primary">
                                <i class="bi bi-person-circle me-2"></i>Data Pribadi
                            </h6>
                            <div class="detail-group">
                                <div class="detail-label">NIK</div>
                                <div class="detail-value" id="dNik">-</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">NISN</div>
                                <div class="detail-value" id="dNisn">-</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Tempat, Tanggal Lahir</div>
                                <div class="detail-value" id="dTtl">-</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Jenis Kelamin</div>
                                <div class="detail-value" id="dJk">-</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">No. HP</div>
                                <div class="detail-value" id="dHp">-</div>
                            </div>
                            <div class="detail-group mb-0">
                                <div class="detail-label">Alamat</div>
                                <div class="detail-value" id="dAlamat">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Pendaftaran -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                            <h6 class="fw-bold mb-3 text-success">
                                <i class="bi bi-mortarboard me-2"></i>Data Pendaftaran
                            </h6>
                            <div class="detail-group">
                                <div class="detail-label">Asal Sekolah</div>
                                <div class="detail-value" id="dSekolah">-</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Jurusan Pilihan</div>
                                <div class="detail-value" id="dJurusan">-</div>
                            </div>
                            <div class="detail-group mb-0">
                                <div class="detail-label">Tanggal Daftar</div>
                                <div class="detail-value" id="dTglDaftar">-</div>
                            </div>
                        </div>

                        <div class="card border-0 bg-light rounded-3 p-3">
                            <h6 class="fw-bold mb-3 text-warning">
                                <i class="bi bi-file-earmark-check me-2"></i>Status
                            </h6>
                            <div class="detail-group">
                                <div class="detail-label">Status Berkas</div>
                                <div id="dStatusBerkas">-</div>
                            </div>
                            <div class="detail-group mb-0">
                                <div class="detail-label">Hasil Seleksi</div>
                                <div id="dHasil">-</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-0 gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnVerifikasi" class="btn btn-success">
                    <i class="bi bi-file-earmark-check me-1"></i> Periksa Berkas
                </a>
                <a href="pengumuman.php" class="btn btn-primary">
                    <i class="bi bi-megaphone me-1"></i> Atur Hasil
                </a>
            </div>

        </div>
    </div>
</div>


<script>
// ============================================================
// SHOW DETAIL MODAL
// ============================================================
function showDetail(row) {

    // Avatar
    const colors = ['#4361ee','#7209b7','#f72585','#3a86ff','#06d6a0','#ef476f','#ffd166'];
    const name = row.fullname || '';
    const parts = name.trim().split(' ');
    let initials = parts[0][0] || '';
    if (parts.length > 1) initials += parts[parts.length-1][0];
    initials = initials.toUpperCase();

    let hash = 0;
    for (let c of name) hash = (hash * 31 + c.charCodeAt(0)) | 0;
    const color = colors[Math.abs(hash) % colors.length];

    document.getElementById('modalAvatar').textContent = initials;
    document.getElementById('modalAvatar').style.background = color;
    document.getElementById('modalNama').textContent  = row.fullname || '-';
    document.getElementById('modalEmail').textContent = row.email    || '-';

    // Data pribadi
    document.getElementById('dNik').textContent    = row.nik  || '-';
    document.getElementById('dNisn').textContent   = row.nisn || '-';
    const ttl = (row.tempat_lahir || '') + (row.tanggal_lahir ? ', ' + formatDate(row.tanggal_lahir) : '');
    document.getElementById('dTtl').textContent    = ttl || '-';
    document.getElementById('dJk').textContent     = row.jenis_kelamin || '-';
    document.getElementById('dHp').textContent     = row.no_hp  || '-';
    document.getElementById('dAlamat').textContent = row.alamat || '-';

    // Data pendaftaran
    document.getElementById('dSekolah').textContent   = row.asal_sekolah    || '-';
    document.getElementById('dJurusan').textContent   = row.jurusan_pilihan || '-';
    document.getElementById('dTglDaftar').textContent = formatDate(row.tgl_daftar) || '-';

    // Status berkas
    const svMap = {
        'Terverifikasi':      '<span class="badge" style="background:#d1fae5;color:#065f46;padding:6px 12px;">✓ Terverifikasi</span>',
        'Menunggu Verifikasi':'<span class="badge" style="background:#fef3c7;color:#92400e;padding:6px 12px;">⏳ Menunggu Verifikasi</span>',
        'Ditolak':            '<span class="badge" style="background:#fee2e2;color:#991b1b;padding:6px 12px;">✗ Ditolak</span>',
    };
    document.getElementById('dStatusBerkas').innerHTML = svMap[row.status_verifikasi]
        || '<span class="badge" style="background:#f3f4f6;color:#6b7280;padding:6px 12px;">— Belum Upload</span>';

    // Hasil seleksi
    const hasilMap = {
        'Diterima':      '<span class="badge bg-success px-3 py-2"><i class="bi bi-check-lg me-1"></i>Diterima</span>',
        'Tidak Diterima':'<span class="badge bg-danger px-3 py-2"><i class="bi bi-x-lg me-1"></i>Tidak Diterima</span>',
    };
    document.getElementById('dHasil').innerHTML = hasilMap[row.status_hasil]
        || '<span class="text-muted">Belum Diproses</span>';

    document.getElementById('modalHasilBadge').innerHTML = hasilMap[row.status_hasil] || '';

    // Button link
    document.getElementById('btnVerifikasi').href = 'verifikasi_detail.php?id=' + row.id;

    // Show modal
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}

function formatDate(str) {
    if (!str) return '';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'});
}

// ============================================================
// EXPORT CSV
// ============================================================
function exportCSV() {
    const table = document.getElementById('tableMahasiswa');
    const rows  = table.querySelectorAll('tr:not(.d-none)');
    let csv = [];

    // Header
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => headers.push('"' + th.textContent.trim() + '"'));
    csv.push(headers.join(','));

    // Body rows
    rows.forEach((row, i) => {
        if (i === 0) return; // skip header
        if (row.querySelector('.empty-state')) return;
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(cell => {
            let text = cell.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        if (rowData.length > 0) csv.push(rowData.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'data_mahasiswa_pmb_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}

// ============================================================
// PRINT
// ============================================================
function printTable() {
    window.print();
}

// Update visible count
document.addEventListener('DOMContentLoaded', function () {
    const count = document.querySelectorAll('#tableMahasiswa tbody tr:not([style*="display: none"])').length;
    const el = document.getElementById('rowCount');
    if (el) el.textContent = count + ' data';
});
</script>

<?php include '../../app/views/layouts/footer.php'; ?>