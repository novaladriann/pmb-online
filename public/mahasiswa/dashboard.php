<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

// Biodata
$biodata = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM biodata_mahasiswa WHERE user_id = '{$user['id']}'"
));

// Dokumen
$documents = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM documents WHERE user_id = '{$user['id']}'"
));

// Daftar ulang
$daftarUlang = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM daftar_ulang WHERE user_id = '{$user['id']}'"
));

// Hitung progress (0–100)
$progress     = 20; // registrasi selalu selesai
$stepBiodata  = false;
$stepDokumen  = false;
$stepVerified = false;
$stepHasil    = false;
$stepDaftar   = false;

if ($biodata && $biodata['nik']) {
    $stepBiodata = true;
    $progress    = 40;
}

$docStatus = $documents['status_verifikasi'] ?? 'Belum Upload';
if ($documents && $docStatus !== 'Belum Upload') {
    $stepDokumen = true;
    $progress    = 60;
}

if ($docStatus === 'Terverifikasi') {
    $stepVerified = true;
    $progress     = 75;
}

$hasilStatus = $biodata['status_hasil'] ?? 'Menunggu';
$isPublished = $biodata['is_published']  ?? 0;

if ($isPublished && $hasilStatus !== 'Menunggu') {
    $stepHasil = true;
    $progress  = 90;
}

if ($daftarUlang && $daftarUlang['status_daftar_ulang'] === 'Terverifikasi') {
    $stepDaftar = true;
    $progress   = 100;
}

// Inisial avatar
$nameParts = explode(' ', trim($user['fullname']));
$initials  = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

// Next action
function getNextAction($biodata, $docStatus, $isPublished, $hasilStatus, $daftarUlang) {
    if (!$biodata || !$biodata['nik'])
        return ['url' => 'biodata.php',     'label' => 'Lengkapi Biodata',      'icon' => 'bi-person-fill',            'color' => 'warning'];
    if ($docStatus === 'Belum Upload')
        return ['url' => 'upload.php',      'label' => 'Upload Berkas',          'icon' => 'bi-cloud-arrow-up-fill',    'color' => 'primary'];
    if ($docStatus === 'Ditolak')
        return ['url' => 'upload.php',      'label' => 'Upload Ulang Berkas',    'icon' => 'bi-arrow-repeat',           'color' => 'danger'];
    if ($docStatus === 'Menunggu Verifikasi')
        return ['url' => 'pengumuman.php',  'label' => 'Pantau Verifikasi',      'icon' => 'bi-hourglass-split',        'color' => 'info'];
    if (!$isPublished)
        return ['url' => 'pengumuman.php',  'label' => 'Tunggu Pengumuman',      'icon' => 'bi-bell',                   'color' => 'secondary'];
    if ($hasilStatus === 'Diterima' && (!$daftarUlang || $daftarUlang['status_daftar_ulang'] !== 'Dikonfirmasi'))
        return ['url' => 'daftar_ulang.php','label' => 'Selesaikan Daftar Ulang','icon' => 'bi-clipboard-check-fill',  'color' => 'success'];
    if ($hasilStatus === 'Tidak Diterima')
        return ['url' => 'pengumuman.php',  'label' => 'Lihat Pengumuman',       'icon' => 'bi-megaphone-fill',         'color' => 'danger'];
    return ['url' => 'pengumuman.php',      'label' => 'Lihat Status',           'icon' => 'bi-eye-fill',               'color' => 'primary'];
}

$nextAction = getNextAction($biodata, $docStatus, $isPublished, $hasilStatus, $daftarUlang);

include '../../app/views/layouts/header.php';
include '../../app/views/layouts/sidebar_mahasiswa.php';
?>

<style>
/* =========================================================
   FONTS
========================================================= */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap');

.main-content { font-family: 'Plus Jakarta Sans', sans-serif; }

/* =========================================================
   WELCOME BANNER
========================================================= */
.welcome-banner {
    background: linear-gradient(135deg, #0f2d5e 0%, #1a4b8c 45%, #1565c0 100%);
    border-radius: 24px;
    padding: 2rem 2.5rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.75rem;
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
}
.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -80px; right: 120px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.avatar-circle {
    width: 70px; height: 70px;
    background: rgba(255,255,255,0.18);
    border: 3px solid rgba(255,255,255,0.35);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 800;
    font-family: 'DM Serif Display', serif;
    flex-shrink: 0;
    backdrop-filter: blur(6px);
}
.welcome-greeting {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    opacity: 0.75;
    font-weight: 600;
    margin-bottom: 4px;
}
.welcome-name {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 6px;
}
.welcome-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 0.78rem;
    font-weight: 500;
    backdrop-filter: blur(4px);
}
.next-action-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: white;
    color: #1a4b8c;
    border: none;
    border-radius: 14px;
    padding: 10px 22px;
    font-weight: 700;
    font-size: 0.88rem;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    white-space: nowrap;
}
.next-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    color: #1a4b8c;
}

/* =========================================================
   PROGRESS STEPPER
========================================================= */
.stepper-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    margin-bottom: 1.75rem;
}
.stepper {
    display: flex;
    align-items: flex-start;
    position: relative;
    gap: 0;
}
.stepper-line {
    position: absolute;
    top: 20px;
    left: 24px;
    right: 24px;
    height: 3px;
    background: #e9ecef;
    z-index: 0;
    border-radius: 2px;
}
.stepper-line-fill {
    height: 100%;
    background: linear-gradient(90deg, #1565c0, #42a5f5);
    border-radius: 2px;
    transition: width 1s ease;
}
.step-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}
.step-dot {
    width: 42px; height: 42px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    border: 3px solid #e9ecef;
    background: white;
    color: #adb5bd;
    margin-bottom: 8px;
    transition: all 0.4s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.step-dot.done {
    background: #1565c0;
    border-color: #1565c0;
    color: white;
    box-shadow: 0 4px 14px rgba(21,101,192,0.35);
}
.step-dot.active {
    background: white;
    border-color: #1565c0;
    color: #1565c0;
    box-shadow: 0 0 0 5px rgba(21,101,192,0.12);
    animation: pulseDot 2s infinite;
}
.step-dot.rejected {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
    box-shadow: 0 4px 14px rgba(220,53,69,0.3);
}
.step-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-align: center;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    max-width: 70px;
}
.step-label.done   { color: #1565c0; }
.step-label.active { color: #1565c0; }
.step-label.rejected { color: #dc3545; }

@keyframes pulseDot {
    0%, 100% { box-shadow: 0 0 0 5px rgba(21,101,192,0.12); }
    50%       { box-shadow: 0 0 0 10px rgba(21,101,192,0.05); }
}

/* =========================================================
   STATUS CARDS
========================================================= */
.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.75rem;
}
.status-card {
    background: white;
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}
.status-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.1);
    color: inherit;
}
.status-card.done   { border-color: #d1fae5; }
.status-card.warn   { border-color: #fef3c7; }
.status-card.error  { border-color: #fee2e2; }
.status-card.info   { border-color: #dbeafe; }
.status-card.muted  { border-color: #f3f4f6; }
.sc-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.sc-icon.green  { background: #d1fae5; color: #065f46; }
.sc-icon.yellow { background: #fef3c7; color: #92400e; }
.sc-icon.red    { background: #fee2e2; color: #991b1b; }
.sc-icon.blue   { background: #dbeafe; color: #1e40af; }
.sc-icon.gray   { background: #f3f4f6; color: #6b7280; }
.sc-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    font-weight: 600;
    color: #9ca3af;
    margin-bottom: 2px;
}
.sc-value {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.3;
}

/* =========================================================
   DOCUMENT CHECKLIST
========================================================= */
.doc-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
.doc-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    border: 1.5px solid #e9ecef;
    transition: 0.2s;
}
.doc-item.uploaded { border-color: #a7f3d0; background: #f0fdf4; }
.doc-item.missing  { border-color: #fecaca; background: #fff5f5; }
.doc-check {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.doc-check.ok  { background: #d1fae5; color: #059669; }
.doc-check.no  { background: #fee2e2; color: #dc2626; }

/* =========================================================
   INFO CARD
========================================================= */
.info-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    height: 100%;
}
.info-card h6 {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    color: #9ca3af;
    margin-bottom: 1rem;
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
    gap: 1rem;
}
.info-row:last-child { border-bottom: none; }
.info-row .lbl { font-size: 0.82rem; color: #9ca3af; font-weight: 500; white-space: nowrap; }
.info-row .val { font-size: 0.88rem; font-weight: 600; color: #1f2937; text-align: right; }

/* =========================================================
   ALERT HASIL
========================================================= */
.banner-diterima {
    background: linear-gradient(135deg, #059669, #10b981);
    color: white;
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 6px 20px rgba(5,150,105,0.25);
    animation: slideDown 0.5s ease;
}
.banner-tidak {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 6px 20px rgba(220,38,38,0.22);
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 576px) {
    .welcome-banner  { padding: 1.5rem; }
    .welcome-name    { font-size: 1.3rem; }
    .doc-grid        { grid-template-columns: 1fr; }
    .step-label      { font-size: 0.62rem; max-width: 55px; }
    .step-dot        { width: 34px; height: 34px; font-size: 0.85rem; }
    .next-action-btn { display: none; }
}
</style>

<div class="main-content">

    <!-- =========================================================
         WELCOME BANNER
    ========================================================= -->
    <div class="welcome-banner">
        <div class="d-flex align-items-center gap-3 flex-wrap">

            <div class="avatar-circle"><?= $initials; ?></div>

            <div class="flex-grow-1">
                <div class="welcome-greeting">Dashboard Mahasiswa</div>
                <div class="welcome-name">
                    <?= htmlspecialchars($user['fullname']); ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php if ($biodata && $biodata['jurusan_pilihan']): ?>
                    <span class="welcome-chip">
                        <i class="bi bi-mortarboard-fill"></i>
                        <?= htmlspecialchars($biodata['jurusan_pilihan']); ?>
                    </span>
                    <?php endif; ?>
                    <span class="welcome-chip">
                        <i class="bi bi-calendar3"></i>
                        Daftar <?= date('d M Y', strtotime($user['created_at'])); ?>
                    </span>
                </div>
            </div>

            <!-- Tombol Aksi Berikutnya -->
            <a href="<?= $nextAction['url']; ?>" class="next-action-btn">
                <i class="bi <?= $nextAction['icon']; ?>"></i>
                <?= $nextAction['label']; ?>
            </a>

        </div>

        <!-- Progress Bar tipis di bawah banner -->
        <div style="margin-top:1.25rem;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span style="font-size:0.75rem;opacity:0.75;font-weight:600;">Progress Pendaftaran</span>
                <span style="font-size:0.75rem;font-weight:700;"><?= $progress; ?>%</span>
            </div>
            <div style="background:rgba(255,255,255,0.2);border-radius:10px;height:8px;overflow:hidden;">
                <div id="bannerProgressBar"
                     style="height:100%;background:white;border-radius:10px;width:0%;transition:width 1.2s ease;">
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         BANNER HASIL (jika sudah diumumkan)
    ========================================================= -->
    <?php if ($isPublished && $hasilStatus === 'Diterima'): ?>
    <div class="banner-diterima">
        <div style="font-size:2rem;">🎉</div>
        <div>
            <div class="fw-bold fs-6">Selamat! Anda Diterima</div>
            <div style="font-size:0.85rem;opacity:0.9;">
                Anda diterima di <strong><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-'); ?></strong>.
                Segera selesaikan daftar ulang!
            </div>
        </div>
        <a href="daftar_ulang.php" class="btn btn-light btn-sm ms-auto fw-bold" style="white-space:nowrap;">
            Daftar Ulang →
        </a>
    </div>
    <?php elseif ($isPublished && $hasilStatus === 'Tidak Diterima'): ?>
    <div class="banner-tidak">
        <div style="font-size:2rem;">📋</div>
        <div>
            <div class="fw-bold fs-6">Pengumuman Telah Keluar</div>
            <div style="font-size:0.85rem;opacity:0.9;">Lihat detail hasil seleksi Anda di halaman Pengumuman.</div>
        </div>
        <a href="pengumuman.php" class="btn btn-light btn-sm ms-auto fw-bold" style="white-space:nowrap;">
            Lihat →
        </a>
    </div>
    <?php endif; ?>

    <!-- =========================================================
         PROGRESS STEPPER
    ========================================================= -->
    <div class="stepper-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:#9ca3af;">
                Tahapan Pendaftaran
            </div>
            <div style="font-size:0.8rem;font-weight:700;color:#1565c0;"><?= $progress; ?>% Selesai</div>
        </div>

        <div class="stepper">
            <!-- Garis background -->
            <div class="stepper-line">
                <div class="stepper-line-fill" id="stepperFill" style="width:0%;"></div>
            </div>

            <?php
            // Definisi setiap step
            $steps = [
                ['label' => 'Registrasi', 'state' => 'done',
                 'icon'  => '<i class="bi bi-check-lg"></i>'],

                ['label' => 'Biodata',    'state' => $stepBiodata ? 'done' : 'active',
                 'icon'  => $stepBiodata ? '<i class="bi bi-check-lg"></i>' : '2'],

                ['label' => 'Berkas',     'state' => $stepVerified ? 'done' : ($stepDokumen ? ($docStatus == 'Ditolak' ? 'rejected' : 'active') : ($stepBiodata ? 'active' : 'pending')),
                 'icon'  => $stepVerified ? '<i class="bi bi-check-lg"></i>' : ($docStatus == 'Ditolak' ? '<i class="bi bi-x-lg"></i>' : '3')],

                ['label' => 'Seleksi',    'state' => $stepHasil ? 'done' : ($stepVerified ? 'active' : 'pending'),
                 'icon'  => $stepHasil ? '<i class="bi bi-check-lg"></i>' : '4'],

                ['label' => 'Selesai',    'state' => $stepDaftar ? 'done' : ($stepHasil && $hasilStatus == 'Diterima' ? 'active' : 'pending'),
                 'icon'  => $stepDaftar ? '<i class="bi bi-check-lg"></i>' : '5'],
            ];

            foreach ($steps as $step):
                $dotClass   = $step['state'] === 'pending' ? '' : $step['state'];
                $labelClass = $step['state'] === 'pending' ? '' : $step['state'];
            ?>
            <div class="step-item">
                <div class="step-dot <?= $dotClass; ?>"><?= $step['icon']; ?></div>
                <div class="step-label <?= $labelClass; ?>"><?= $step['label']; ?></div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- =========================================================
         STATUS CARDS + DOC CHECKLIST
    ========================================================= -->
    <div class="row g-4 mb-4">

        <!-- Status Cards -->
        <div class="col-lg-8">
            <div class="status-grid">

                <!-- Biodata -->
                <?php
                if ($stepBiodata) {
                    $scClass = 'done'; $icClass = 'green';
                    $scIcon  = 'bi-person-check-fill'; $scVal = 'Lengkap';
                } else {
                    $scClass = 'warn'; $icClass = 'yellow';
                    $scIcon  = 'bi-person-exclamation'; $scVal = 'Belum Diisi';
                }
                ?>
                <a href="biodata.php" class="status-card <?= $scClass; ?>">
                    <div class="sc-icon <?= $icClass; ?>">
                        <i class="bi <?= $scIcon; ?>"></i>
                    </div>
                    <div>
                        <div class="sc-label">Biodata</div>
                        <div class="sc-value"><?= $scVal; ?></div>
                    </div>
                </a>

                <!-- Berkas -->
                <?php
                if ($docStatus === 'Terverifikasi') {
                    $scClass = 'done'; $icClass = 'green';
                    $scIcon  = 'bi-folder-check'; $scVal = 'Terverifikasi';
                } elseif ($docStatus === 'Menunggu Verifikasi') {
                    $scClass = 'info'; $icClass = 'blue';
                    $scIcon  = 'bi-folder-symlink'; $scVal = 'Menunggu Verifikasi';
                } elseif ($docStatus === 'Ditolak') {
                    $scClass = 'error'; $icClass = 'red';
                    $scIcon  = 'bi-folder-x'; $scVal = 'Ditolak — Upload Ulang';
                } else {
                    $scClass = 'warn'; $icClass = 'yellow';
                    $scIcon  = 'bi-folder2-open'; $scVal = 'Belum Diupload';
                }
                ?>
                <a href="upload.php" class="status-card <?= $scClass; ?>">
                    <div class="sc-icon <?= $icClass; ?>">
                        <i class="bi <?= $scIcon; ?>"></i>
                    </div>
                    <div>
                        <div class="sc-label">Berkas Dokumen</div>
                        <div class="sc-value"><?= $scVal; ?></div>
                    </div>
                </a>

                <!-- Seleksi -->
                <?php
                if ($hasilStatus === 'Diterima') {
                    $scClass = 'done'; $icClass = 'green';
                    $scIcon  = 'bi-patch-check-fill'; $scVal = 'Diterima ✓';
                } elseif ($hasilStatus === 'Tidak Diterima') {
                    $scClass = 'error'; $icClass = 'red';
                    $scIcon  = 'bi-patch-minus-fill'; $scVal = 'Tidak Diterima';
                } else {
                    $scClass = 'muted'; $icClass = 'gray';
                    $scIcon  = 'bi-clipboard2-pulse'; $scVal = 'Belum Diumumkan';
                }
                ?>
                <a href="pengumuman.php" class="status-card <?= $scClass; ?>">
                    <div class="sc-icon <?= $icClass; ?>">
                        <i class="bi <?= $scIcon; ?>"></i>
                    </div>
                    <div>
                        <div class="sc-label">Hasil Seleksi</div>
                        <div class="sc-value"><?= $scVal; ?></div>
                    </div>
                </a>

                <!-- Daftar Ulang -->
                <?php
                if ($daftarUlang && $daftarUlang['status_daftar_ulang'] === 'Dikonfirmasi') {
                    $scClass = 'done'; $icClass = 'green';
                    $scIcon  = 'bi-clipboard2-check-fill'; $scVal = 'Selesai';
                } elseif ($daftarUlang && $daftarUlang['status_daftar_ulang'] === 'Menunggu Konfirmasi') {
                    $scClass = 'info'; $icClass = 'blue';
                    $scIcon  = 'bi-clipboard2-pulse-fill'; $scVal = 'Menunggu Konfirmasi';
                } elseif ($hasilStatus === 'Diterima' && $isPublished) {
                    $scClass = 'warn'; $icClass = 'yellow';
                    $scIcon  = 'bi-clipboard2-fill'; $scVal = 'Perlu Daftar Ulang';
                } else {
                    $scClass = 'muted'; $icClass = 'gray';
                    $scIcon  = 'bi-clipboard2'; $scVal = 'Belum Tersedia';
                }
                ?>
                <a href="<?= $hasilStatus === 'Diterima' && $isPublished ? 'daftar_ulang.php' : '#'; ?>"
                   class="status-card <?= $scClass; ?>">
                    <div class="sc-icon <?= $icClass; ?>">
                        <i class="bi <?= $scIcon; ?>"></i>
                    </div>
                    <div>
                        <div class="sc-label">Daftar Ulang</div>
                        <div class="sc-value"><?= $scVal; ?></div>
                    </div>
                </a>

            </div>
        </div>

        <!-- Dokumen Checklist -->
        <div class="col-lg-4">
            <div class="info-card">
                <h6><i class="bi bi-folder2 me-2"></i>Kelengkapan Dokumen</h6>
                <div class="doc-grid">
                    <?php
                    $docs = [
                        ['key' => 'foto',   'label' => 'Foto Diri',   'icon' => 'bi-person-square'],
                        ['key' => 'ktp',    'label' => 'KTP / KK',    'icon' => 'bi-credit-card'],
                        ['key' => 'ijazah', 'label' => 'Ijazah',      'icon' => 'bi-award'],
                        ['key' => 'rapor',  'label' => 'Rapor',       'icon' => 'bi-journal-text'],
                    ];
                    foreach ($docs as $doc):
                        $uploaded = !empty($documents[$doc['key']]);
                    ?>
                    <div class="doc-item <?= $uploaded ? 'uploaded' : 'missing'; ?>">
                        <div class="doc-check <?= $uploaded ? 'ok' : 'no'; ?>">
                            <i class="bi <?= $uploaded ? 'bi-check-lg' : 'bi-dash'; ?>"></i>
                        </div>
                        <div>
                            <div style="font-size:0.78rem;font-weight:600;color:#374151;">
                                <?= $doc['label']; ?>
                            </div>
                            <div style="font-size:0.68rem;color:#9ca3af;">
                                <?= $uploaded ? 'Terupload' : 'Belum'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($documents && $documents['catatan']): ?>
                <div class="mt-3 p-2 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                    <div style="font-size:0.72rem;font-weight:700;color:#92400e;margin-bottom:3px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Catatan Admin
                    </div>
                    <div style="font-size:0.78rem;color:#78350f;">
                        <?= nl2br(htmlspecialchars($documents['catatan'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="upload.php" class="btn btn-primary btn-sm w-100 mt-3 rounded-pill fw-600">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Kelola Berkas
                </a>
            </div>
        </div>

    </div>

    <!-- =========================================================
         INFO BIODATA (jika sudah ada)
    ========================================================= -->
    <?php if ($biodata && $biodata['nik']): ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="info-card">
                <h6><i class="bi bi-person-circle me-2"></i>Data Pribadi</h6>
                <div class="info-row">
                    <span class="lbl">NIK</span>
                    <span class="val"><?= htmlspecialchars($biodata['nik']); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">NISN</span>
                    <span class="val"><?= htmlspecialchars($biodata['nisn'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Tgl. Lahir</span>
                    <span class="val">
                        <?= $biodata['tanggal_lahir'] ? date('d M Y', strtotime($biodata['tanggal_lahir'])) : '-'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="lbl">No. HP</span>
                    <span class="val"><?= htmlspecialchars($biodata['no_hp'] ?? '-'); ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-card">
                <h6><i class="bi bi-mortarboard me-2"></i>Data Pendaftaran</h6>
                <div class="info-row">
                    <span class="lbl">Asal Sekolah</span>
                    <span class="val"><?= htmlspecialchars($biodata['asal_sekolah'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Jurusan Pilihan</span>
                    <span class="val"><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Jenis Kelamin</span>
                    <span class="val"><?= htmlspecialchars($biodata['jenis_kelamin'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Status</span>
                    <span class="val">
                        <?php
                        $sp = $biodata['status_pendaftaran'];
                        if ($sp == 'Terverifikasi')
                            echo '<span style="color:#059669;font-weight:700;">✓ Terverifikasi</span>';
                        elseif ($sp == 'Menunggu Verifikasi')
                            echo '<span style="color:#d97706;font-weight:700;">⏳ Menunggu</span>';
                        else
                            echo '<span style="color:#6b7280;">' . htmlspecialchars($sp) . '</span>';
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- end main-content -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const progress = <?= $progress; ?>;

    // Animasi progress bar di banner
    setTimeout(() => {
        document.getElementById('bannerProgressBar').style.width = progress + '%';
    }, 300);

    // Animasi stepper fill
    // Hitung posisi fill berdasarkan step yang done
    const stepsDone = <?= array_sum([(int)true, (int)$stepBiodata, (int)$stepVerified, (int)$stepHasil, (int)$stepDaftar]); ?>;
    const totalSteps = 5;
    const fillPercent = stepsDone === 1 ? 0 : Math.min(((stepsDone - 1) / (totalSteps - 1)) * 100, 100);

    setTimeout(() => {
        document.getElementById('stepperFill').style.width = fillPercent + '%';
    }, 400);
});
</script>

<?php include '../../app/views/layouts/footer.php'; ?>