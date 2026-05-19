<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header("Location: verifikasi.php");
    exit;
}

$pesan = $_GET['pesan'] ?? '';

// ============================================================
// HANDLE AKSI (Verifikasi / Tolak)
// ============================================================
if (isset($_POST['aksi'])) {

    $aksi    = $_POST['aksi'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    if ($aksi == 'verifikasi') {

        mysqli_query($conn, "
            UPDATE documents
            SET status_verifikasi = 'Terverifikasi', catatan = '$catatan'
            WHERE user_id = '$user_id'
        ");
        mysqli_query($conn, "
            UPDATE biodata_mahasiswa
            SET status_pendaftaran = 'Terverifikasi'
            WHERE user_id = '$user_id'
        ");
        header("Location: verifikasi_detail.php?id=$user_id&pesan=verifikasi");

    } elseif ($aksi == 'tolak') {

        mysqli_query($conn, "
            UPDATE documents
            SET status_verifikasi = 'Ditolak', catatan = '$catatan'
            WHERE user_id = '$user_id'
        ");
        mysqli_query($conn, "
            UPDATE biodata_mahasiswa
            SET status_pendaftaran = 'Belum Lengkap'
            WHERE user_id = '$user_id'
        ");
        header("Location: verifikasi_detail.php?id=$user_id&pesan=tolak");

    }

    exit;
}

// ============================================================
// AMBIL DATA MAHASISWA
// ============================================================
$mhsQuery = mysqli_query($conn, "
    SELECT
        u.id,
        u.fullname,
        u.email,
        u.created_at AS tgl_daftar,
        b.nik,
        b.nisn,
        b.tempat_lahir,
        b.tanggal_lahir,
        b.jenis_kelamin,
        b.alamat,
        b.no_hp,
        b.asal_sekolah,
        b.jurusan_pilihan,
        b.status_pendaftaran,
        d.foto        AS doc_foto,
        d.ijazah      AS doc_ijazah,
        d.rapor       AS doc_rapor,
        d.ktp         AS doc_ktp,
        d.status_verifikasi,
        d.catatan     AS catatan_admin
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.id = '$user_id' AND u.role = 'mahasiswa'
");

$mhs = mysqli_fetch_assoc($mhsQuery);

if (!$mhs) {
    header("Location: verifikasi.php");
    exit;
}

// Helper: cek apakah file adalah gambar
function isImage($filename) {
    if (!$filename) return false;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

include '../../app/views/layouts/header.php';
?>


<?php include '../../app/views/layouts/sidebar_admin.php'; ?>
<div class="main-content">

    <!-- BREADCRUMB -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="verifikasi.php">Verifikasi Berkas</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($mhs['fullname']); ?></li>
        </ol>
    </nav>

    <!-- NOTIFIKASI: ditangani AJAX toast di bawah -->

    <div class="row">

        <!-- KOLOM KIRI: Info + Aksi -->
        <div class="col-lg-4 mb-4">

            <!-- INFO MAHASISWA -->
            <div class="card card-modern p-4 mb-3">

                <div class="text-center mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                        style="width:70px;height:70px;">
                        <i class="bi bi-person-fill fs-2 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($mhs['fullname']); ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($mhs['email']); ?></small>
                </div>

                <hr>

                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">NIK</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['nik'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">NISN</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['nisn'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Lahir</td>
                        <td class="fw-semibold">
                            <?= $mhs['tempat_lahir'] ? htmlspecialchars($mhs['tempat_lahir']) . ', ' .
                                date('d/m/Y', strtotime($mhs['tanggal_lahir'])) : '-'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">No. HP</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['no_hp'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Asal Sekolah</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['asal_sekolah'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jurusan</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['jurusan_pilihan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alamat</td>
                        <td class="fw-semibold"><?= htmlspecialchars($mhs['alamat'] ?? '-'); ?></td>
                    </tr>
                </table>

            </div>

            <!-- STATUS & AKSI -->
            <div class="card card-modern p-4">

                <h6 class="fw-bold mb-3">Status Dokumen</h6>

                <?php
                $sv = $mhs['status_verifikasi'] ?? 'Belum Upload';
                if ($sv == 'Terverifikasi') {
                    echo "<div id='statusVerifikasiBadge' class='alert alert-success py-2 mb-3'>
                            <i class='bi bi-patch-check-fill me-2'></i> Terverifikasi
                          </div>";
                } elseif ($sv == 'Menunggu Verifikasi') {
                    echo "<div id='statusVerifikasiBadge' class='alert alert-warning py-2 mb-3'>
                            <i class='bi bi-hourglass-split me-2'></i> Menunggu Verifikasi
                          </div>";
                } elseif ($sv == 'Ditolak') {
                    echo "<div id='statusVerifikasiBadge' class='alert alert-danger py-2 mb-3'>
                            <i class='bi bi-x-circle-fill me-2'></i> Ditolak
                          </div>";
                } else {
                    echo "<div id='statusVerifikasiBadge' class='alert alert-secondary py-2 mb-3'>
                            <i class='bi bi-cloud-upload me-2'></i> Belum Upload
                          </div>";
                }
                ?>

                <?php if ($mhs['catatan_admin']): ?>
                <div class="mb-3">
                    <label class="text-muted small">Catatan Admin:</label>
                    <div class="border rounded p-2 bg-light small">
                        <?= nl2br(htmlspecialchars($mhs['catatan_admin'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi hanya jika ada dokumen -->
                <?php if ($sv == 'Menunggu Verifikasi' || $sv == 'Terverifikasi' || $sv == 'Ditolak'): ?>

                <div class="d-grid gap-2">

                    <button class="btn btn-success"
                        <?= $sv == 'Terverifikasi' ? 'disabled' : '' ?>
                        data-bs-toggle="modal"
                        data-bs-target="#modalVerifikasi">
                        <i class="bi bi-patch-check me-1"></i> Verifikasi Dokumen
                    </button>

                    <button class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTolak">
                        <i class="bi bi-x-circle me-1"></i> Tolak & Minta Upload Ulang
                    </button>

                </div>

                <?php else: ?>
                    <div class="text-muted small text-center">
                        <i class="bi bi-info-circle me-1"></i>
                        Mahasiswa belum mengupload dokumen.
                    </div>
                <?php endif; ?>

            </div>

        </div>

        <!-- KOLOM KANAN: Preview Dokumen -->
        <div class="col-lg-8 mb-4">

            <div class="card card-modern p-4">

                <h5 class="fw-bold mb-4">
                    <i class="bi bi-folder2-open me-2 text-primary"></i>
                    Dokumen yang Diupload
                </h5>

                <div class="row">

                    <!-- FOTO -->
                    <div class="col-md-6 mb-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-person-square me-1 text-primary"></i> Foto
                            </h6>
                            <?php if ($mhs['doc_foto']): ?>
                                <img src="../uploads/foto/<?= htmlspecialchars($mhs['doc_foto']); ?>"
                                    class="img-fluid rounded"
                                    style="max-height:200px;object-fit:cover;width:100%;"
                                    onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ditemukan'">
                                <a href="../uploads/foto/<?= htmlspecialchars($mhs['doc_foto']); ?>"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary mt-2 w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> Buka Full
                                </a>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-image fs-1"></i><br>
                                    <small>Belum diupload</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- KTP / KK -->
                    <div class="col-md-6 mb-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-credit-card me-1 text-warning"></i> KTP / KK
                            </h6>
                            <?php if ($mhs['doc_ktp']): ?>
                                <?php if (isImage($mhs['doc_ktp'])): ?>
                                    <img src="../uploads/ktp/<?= htmlspecialchars($mhs['doc_ktp']); ?>"
                                        class="img-fluid rounded"
                                        style="max-height:200px;object-fit:cover;width:100%;"
                                        onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ditemukan'">
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i><br>
                                        <small class="text-muted">File PDF</small>
                                    </div>
                                <?php endif; ?>
                                <a href="../uploads/ktp/<?= htmlspecialchars($mhs['doc_ktp']); ?>"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary mt-2 w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                                </a>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-credit-card fs-1"></i><br>
                                    <small>Belum diupload</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- IJAZAH -->
                    <div class="col-md-6 mb-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-award me-1 text-success"></i> Ijazah
                            </h6>
                            <?php if ($mhs['doc_ijazah']): ?>
                                <?php if (isImage($mhs['doc_ijazah'])): ?>
                                    <img src="../uploads/ijazah/<?= htmlspecialchars($mhs['doc_ijazah']); ?>"
                                        class="img-fluid rounded"
                                        style="max-height:200px;object-fit:cover;width:100%;"
                                        onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ditemukan'">
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i><br>
                                        <small class="text-muted">File PDF</small>
                                    </div>
                                <?php endif; ?>
                                <a href="../uploads/ijazah/<?= htmlspecialchars($mhs['doc_ijazah']); ?>"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-success mt-2 w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                                </a>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-award fs-1"></i><br>
                                    <small>Belum diupload</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RAPOR -->
                    <div class="col-md-6 mb-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-journal-text me-1 text-info"></i> Rapor
                            </h6>
                            <?php if ($mhs['doc_rapor']): ?>
                                <?php if (isImage($mhs['doc_rapor'])): ?>
                                    <img src="../uploads/rapor/<?= htmlspecialchars($mhs['doc_rapor']); ?>"
                                        class="img-fluid rounded"
                                        style="max-height:200px;object-fit:cover;width:100%;"
                                        onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ditemukan'">
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i><br>
                                        <small class="text-muted">File PDF</small>
                                    </div>
                                <?php endif; ?>
                                <a href="../uploads/rapor/<?= htmlspecialchars($mhs['doc_rapor']); ?>"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-info mt-2 w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                                </a>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-journal-text fs-1"></i><br>
                                    <small>Belum diupload</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ============================================================ -->
<!-- TOAST NOTIFIKASI -->
<!-- ============================================================ -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="toastNotif" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL VERIFIKASI -->
<!-- ============================================================ -->
<div class="modal fade" id="modalVerifikasi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formVerifikasi">
                <input type="hidden" name="aksi" value="verifikasi">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-success">
                        <i class="bi bi-patch-check me-2"></i> Konfirmasi Verifikasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Anda akan <strong>memverifikasi</strong> dokumen milik:</p>
                    <div class="alert alert-light border">
                        <strong><?= htmlspecialchars($mhs['fullname']); ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($mhs['email']); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="3"
                            placeholder="Tambahkan catatan jika perlu..."></textarea>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Status pendaftaran mahasiswa akan diubah menjadi <strong>Terverifikasi</strong>.
                    </p>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnVerifikasi">
                        <i class="bi bi-check-lg me-1"></i> Ya, Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL TOLAK -->
<!-- ============================================================ -->
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTolak">
                <input type="hidden" name="aksi" value="tolak">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-x-circle me-2"></i> Tolak Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Anda akan <strong>menolak</strong> dokumen milik:</p>
                    <div class="alert alert-light border">
                        <strong><?= htmlspecialchars($mhs['fullname']); ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($mhs['email']); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="4"
                            placeholder="Jelaskan alasan penolakan agar mahasiswa tahu dokumen mana yang perlu diperbaiki..."
                            required></textarea>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                        Mahasiswa perlu mengupload ulang dokumen setelah ditolak.
                    </p>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnTolak">
                        <i class="bi bi-x-lg me-1"></i> Ya, Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const userId = <?= (int)$user_id ?>;

    // ── Helper: tampilkan toast ────────────────────────────────────
    function showToast(msg, type) {
        const toast = document.getElementById('toastNotif');
        const toastMsg = document.getElementById('toastMsg');
        toastMsg.textContent = msg;
        toast.className = 'toast align-items-center border-0 text-bg-' + type;
        bootstrap.Toast.getOrCreateInstance(toast, { delay: 3500 }).show();
    }

    // ── Helper: update badge status di halaman ────────────────────
    function updateBadge(aksi) {
        // Badge status verifikasi
        // Update status box di halaman
        const statusBox = document.getElementById('statusVerifikasiBadge');
        if (statusBox) {
            if (aksi === 'verifikasi') {
                statusBox.className = 'alert alert-success py-2 mb-3';
                statusBox.innerHTML = "<i class='bi bi-patch-check-fill me-2'></i> Terverifikasi";
            } else {
                statusBox.className = 'alert alert-danger py-2 mb-3';
                statusBox.innerHTML = "<i class='bi bi-x-circle-fill me-2'></i> Ditolak";
            }
        }
        // Disable tombol verifikasi setelah berhasil diverifikasi
        if (aksi === 'verifikasi') {
            const btnV = document.querySelector('[data-bs-target="#modalVerifikasi"]');
            if (btnV) btnV.setAttribute('disabled', 'disabled');
        }
    }

    // ── Helper: kirim form via fetch ──────────────────────────────
    function submitAksi(formEl, modalEl, btnEl, aksi) {
        const btn = document.getElementById(btnEl);
        if (!btn) return;

        btn.addEventListener('click', function () {
            const form = document.getElementById(formEl);
            const catatan = form.querySelector('textarea[name="catatan"]');

            // Validasi jika tolak harus ada catatan
            if (aksi === 'tolak' && (!catatan || catatan.value.trim() === '')) {
                catatan.classList.add('is-invalid');
                catatan.focus();
                return;
            }
            if (catatan) catatan.classList.remove('is-invalid');

            // Kunci tombol agar tidak double-click
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            const data = new FormData();
            data.append('aksi', aksi);
            data.append('catatan', catatan ? catatan.value : '');

            fetch('verifikasi_detail.php?id=' + userId, {
                method: 'POST',
                body: data
            })
            .then(function (res) {
                // PHP redirect (302) otomatis diikuti fetch, cek final URL
                if (res.ok || res.redirected) {
                    // Tutup modal
                    bootstrap.Modal.getInstance(document.getElementById(modalEl)).hide();

                    // Reset form
                    if (catatan) catatan.value = '';

                    // Update badge di halaman
                    updateBadge(aksi);

                    // Tampilkan toast
                    if (aksi === 'verifikasi') {
                        showToast('✅ Dokumen berhasil diverifikasi.', 'success');
                    } else {
                        showToast('❌ Dokumen ditolak. Mahasiswa perlu upload ulang.', 'danger');
                    }
                } else {
                    showToast('Terjadi kesalahan. Coba lagi.', 'warning');
                }
            })
            .catch(function () {
                showToast('Gagal terhubung ke server.', 'danger');
            })
            .finally(function () {
                // Pulihkan tombol
                btn.disabled = false;
                if (aksi === 'verifikasi') {
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Ya, Verifikasi';
                } else {
                    btn.innerHTML = '<i class="bi bi-x-lg me-1"></i> Ya, Tolak';
                }
            });
        });
    }

    submitAksi('formVerifikasi', 'modalVerifikasi', 'btnVerifikasi', 'verifikasi');
    submitAksi('formTolak',      'modalTolak',      'btnTolak',      'tolak');
})();
</script>

<?php include '../../app/views/layouts/footer.php'; ?>