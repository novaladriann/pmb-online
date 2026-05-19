<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

// Ambil data biodata + hasil seleksi
$query = mysqli_query($conn, "
    SELECT
        b.status_pendaftaran,
        b.jurusan_pilihan,
        b.asal_sekolah,
        COALESCE(b.status_hasil, 'Menunggu')  AS status_hasil,
        b.catatan_hasil,
        COALESCE(b.is_published, 0)            AS is_published
    FROM biodata_mahasiswa b
    WHERE b.user_id = '{$user['id']}'
");

$biodata = mysqli_fetch_assoc($query);

// Cek status dokumen
$docQuery = mysqli_query($conn, "
    SELECT status_verifikasi
    FROM documents
    WHERE user_id = '{$user['id']}'
");
$doc = mysqli_fetch_assoc($docQuery);

include '../../app/views/layouts/header.php';
?>

<style>

/* Hasil cards */
.result-wrapper {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Diterima */
.card-diterima {
    border: none;
    border-radius: 24px;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    box-shadow: 0 10px 40px rgba(25, 135, 84, 0.15);
    overflow: hidden;
    position: relative;
}
.card-diterima::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    background: rgba(25,135,84,0.08);
    border-radius: 50%;
}
.card-diterima::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -30px;
    width: 220px; height: 220px;
    background: rgba(25,135,84,0.05);
    border-radius: 50%;
}
.icon-diterima {
    width: 100px; height: 100px;
    background: #198754;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
    box-shadow: 0 8px 25px rgba(25,135,84,0.35);
    animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
}

/* Tidak Diterima */
.card-tidak {
    border: none;
    border-radius: 24px;
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    box-shadow: 0 10px 40px rgba(220, 53, 69, 0.12);
}
.icon-tidak {
    width: 100px; height: 100px;
    background: #dc3545;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
    box-shadow: 0 8px 25px rgba(220,53,69,0.3);
    animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
}

/* Menunggu */
.card-menunggu {
    border: none;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}
.icon-menunggu {
    width: 100px; height: 100px;
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
    animation: pulse 2s infinite;
}

/* Belum lengkap */
.card-belum {
    border: none;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

/* Timeline progress */
.timeline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin: 2rem 0;
    flex-wrap: wrap;
}
.tl-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    min-width: 100px;
}
.tl-circle {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: bold; font-size: 14px;
    position: relative; z-index: 1;
}
.tl-line {
    height: 3px;
    width: 60px;
    background: #dee2e6;
    margin-top: -20px;
}
.tl-line.done { background: #198754; }
.tl-label {
    font-size: 11px;
    margin-top: 8px;
    color: #6c757d;
    font-weight: 500;
}
.tl-circle.done { background: #198754; color: white; }
.tl-circle.active { background: #0d6efd; color: white; box-shadow: 0 0 0 4px rgba(13,110,253,0.2); }
.tl-circle.pending { background: #e9ecef; color: #adb5bd; }
.tl-circle.rejected { background: #dc3545; color: white; }

@keyframes popIn {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,193,7,0.4); }
    50%       { box-shadow: 0 0 0 15px rgba(255,193,7,0); }
}

/* Confetti dots dekoratif */
.confetti-dot {
    position: absolute;
    width: 10px; height: 10px;
    border-radius: 50%;
    opacity: 0.4;
}
</style>

<div class="sidebar">
    <h4 class="text-center fw-bold mb-4">PMB ONLINE</h4>
    <a href="dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="biodata.php"><i class="bi bi-person"></i> Biodata</a>
    <a href="upload.php"><i class="bi bi-file-earmark-arrow-up"></i> Upload Berkas</a>
    <a href="pengumuman.php" style="background:rgba(255,255,255,0.2);">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>
    <?php if ($biodata && ($biodata['status_hasil'] ?? '') === 'Diterima' && ($biodata['is_published'] ?? 0)): ?>
    <a href="daftar_ulang.php">
        <i class="bi bi-clipboard-check"></i> Daftar Ulang
    </a>
    <?php endif; ?>
    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">

    <h2 class="fw-bold mb-1">Pengumuman Hasil Seleksi</h2>
    <p class="text-muted mb-4">Status penerimaan Anda di PMB Online</p>

    <!-- ============================================================ -->
    <!-- KASUS 1: Belum isi biodata sama sekali -->
    <!-- ============================================================ -->
    <?php if (!$biodata): ?>

    <div class="result-wrapper">
        <div class="card card-belum p-5 text-center" style="max-width:500px;width:100%;">
            <div class="icon-menunggu mx-auto mb-4">
                <i class="bi bi-person-fill text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-2">Biodata Belum Diisi</h4>
            <p class="text-muted mb-4">
                Anda belum melengkapi biodata pendaftaran. Lengkapi biodata terlebih dahulu
                untuk dapat mengikuti proses seleksi.
            </p>
            <a href="biodata.php" class="btn btn-primary btn-lg rounded-pill px-4">
                <i class="bi bi-person me-2"></i> Isi Biodata Sekarang
            </a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KASUS 2: Dokumen belum diupload / ditolak -->
    <!-- ============================================================ -->
    <?php elseif (!$doc || $doc['status_verifikasi'] == 'Belum Upload'): ?>

    <div class="result-wrapper">
        <div class="card card-belum p-5 text-center" style="max-width:520px;width:100%;">
            <div class="icon-menunggu mx-auto mb-4">
                <i class="bi bi-cloud-upload text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-2">Dokumen Belum Diupload</h4>
            <p class="text-muted mb-4">
                Upload dokumen persyaratan (Foto, KTP, Ijazah, Rapor) untuk melanjutkan
                proses pendaftaran Anda.
            </p>
            <a href="upload.php" class="btn btn-primary btn-lg rounded-pill px-4">
                <i class="bi bi-cloud-upload me-2"></i> Upload Dokumen
            </a>
        </div>
    </div>

    <?php elseif ($doc['status_verifikasi'] == 'Ditolak'): ?>

    <div class="result-wrapper">
        <div class="card card-tidak p-5 text-center" style="max-width:520px;width:100%;">
            <div class="icon-tidak mx-auto">
                <i class="bi bi-x-lg text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-2 text-danger">Dokumen Ditolak</h4>
            <p class="text-muted mb-4">
                Dokumen Anda ditolak oleh tim verifikasi. Silakan upload ulang dokumen
                yang benar dan lengkap.
            </p>
            <a href="upload.php" class="btn btn-danger btn-lg rounded-pill px-4">
                <i class="bi bi-arrow-repeat me-2"></i> Upload Ulang
            </a>
        </div>
    </div>

    <?php elseif ($doc['status_verifikasi'] == 'Menunggu Verifikasi'): ?>

    <!-- ============================================================ -->
    <!-- KASUS 3: Menunggu verifikasi dokumen -->
    <!-- ============================================================ -->
    <div class="result-wrapper">
        <div class="card card-menunggu p-5 text-center" style="max-width:520px;width:100%;">
            <div class="icon-menunggu mx-auto mb-4">
                <i class="bi bi-hourglass-split text-white fs-2"></i>
            </div>
            <h4 class="fw-bold mb-2">Dokumen Sedang Diverifikasi</h4>
            <p class="text-muted mb-4">
                Dokumen Anda sedang dalam proses verifikasi oleh tim admin.
                Harap bersabar dan pantau halaman ini secara berkala.
            </p>
            <?php include 'inc_timeline.php'; ?>
        </div>
    </div>

    <?php else: /* status_verifikasi == Terverifikasi */ ?>

        <?php if (!$biodata['is_published']): ?>

        <!-- ============================================================ -->
        <!-- KASUS 4: Dokumen terverifikasi, hasil belum dipublikasi -->
        <!-- ============================================================ -->
        <div class="result-wrapper">
            <div class="card card-menunggu p-5 text-center" style="max-width:520px;width:100%;">
                <div class="icon-menunggu mx-auto mb-4">
                    <i class="bi bi-megaphone text-white fs-2"></i>
                </div>
                <h4 class="fw-bold mb-2">Dokumen Terverifikasi ✓</h4>
                <p class="text-muted mb-4">
                    Dokumen Anda telah <strong class="text-success">berhasil diverifikasi</strong>.
                    Hasil seleksi penerimaan akan segera diumumkan oleh admin. Pantau terus halaman ini!
                </p>
                <div class="alert alert-info py-2">
                    <i class="bi bi-bell me-1"></i> Pengumuman belum dipublikasikan
                </div>
            </div>
        </div>

        <?php elseif ($biodata['status_hasil'] == 'Diterima'): ?>

        <!-- ============================================================ -->
        <!-- KASUS 5: DITERIMA 🎉 -->
        <!-- ============================================================ -->
        <div class="result-wrapper">
            <div class="card card-diterima p-5 text-center" style="max-width:580px;width:100%;position:relative;">

                <div class="icon-diterima position-relative" style="z-index:2;">
                    <i class="bi bi-check-lg text-white" style="font-size:2.5rem;"></i>
                </div>

                <div class="position-relative" style="z-index:2;">
                    <div class="text-success fw-bold mb-1" style="letter-spacing:2px;font-size:13px;">
                        SELAMAT!
                    </div>
                    <h3 class="fw-bold mb-1 text-success">Anda Diterima 🎉</h3>
                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($user['fullname']); ?>
                    </p>

                    <div class="card mt-4 mb-3 border-0 bg-white bg-opacity-75 rounded-3 p-3 text-start">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-muted small">Jurusan</div>
                                <div class="fw-semibold"><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-'); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Status</div>
                                <div class="fw-semibold text-success">
                                    <i class="bi bi-patch-check-fill me-1"></i> Diterima
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($biodata['catatan_hasil']): ?>
                    <div class="alert alert-success border-0 text-start mb-3">
                        <div class="fw-semibold mb-1"><i class="bi bi-chat-left-text me-1"></i> Pesan dari Admin:</div>
                        <?= nl2br(htmlspecialchars($biodata['catatan_hasil'])); ?>
                    </div>
                    <?php endif; ?>

                    <p class="text-muted small mb-4">
                        Segera lakukan <strong>Daftar Ulang</strong> sebelum batas waktu yang ditentukan.
                    </p>

                    <a href="daftar_ulang.php" class="btn btn-success btn-lg rounded-pill px-5 shadow-sm">
                        <i class="bi bi-arrow-right-circle me-2"></i> Lanjut Daftar Ulang
                    </a>
                </div>

            </div>
        </div>

        <?php elseif ($biodata['status_hasil'] == 'Tidak Diterima'): ?>

        <!-- ============================================================ -->
        <!-- KASUS 6: TIDAK DITERIMA -->
        <!-- ============================================================ -->
        <div class="result-wrapper">
            <div class="card card-tidak p-5 text-center" style="max-width:540px;width:100%;">

                <div class="icon-tidak">
                    <i class="bi bi-x-lg text-white" style="font-size:2rem;"></i>
                </div>

                <h3 class="fw-bold mb-2 text-danger mt-2">Tidak Diterima</h3>
                <p class="text-muted mb-0"><?= htmlspecialchars($user['fullname']); ?></p>

                <div class="card mt-4 mb-3 border-0 bg-white bg-opacity-75 rounded-3 p-3 text-start">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-muted small">Jurusan yang Dipilih</div>
                            <div class="fw-semibold"><?= htmlspecialchars($biodata['jurusan_pilihan'] ?? '-'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Status</div>
                            <div class="fw-semibold text-danger">
                                <i class="bi bi-x-circle-fill me-1"></i> Tidak Diterima
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($biodata['catatan_hasil']): ?>
                <div class="alert alert-light border text-start mb-3">
                    <div class="fw-semibold mb-1"><i class="bi bi-chat-left-text me-1"></i> Pesan dari Admin:</div>
                    <?= nl2br(htmlspecialchars($biodata['catatan_hasil'])); ?>
                </div>
                <?php endif; ?>

                <p class="text-muted small mb-0">
                    Terima kasih atas partisipasi Anda dalam proses seleksi PMB Online.
                    Jangan menyerah — teruslah berjuang untuk masa depan Anda! 💪
                </p>

            </div>
        </div>

        <?php else: ?>

        <!-- ============================================================ -->
        <!-- KASUS 7: Dokumen terverifikasi, hasil masih Menunggu (published tapi belum di-set) -->
        <!-- ============================================================ -->
        <div class="result-wrapper">
            <div class="card card-menunggu p-5 text-center" style="max-width:520px;width:100%;">
                <div class="icon-menunggu mx-auto mb-4">
                    <i class="bi bi-clock text-white fs-2"></i>
                </div>
                <h4 class="fw-bold mb-2">Menunggu Keputusan</h4>
                <p class="text-muted mb-0">
                    Dokumen Anda telah terverifikasi. Hasil seleksi sedang diproses oleh admin.
                    Pantau terus halaman ini!
                </p>
            </div>
        </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- TIMELINE PROGRESS (selalu tampil di bawah) -->
    <?php if ($biodata): ?>
    <div class="card card-modern p-4 mt-4">
        <h6 class="fw-bold mb-3 text-muted">
            <i class="bi bi-signpost-split me-2"></i>Progress Pendaftaran
        </h6>
        <div class="timeline">

            <!-- Step 1: Registrasi -->
            <div class="tl-step">
                <div class="tl-circle done">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="tl-label">Registrasi</div>
            </div>

            <div class="tl-line done"></div>

            <!-- Step 2: Biodata -->
            <div class="tl-step">
                <div class="tl-circle <?= $biodata ? 'done' : 'pending'; ?>">
                    <?= $biodata ? '<i class="bi bi-check-lg"></i>' : '2'; ?>
                </div>
                <div class="tl-label">Biodata</div>
            </div>

            <div class="tl-line <?= $biodata ? 'done' : ''; ?>"></div>

            <!-- Step 3: Upload Berkas -->
            <?php
            $docStatus = $doc['status_verifikasi'] ?? 'Belum Upload';
            $step3Class = 'pending';
            if ($docStatus == 'Terverifikasi') $step3Class = 'done';
            elseif ($docStatus == 'Menunggu Verifikasi') $step3Class = 'active';
            elseif ($docStatus == 'Ditolak') $step3Class = 'rejected';
            elseif ($biodata) $step3Class = 'active';
            ?>
            <div class="tl-step">
                <div class="tl-circle <?= $step3Class; ?>">
                    <?php
                    if ($step3Class == 'done') echo '<i class="bi bi-check-lg"></i>';
                    elseif ($step3Class == 'rejected') echo '<i class="bi bi-x-lg"></i>';
                    else echo '3';
                    ?>
                </div>
                <div class="tl-label">Berkas</div>
            </div>

            <div class="tl-line <?= $docStatus == 'Terverifikasi' ? 'done' : ''; ?>"></div>

            <!-- Step 4: Seleksi -->
            <?php
            $step4Class = 'pending';
            if ($docStatus == 'Terverifikasi') $step4Class = 'active';
            if ($biodata['status_hasil'] != 'Menunggu') $step4Class = 'done';
            ?>
            <div class="tl-step">
                <div class="tl-circle <?= $step4Class; ?>">
                    <?= $step4Class == 'done' ? '<i class="bi bi-check-lg"></i>' : '4'; ?>
                </div>
                <div class="tl-label">Seleksi</div>
            </div>

            <div class="tl-line <?= $biodata['status_hasil'] == 'Diterima' ? 'done' : ''; ?>"></div>

            <!-- Step 5: Pengumuman -->
            <?php
            $step5Class = 'pending';
            if ($biodata['is_published'] && $biodata['status_hasil'] != 'Menunggu') $step5Class = 'done';
            ?>
            <div class="tl-step">
                <div class="tl-circle <?= $step5Class; ?>">
                    <?= $step5Class == 'done' ? '<i class="bi bi-check-lg"></i>' : '5'; ?>
                </div>
                <div class="tl-label">Pengumuman</div>
            </div>

            <?php if ($biodata['status_hasil'] == 'Diterima'): ?>
            <div class="tl-line <?= $biodata['is_published'] ? 'done' : ''; ?>"></div>

            <!-- Step 6: Daftar Ulang -->
            <div class="tl-step">
                <div class="tl-circle <?= $biodata['is_published'] ? 'active' : 'pending'; ?>">
                    6
                </div>
                <div class="tl-label">Daftar Ulang</div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>