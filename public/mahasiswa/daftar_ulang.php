<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

// ============================================================
// CEK STATUS - HANYA MAHASISWA DITERIMA YANG BOLEH AKSES
// ============================================================
$bioQuery = mysqli_query($conn, "
    SELECT
        b.*,
        COALESCE(b.status_hasil, 'Menunggu') AS status_hasil,
        COALESCE(b.is_published, 0)           AS is_published
    FROM biodata_mahasiswa b
    WHERE b.user_id = '{$user['id']}'
");
$biodata = mysqli_fetch_assoc($bioQuery);


// ============================================================
// CEK APAKAH SUDAH DAFTAR ULANG
// ============================================================
$daftarUlangQuery = mysqli_query($conn, "
    SELECT * FROM daftar_ulang WHERE user_id = '{$user['id']}'
");
$existingDU = mysqli_fetch_assoc($daftarUlangQuery);

$message = '';
$messageType = '';
$step = $existingDU ? 'done' : 'form'; // 'form' | 'done'


    // =========================
    // AMBIL DATA BIAYA OTOMATIS
    // =========================

    $kelasSimple = 'Kelas Pagi';

    // default asrama
    $asrama = 0;

    // ambil dari data daftar ulang sebelumnya jika ada
    if ($existingDU) {

        $pilihan_kelas = $existingDU['pilihan_kelas'];
        $asrama = $existingDU['asrama'];

    } else {

        // default awal
        $pilihan_kelas = 'Kelas Pagi';
    }

    // normalisasi kelas
    if (strpos($pilihan_kelas, 'Siang') !== false) {

        $kelasSimple = 'Kelas Siang';

    } elseif (strpos($pilihan_kelas, 'Malam') !== false) {

        $kelasSimple = 'Kelas Malam';

    }

    // ambil jurusan mahasiswa
    $jurusan = $biodata['jurusan_pilihan'];

    // query biaya
    $biayaQuery = mysqli_query($conn, "
        SELECT *
        FROM biaya_kuliah
        WHERE jurusan = '$jurusan'
        AND kelas = '$kelasSimple'
    ");

    // hasil biaya
    $biaya = mysqli_fetch_assoc($biayaQuery);

    // fallback jika data biaya belum ada
    if (!$biaya) {

        $biaya = [
            'biaya_daftar_ulang' => 0,
            'biaya_spp' => 0,
            'biaya_asrama' => 0
        ];
    }

    // hitung total
    $totalPembayaran =
        $biaya['biaya_daftar_ulang']
        + $biaya['biaya_spp'];

    if ($asrama) {

        $totalPembayaran += $biaya['biaya_asrama'];

    }


// Redirect jika belum diterima
if (!$biodata || $biodata['status_hasil'] !== 'Diterima' || !$biodata['is_published']) {
    header("Location: pengumuman.php");
    exit;
}


// ============================================================
// HANDLE SUBMIT DAFTAR ULANG
// ============================================================
if (isset($_POST['submit_daftar_ulang']) && !$existingDU) {

    $nama_ortu = mysqli_real_escape_string($conn, $_POST['nama_ortu']);
    $pekerjaan_ortu = mysqli_real_escape_string($conn, $_POST['pekerjaan_ortu']);
    $no_hp_ortu = mysqli_real_escape_string($conn, $_POST['no_hp_ortu']);
    $penghasilan = mysqli_real_escape_string($conn, $_POST['penghasilan']);
    $pilihan_kelas = mysqli_real_escape_string($conn, $_POST['pilihan_kelas']);
    $asrama = isset($_POST['asrama']) ? 1 : 0;
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    // Validasi field wajib
    if (empty($nama_ortu) || empty($pekerjaan_ortu) || empty($no_hp_ortu) || empty($pilihan_kelas)) {
        $message = "Harap lengkapi semua field yang wajib diisi.";
        $messageType = 'danger';
    } else {
        // Cek tabel daftar_ulang, buat kalau belum ada
        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS `daftar_ulang` (
                `id`              int NOT NULL AUTO_INCREMENT,
                `user_id`         int NOT NULL,
                `nama_ortu`       varchar(100) NOT NULL,
                `pekerjaan_ortu`  varchar(100) NOT NULL,
                `no_hp_ortu`      varchar(20)  NOT NULL,
                `penghasilan`     varchar(50)  DEFAULT NULL,
                `pilihan_kelas`   varchar(50)  NOT NULL,
                `asrama`          tinyint(1)   DEFAULT '0',
                `catatan`         text,
                `status`          enum('Menunggu Konfirmasi','Terkonfirmasi','Dibatalkan') DEFAULT 'Menunggu Konfirmasi',
                `created_at`      timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                CONSTRAINT `daftar_ulang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $nomor_registrasi =
            'REG-' .
            date('Y') .
            '-' .
            strtoupper(substr(md5(time() . $user['id']), 0, 6));

        $uploadDir = "../uploads/pembayaran/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . $_FILES['bukti_pembayaran']['name'];
        $tmpName = $_FILES['bukti_pembayaran']['tmp_name'];

        move_uploaded_file($tmpName, $uploadDir . $fileName);

        $insert = mysqli_query($conn, "
            INSERT INTO daftar_ulang (
    user_id,
    nomor_registrasi,

    nama_ortu,
    pekerjaan_ortu,
    no_hp_ortu,
    penghasilan,

    pilihan_kelas,
    asrama,
    catatan,

    nama_bank,
    nama_pengirim,
    total_pembayaran,
    tanggal_pembayaran,

    bukti_pembayaran,

    status_pembayaran,
    status_daftar_ulang
)
            VALUES (
    '{$user['id']}',
    '$nomor_registrasi',

    '$nama_ortu',
    '$pekerjaan_ortu',
    '$no_hp_ortu',
    '$penghasilan',

    '$pilihan_kelas',
    '$asrama',
    '$catatan',

    '$nama_bank',
    '$nama_pengirim',
    '$totalPembayaran',
     " . ($tanggal_pembayaran ? "'$tanggal_pembayaran'" : "NULL") . ",

    '$fileName',

    'Menunggu Verifikasi',
    'Pending'
)");

        if ($insert) {
            // Re-fetch
            $daftarUlangQuery = mysqli_query($conn, "SELECT * FROM daftar_ulang WHERE user_id = '{$user['id']}'");
            $existingDU = mysqli_fetch_assoc($daftarUlangQuery);
            $step = 'done';
        } else {
            $message = "Terjadi kesalahan. Silakan coba lagi.";
            $messageType = 'danger';
        }
    }

    $biaya = mysqli_fetch_assoc($biayaQuery);



    $status = $existingDU['status_pembayaran'];

    if ($status == 'Terverifikasi') {
        echo '<span class="badge bg-success">Terverifikasi</span>';
    } elseif ($status == 'Ditolak') {
        echo '<span class="badge bg-danger">Ditolak</span>';
    } else {
        echo '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>';
    }
}

include '../../app/views/layouts/header.php';
?>

<style>
    /* ===== SIDEBAR ACTIVE ===== */
    .sidebar a.active-nav {
        background: rgba(255, 255, 255, 0.2);
    }

    /* ===== STEP INDICATOR ===== */
    .step-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 2rem;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .step-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        border: 2px solid #dee2e6;
        background: white;
        color: #adb5bd;
        position: relative;
        z-index: 1;
        transition: 0.3s;
    }

    .step-circle.done {
        background: #198754;
        border-color: #198754;
        color: white;
    }

    .step-circle.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, .15);
    }

    .step-label {
        font-size: 11px;
        font-weight: 600;
        color: #6c757d;
        margin-top: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .step-label.active {
        color: #0d6efd;
    }

    .step-label.done {
        color: #198754;
    }

    .step-line {
        height: 2px;
        width: 70px;
        background: #dee2e6;
        margin-top: -22px;
        transition: 0.3s;
    }

    .step-line.done {
        background: #198754;
    }

    /* ===== FORM STYLES ===== */
    .form-section {
        background: white;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .form-section h6 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f3f4f6;
    }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        border-color: #e5e7eb;
        font-size: 14px;
        padding: 10px 14px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, .1);
    }

    .required-star {
        color: #ef4444;
    }

    /* ===== KELAS CARD ===== */
    .kelas-card {
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        padding: 18px;
        cursor: pointer;
        transition: 0.2s;
        position: relative;
    }

    .kelas-card:hover {
        border-color: #0d6efd;
        background: #f8faff;
    }

    .kelas-card.selected {
        border-color: #0d6efd;
        background: #eff6ff;
    }

    .kelas-card .check-icon {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 22px;
        height: 22px;
        background: #0d6efd;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }

    .kelas-card.selected .check-icon {
        display: flex;
    }

    .kelas-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ===== SUCCESS STATE ===== */
    .success-card {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-radius: 24px;
        padding: 48px 32px;
        text-align: center;
        position: relative;
        overflow: hidden;
        border: none;
        box-shadow: 0 10px 40px rgba(16, 185, 129, .2);
    }

    .success-card::before {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(16, 185, 129, .1);
        top: -60px;
        right: -60px;
    }

    .success-icon {
        width: 90px;
        height: 90px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 8px 30px rgba(16, 185, 129, .4);
        animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    .receipt-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        max-width: 480px;
        margin: 0 auto;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        text-align: left;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }

    .receipt-row:last-child {
        border-bottom: none;
    }

    .receipt-label {
        color: #9ca3af;
        font-size: 12px;
        font-weight: 600;
    }

    .receipt-value {
        font-weight: 600;
        color: #1f2937;
        text-align: right;
        max-width: 200px;
    }

    /* ===== NOMOR PENDAFTARAN ===== */
    .nomor-daftar {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: 3px;
        color: #065f46;
        font-family: monospace;
    }

    @keyframes popIn {
        from {
            transform: scale(0);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* ===== INFO BANNER ===== */
    .info-banner {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-left: 4px solid #3b82f6;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    /* ===== ASRAMA TOGGLE ===== */
    .asrama-toggle {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 20px;
        cursor: pointer;
        transition: 0.2s;
    }

    .asrama-toggle:hover {
        border-color: #0d6efd;
    }

    .asrama-toggle.active {
        border-color: #0d6efd;
        background: #eff6ff;
    }
</style>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4 class="text-center fw-bold mb-4">PMB ONLINE</h4>
    <a href="dashboard.php"><i class="bi bi-grid me-2"></i> Dashboard</a>
    <a href="biodata.php"><i class="bi bi-person me-2"></i> Biodata</a>
    <a href="upload.php"><i class="bi bi-file-earmark-arrow-up me-2"></i> Upload Berkas</a>
    <a href="pengumuman.php"><i class="bi bi-megaphone me-2"></i> Pengumuman</a>
    <a href="daftar_ulang.php" class="active-nav"><i class="bi bi-clipboard-check me-2"></i> Daftar Ulang</a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
</div>

<div class="main-content">

    <!-- HEADER -->
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Daftar Ulang</h2>
        <p class="text-muted mb-0">Selamat! Anda diterima. Lengkapi formulir daftar ulang untuk mengkonfirmasi kehadiran
            Anda.</p>
    </div>

    <!-- STEP INDICATOR -->
    <div class="step-wrap">

        <div class="step-item">
            <div class="step-circle done"><i class="bi bi-check-lg"></i></div>
            <div class="step-label done">Registrasi</div>
        </div>
        <div class="step-line done"></div>

        <div class="step-item">
            <div class="step-circle done"><i class="bi bi-check-lg"></i></div>
            <div class="step-label done">Biodata</div>
        </div>
        <div class="step-line done"></div>

        <div class="step-item">
            <div class="step-circle done"><i class="bi bi-check-lg"></i></div>
            <div class="step-label done">Berkas</div>
        </div>
        <div class="step-line done"></div>

        <div class="step-item">
            <div class="step-circle done"><i class="bi bi-check-lg"></i></div>
            <div class="step-label done">Seleksi</div>
        </div>
        <div class="step-line done"></div>

        <div class="step-item">
            <div class="step-circle done"><i class="bi bi-check-lg"></i></div>
            <div class="step-label done">Pengumuman</div>
        </div>
        <div class="step-line <?= $step === 'done' ? 'done' : '' ?>"></div>

        <div class="step-item">
            <div class="step-circle <?= $step === 'done' ? 'done' : 'active' ?>">
                <?= $step === 'done' ? '<i class="bi bi-check-lg"></i>' : '6' ?>
            </div>
            <div class="step-label <?= $step === 'done' ? 'done' : 'active' ?>">Daftar Ulang</div>
        </div>

    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-<?= $messageType === 'danger' ? 'exclamation-triangle' : 'check-circle' ?>-fill me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>


    <!-- ============================================================ -->
    <!-- STATE: SUDAH DAFTAR ULANG                                   -->
    <!-- ============================================================ -->
    <?php if ($step === 'done'): ?>

        <?php
        // Buat nomor pendaftaran unik dari user_id + created_at
        $nomorDaftar = 'PMB-' . date('Y') . '-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);
        ?>

        <div class="success-card mb-4">
            <div class="success-icon">
                <i class="bi bi-check-lg text-white" style="font-size:2.5rem;"></i>
            </div>
            <div class="text-success fw-bold mb-2" style="letter-spacing:2px;font-size:13px;">PENDAFTARAN BERHASIL</div>
            <h3 class="fw-bold mb-2 text-success">Daftar Ulang Selesai! 🎉</h3>
            <p class="text-muted mb-4">
                Selamat <?= htmlspecialchars($user['fullname']) ?>! Proses daftar ulang Anda telah berhasil dikirim.
                Simpan nomor pendaftaran Anda di bawah ini.
            </p>

            <!-- Nomor Pendaftaran -->
            <div class="receipt-card mb-4">
                <div class="text-center mb-3">
                    <div style="font-size:12px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:1px;">
                        Nomor Pendaftaran
                    </div>
                    <div class="nomor-daftar"><?= $nomorDaftar ?></div>
                    <small class="text-muted">Simpan nomor ini untuk keperluan administrasi</small>
                </div>

                <hr>

                <div class="receipt-row">
                    <span class="receipt-label">Nama Lengkap</span>
                    <span class="receipt-value"><?= htmlspecialchars($user['fullname']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Jurusan Diterima</span>
                    <span class="receipt-value"><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Pilihan Kelas</span>
                    <span class="receipt-value"><?= htmlspecialchars($existingDU['pilihan_kelas'] ?? '-') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Asrama</span>
                    <span class="receipt-value">
                        <?= ($existingDU['asrama'] ?? 0) ? '✓ Mengambil Asrama' : '✗ Tidak Mengambil Asrama' ?>
                    </span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Nama Orang Tua</span>
                    <span class="receipt-value"><?= htmlspecialchars($existingDU['nama_ortu'] ?? '-') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Status</span>
                    <span class="receipt-value">
                        <?php
                        $statusDU = $existingDU['status_pembayaran'] ?? 'Menunggu Konfirmasi';
                        if ($statusDU === 'Terverifikasi') {
                            echo '<span class="badge bg-success px-3">✓ Terverifikasi</span>';
                        } elseif ($statusDU === 'Dibatalkan') {
                            echo '<span class="badge bg-danger px-3">✗ Dibatalkan</span>';
                        } else {
                            echo '<span class="badge bg-warning text-dark px-3">⏳ Menunggu Konfirmasi</span>';
                        }
                        ?>
                    </span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Tanggal Daftar Ulang</span>
                    <span class="receipt-value">
                        <?= date('d F Y, H:i', strtotime($existingDU['created_at'] ?? 'now')) ?> WIB
                    </span>
                </div>
            </div>

        </div>

        <!-- INFO SELANJUTNYA -->
        <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                Langkah Selanjutnya
            </h6>
            <div class="row g-3">

                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <div style="width:40px;height:40px;border-radius:12px;background:#eff6ff;
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-envelope text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:14px;">Cek Email</div>
                            <div class="text-muted" style="font-size:12px;">
                                Konfirmasi akan dikirim ke email Anda setelah admin memverifikasi.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <div style="width:40px;height:40px;border-radius:12px;background:#f0fdf4;
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-bank text-success"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:14px;">Pembayaran SPP</div>
                            <div class="text-muted" style="font-size:12px;">
                                Datang ke kampus untuk menyelesaikan proses pembayaran dan administrasi.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <div style="width:40px;height:40px;border-radius:12px;background:#fff7ed;
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-mortarboard text-warning"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:14px;">OSPEK / Orientasi</div>
                            <div class="text-muted" style="font-size:12px;">
                                Ikuti kegiatan orientasi mahasiswa baru untuk mengenal kampus.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-center mt-3">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-2"></i> Print Bukti Pendaftaran
            </button>
        </div>


        <!-- ============================================================ -->
        <!-- STATE: FORM DAFTAR ULANG                                    -->
        <!-- ============================================================ -->
    <?php else: ?>

        <!-- INFO BANNER -->
        <div class="info-banner">
            <div class="d-flex gap-3 align-items-start">
                <i class="bi bi-megaphone-fill text-primary fs-5 mt-1"></i>
                <div>
                    <div class="fw-bold" style="font-size:14px;">
                        Selamat! Anda dinyatakan <span class="text-success">DITERIMA</span>
                        di Program Studi <strong><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-') ?></strong>
                    </div>
                    <div class="text-muted" style="font-size:13px;margin-top:4px;">
                        <?php if ($biodata['catatan_hasil']): ?>
                            <?= nl2br(htmlspecialchars($biodata['catatan_hasil'])) ?>
                        <?php else: ?>
                            Harap lengkapi formulir daftar ulang di bawah ini sebelum batas waktu yang ditentukan.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formDaftarUlang">

            <!-- DATA ORANG TUA / WALI -->
            <div class="form-section">
                <h6 class="text-warning">
                    <i class="bi bi-people me-2"></i> Data Orang Tua / Wali
                </h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Nama Orang Tua / Wali <span class="required-star">*</span>
                        </label>
                        <input type="text" name="nama_ortu" class="form-control"
                            placeholder="Nama lengkap orang tua atau wali" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            No. HP Orang Tua / Wali <span class="required-star">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">+62</span>
                            <input type="tel" name="no_hp_ortu" class="form-control" placeholder="8xx xxxx xxxx" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Pekerjaan Orang Tua <span class="required-star">*</span>
                        </label>
                        <select name="pekerjaan_ortu" class="form-select" required>
                            <option value="">-- Pilih pekerjaan --</option>
                            <option>PNS / ASN</option>
                            <option>TNI / POLRI</option>
                            <option>Pegawai Swasta</option>
                            <option>Wiraswasta / Pengusaha</option>
                            <option>Petani / Nelayan</option>
                            <option>Pedagang</option>
                            <option>Buruh / Karyawan Harian</option>
                            <option>Tidak Bekerja</option>
                            <option>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Penghasilan Orang Tua per Bulan</label>
                        <select name="penghasilan" class="form-select">
                            <option value="">-- Pilih rentang penghasilan --</option>
                            <option>Kurang dari Rp 1.000.000</option>
                            <option>Rp 1.000.000 – Rp 3.000.000</option>
                            <option>Rp 3.000.000 – Rp 5.000.000</option>
                            <option>Rp 5.000.000 – Rp 10.000.000</option>
                            <option>Lebih dari Rp 10.000.000</option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- PILIHAN KELAS -->
            <div class="form-section">
                <h6 class="text-primary">
                    <i class="bi bi-grid me-2"></i> Pilihan Kelas
                    <span class="required-star">*</span>
                </h6>

                <input type="hidden" name="pilihan_kelas" id="pilihan_kelas" required>

                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="kelas-card" onclick="selectKelas(this, 'Kelas Pagi (07:00 – 12:00)')">
                            <div class="check-icon"><i class="bi bi-check-lg"></i></div>
                            <div class="mb-2">
                                <span class="kelas-badge" style="background:#dbeafe;color:#1d4ed8;">PAGI</span>
                            </div>
                            <div class="fw-bold" style="font-size:15px;">Kelas Pagi</div>
                            <div class="text-muted" style="font-size:13px;">07:00 – 12:00 WIB</div>
                            <div class="mt-2 text-muted" style="font-size:12px;">
                                <i class="bi bi-calendar-check me-1"></i> Senin – Jumat
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kelas-card" onclick="selectKelas(this, 'Kelas Siang (13:00 – 18:00)')">
                            <div class="check-icon"><i class="bi bi-check-lg"></i></div>
                            <div class="mb-2">
                                <span class="kelas-badge" style="background:#fef3c7;color:#92400e;">SIANG</span>
                            </div>
                            <div class="fw-bold" style="font-size:15px;">Kelas Siang</div>
                            <div class="text-muted" style="font-size:13px;">13:00 – 18:00 WIB</div>
                            <div class="mt-2 text-muted" style="font-size:12px;">
                                <i class="bi bi-calendar-check me-1"></i> Senin – Jumat
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="kelas-card" onclick="selectKelas(this, 'Kelas Malam (18:30 – 21:30)')">
                            <div class="check-icon"><i class="bi bi-check-lg"></i></div>
                            <div class="mb-2">
                                <span class="kelas-badge" style="background:#ede9fe;color:#5b21b6;">MALAM</span>
                            </div>
                            <div class="fw-bold" style="font-size:15px;">Kelas Malam</div>
                            <div class="text-muted" style="font-size:13px;">18:30 – 21:30 WIB</div>
                            <div class="mt-2 text-muted" style="font-size:12px;">
                                <i class="bi bi-calendar-check me-1"></i> Senin – Jumat
                            </div>
                        </div>
                    </div>

                </div>

                <div id="kelasError" class="text-danger small mt-2" style="display:none;">
                    <i class="bi bi-exclamation-circle me-1"></i> Harap pilih salah satu kelas.
                </div>

            </div>

            <!-- FASILITAS ASRAMA -->
            <div class="form-section">
                <h6 class="text-success">
                    <i class="bi bi-house me-2"></i> Fasilitas Asrama
                </h6>

                <div class="asrama-toggle d-flex align-items-center gap-3" id="asramaTgl" onclick="toggleAsrama()">
                    <div style="width:24px;height:24px;border:2px solid #d1d5db;border-radius:6px;
                                display:flex;align-items:center;justify-content:center;background:white;
                                transition:0.2s;" id="asramaCheck">
                    </div>
                    <div>
                        <div class="fw-semibold">Ya, saya ingin mengambil fasilitas asrama</div>
                        <div class="text-muted" style="font-size:13px;">
                            Fasilitas asrama tersedia untuk mahasiswa yang membutuhkan. Biaya asrama akan diinformasikan
                            lebih lanjut.
                        </div>
                    </div>
                    <input type="checkbox" name="asrama" id="asramaInput" style="display:none;" value="1">
                </div>

            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Pengirim</label>
                <select name="nama_bank" class="form-select" required>
                    <option value="">-- Pilih Bank --</option>
                    <option>BCA</option>
                    <option>BRI</option>
                    <option>BNI</option>
                    <option>MANDIRI</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nama Pengirim</label>
                <input type="text" name="nama_pengirim" class="form-control" required>
            </div>

            <div class="form-section">

                <h6 class="text-success">
                    <i class="bi bi-receipt me-2"></i>
                    Rincian Pembayaran
                </h6>

                <div class="receipt-card">

                    <div class="receipt-row">
                        <span>Biaya Daftar Ulang</span>
                        <strong>
                            Rp
                            <?= number_format($biaya['biaya_daftar_ulang']) ?>
                        </strong>
                    </div>

                    <div class="receipt-row">
                        <span>SPP Semester 1</span>
                        <strong>
                            Rp
                            <?= number_format($biaya['biaya_spp']) ?>
                        </strong>
                    </div>

                    <?php if ($asrama): ?>
                        <div class="receipt-row">
                            <span>Biaya Asrama</span>
                            <strong>
                                Rp
                                <?= number_format($biaya['biaya_asrama']) ?>
                            </strong>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="receipt-row">
                        <span class="fw-bold">TOTAL PEMBAYARAN</span>
                        <strong class="text-success fs-5">
                            Rp
                            <?= number_format($totalPembayaran) ?>
                        </strong>
                    </div>

                </div>

            </div>

            <div class="col-md-6">
                <label class="form-label">Tanggal Pembayaran</label>
                <input type="date" name="tanggal_pembayaran" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">Upload Bukti Transfer</label>

                <input type="file" name="bukti_pembayaran" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>


            <!-- CATATAN TAMBAHAN -->
            <div class="form-section">
                <h6 class="text-secondary">
                    <i class="bi bi-chat-left-text me-2"></i> Catatan Tambahan
                </h6>
                <div>
                    <label class="form-label">
                        Pesan / Catatan untuk Pihak Kampus
                        <span class="text-muted fw-normal">(opsional)</span>
                    </label>
                    <textarea name="catatan" class="form-control" rows="4"
                        placeholder="Contoh: Ada kebutuhan khusus, pertanyaan tentang jadwal, dll..."></textarea>
                </div>
            </div>

            <!-- PERNYATAAN -->
            <div class="form-section" style="background:#f9fafb;">
                <div class="d-flex gap-3">
                    <input type="checkbox" class="form-check-input mt-1" id="pernyataan" required
                        style="width:18px;height:18px;flex-shrink:0;">
                    <label class="form-check-label" for="pernyataan" style="font-size:14px;cursor:pointer;">
                        Saya menyatakan bahwa seluruh data yang diisi dalam formulir ini adalah
                        <strong>benar dan dapat dipertanggungjawabkan</strong>. Saya bersedia mengikuti
                        seluruh peraturan dan ketentuan yang berlaku di institusi ini.
                    </label>
                </div>
            </div>


            <!-- TOMBOL SUBMIT -->
            <div class="d-flex gap-3 justify-content-end">
                <a href="pengumuman.php" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-2"></i> Kembali
                </a>
                <button type="submit" name="submit_daftar_ulang" class="btn btn-success btn-lg px-5" id="btnSubmit">
                    <i class="bi bi-clipboard-check me-2"></i> Konfirmasi Daftar Ulang
                </button>
            </div>



        </form>

    <?php endif; ?>

</div><!-- end main-content -->


<script>
    // ===== PILIHAN KELAS =====
    function selectKelas(el, value) {
        document.querySelectorAll('.kelas-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('pilihan_kelas').value = value;
        document.getElementById('kelasError').style.display = 'none';
    }

    // ===== ASRAMA TOGGLE =====
    function toggleAsrama() {
        const input = document.getElementById('asramaInput');
        const check = document.getElementById('asramaCheck');
        const wrapper = document.getElementById('asramaTgl');

        input.checked = !input.checked;

        if (input.checked) {
            check.innerHTML = '<i class="bi bi-check-lg" style="color:#0d6efd;font-size:14px;"></i>';
            check.style.borderColor = '#0d6efd';
            check.style.background = '#eff6ff';
            wrapper.classList.add('active');
        } else {
            check.innerHTML = '';
            check.style.borderColor = '#d1d5db';
            check.style.background = 'white';
            wrapper.classList.remove('active');
        }
    }

    // ===== FORM VALIDATION =====
    document.getElementById('formDaftarUlang')?.addEventListener('submit', function (e) {
        const kelas = document.getElementById('pilihan_kelas').value;
        if (!kelas) {
            e.preventDefault();
            document.getElementById('kelasError').style.display = 'block';
            document.querySelector('.form-section:nth-child(3)').scrollIntoView({ behavior: 'smooth' });
            return;
        }

        // Konfirmasi submit
        const ok = confirm('Apakah Anda yakin ingin mengirim formulir daftar ulang?\n\nData tidak dapat diubah setelah dikirim.');
        if (!ok) e.preventDefault();
    });
</script>

<?php include '../../app/views/layouts/footer.php'; ?>