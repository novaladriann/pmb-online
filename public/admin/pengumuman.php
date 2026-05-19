<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user = $_SESSION['user'];
$pesan = '';

// ============================================================
// HANDLE AKSI: Set hasil seleksi per mahasiswa
// ============================================================
if (isset($_POST['simpan_hasil'])) {

    $target_user_id = (int)$_POST['target_user_id'];
    $hasil          = $_POST['hasil'];
    $catatan_hasil  = mysqli_real_escape_string($conn, $_POST['catatan_hasil'] ?? '');

    $allowed = ['Menunggu', 'Diterima', 'Tidak Diterima'];

    if (in_array($hasil, $allowed)) {
    mysqli_query($conn, "
        UPDATE biodata_mahasiswa
        SET status_hasil  = '$hasil',
            catatan_hasil = '$catatan_hasil',
            is_published  = 1
        WHERE user_id = '$target_user_id'
    ");
        $pesan = 'berhasil';
    }

    header("Location: pengumuman.php?pesan=$pesan");
    exit;
}

// ============================================================
// HANDLE AKSI: Umumkan semua (publish)
// ============================================================
if (isset($_POST['publikasikan'])) {
    mysqli_query($conn, "
        UPDATE biodata_mahasiswa
        SET is_published = 1
        WHERE status_hasil IN ('Diterima', 'Tidak Diterima')
    ");
    header("Location: pengumuman.php?pesan=published");
    exit;
}

$pesan = $_GET['pesan'] ?? '';

// Filter
$filter = $_GET['filter'] ?? 'semua';
$whereClause = "";
if ($filter == 'terverifikasi') {
    $whereClause = "AND d.status_verifikasi = 'Terverifikasi'";
} elseif ($filter == 'diterima') {
    $whereClause = "AND b.status_hasil = 'Diterima'";
} elseif ($filter == 'tidak_diterima') {
    $whereClause = "AND b.status_hasil = 'Tidak Diterima'";
} elseif ($filter == 'menunggu') {
    $whereClause = "AND (b.status_hasil = 'Menunggu' OR b.status_hasil IS NULL)";
}

// Ambil data
$data = mysqli_query($conn, "
    SELECT
        u.id,
        u.fullname,
        u.email,
        b.jurusan_pilihan,
        b.asal_sekolah,
        b.status_pendaftaran,
        COALESCE(b.status_hasil, 'Menunggu')   AS status_hasil,
        b.catatan_hasil,
        COALESCE(b.is_published, 0)             AS is_published,
        COALESCE(d.status_verifikasi, 'Belum Upload') AS status_verifikasi
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
    $whereClause
    ORDER BY
        CASE COALESCE(d.status_verifikasi,'Belum Upload')
            WHEN 'Terverifikasi' THEN 1
            ELSE 2
        END,
        CASE COALESCE(b.status_hasil,'Menunggu')
            WHEN 'Menunggu' THEN 1
            WHEN 'Diterima' THEN 2
            WHEN 'Tidak Diterima' THEN 3
        END,
        u.id ASC
");

// Hitung statistik
$statQ = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN d.status_verifikasi = 'Terverifikasi' THEN 1 ELSE 0 END) AS lulus_berkas,
        SUM(CASE WHEN b.status_hasil = 'Diterima' THEN 1 ELSE 0 END)           AS diterima,
        SUM(CASE WHEN b.status_hasil = 'Tidak Diterima' THEN 1 ELSE 0 END)     AS tidak_diterima,
        SUM(CASE WHEN COALESCE(b.status_hasil,'Menunggu') = 'Menunggu'
                  AND d.status_verifikasi = 'Terverifikasi' THEN 1 ELSE 0 END) AS belum_diproses
    FROM users u
    LEFT JOIN biodata_mahasiswa b ON u.id = b.user_id
    LEFT JOIN documents d ON u.id = d.user_id
    WHERE u.role = 'mahasiswa'
");
$stat = mysqli_fetch_assoc($statQ);

include '../../app/views/layouts/header.php';
?>

<style>
.badge-diterima    { background:#198754; color:white; }
.badge-tidak       { background:#dc3545; color:white; }
.badge-menunggu    { background:#ffc107; color:#212529; }
.row-diterima      { background:#f0fff4 !important; }
.row-tidak         { background:#fff5f5 !important; }
.published-dot     { width:8px;height:8px;border-radius:50%;background:#198754;display:inline-block; }
.unpublished-dot   { width:8px;height:8px;border-radius:50%;background:#adb5bd;display:inline-block; }
</style>


<?php include '../../app/views/layouts/sidebar_admin.php'; ?>
<div class="main-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">Pengumuman Hasil Seleksi</h2>
            <p class="text-muted mb-0">Tentukan hasil penerimaan mahasiswa baru</p>
        </div>
        <form method="POST" onsubmit="return confirm('Publikasikan semua hasil? Mahasiswa akan bisa melihat hasilnya.')">
            <button type="submit" name="publikasikan" class="btn btn-primary">
                <i class="bi bi-megaphone me-1"></i> Publikasikan Hasil
            </button>
        </form>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan == 'berhasil'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i> Hasil seleksi berhasil disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan == 'published'): ?>
        <div class="alert alert-primary alert-dismissible fade show">
            <i class="bi bi-megaphone-fill me-2"></i> <strong>Pengumuman dipublikasikan!</strong>
            Mahasiswa kini dapat melihat hasil seleksinya.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <div class="card card-modern p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-people fs-2 text-primary"></i>
                    <div>
                        <div class="fw-bold fs-4"><?= $stat['lulus_berkas']; ?></div>
                        <div class="text-muted small">Lulus Seleksi Berkas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card card-modern p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                    <div>
                        <div class="fw-bold fs-4"><?= $stat['belum_diproses']; ?></div>
                        <div class="text-muted small">Belum Diproses</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card card-modern p-3 border-start border-success border-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-patch-check fs-2 text-success"></i>
                    <div>
                        <div class="fw-bold fs-4"><?= $stat['diterima']; ?></div>
                        <div class="text-muted small">Diterima</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card card-modern p-3 border-start border-danger border-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-x-circle fs-2 text-danger"></i>
                    <div>
                        <div class="fw-bold fs-4"><?= $stat['tidak_diterima']; ?></div>
                        <div class="text-muted small">Tidak Diterima</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABEL -->
    <div class="card card-modern p-4">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

            <!-- Filter Tab -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="pengumuman.php?filter=semua"
                    class="btn btn-sm <?= $filter=='semua' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Semua
                </a>
                <a href="pengumuman.php?filter=terverifikasi"
                    class="btn btn-sm <?= $filter=='terverifikasi' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Lulus Berkas
                </a>
                <a href="pengumuman.php?filter=menunggu"
                    class="btn btn-sm <?= $filter=='menunggu' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    Belum Diproses
                </a>
                <a href="pengumuman.php?filter=diterima"
                    class="btn btn-sm <?= $filter=='diterima' ? 'btn-success' : 'btn-outline-success' ?>">
                    Diterima
                </a>
                <a href="pengumuman.php?filter=tidak_diterima"
                    class="btn btn-sm <?= $filter=='tidak_diterima' ? 'btn-danger' : 'btn-outline-danger' ?>">
                    Tidak Diterima
                </a>
            </div>

            <input type="text" id="searchInput" class="form-control w-auto"
                placeholder="🔍 Cari nama..." style="min-width:200px;">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabelHasil">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Mahasiswa</th>
                        <th>Jurusan Pilihan</th>
                        <th>Status Berkas</th>
                        <th>Hasil Seleksi</th>
                        <th>Dipublikasi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>

                    <?php
                    $rowClass = '';
                    if ($row['status_hasil'] == 'Diterima') $rowClass = 'row-diterima';
                    elseif ($row['status_hasil'] == 'Tidak Diterima') $rowClass = 'row-tidak';
                    $isVerified = ($row['status_verifikasi'] == 'Terverifikasi');
                    ?>

                    <tr class="<?= $rowClass ?>">
                        <td><?= $no++; ?></td>

                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['fullname']); ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($row['email']); ?></div>
                        </td>

                        <td><?= htmlspecialchars($row['jurusan_pilihan'] ?? '-'); ?></td>

                        <td>
                            <?php if ($isVerified): ?>
                                <span class="badge bg-success">✓ Terverifikasi</span>
                            <?php elseif ($row['status_verifikasi'] == 'Menunggu Verifikasi'): ?>
                                <span class="badge bg-warning text-dark">⏳ Menunggu</span>
                            <?php elseif ($row['status_verifikasi'] == 'Ditolak'): ?>
                                <span class="badge bg-danger">✗ Ditolak</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">— Belum Upload</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($row['status_hasil'] == 'Diterima'): ?>
                                <span class="badge badge-diterima px-3 py-2">
                                    <i class="bi bi-check-lg me-1"></i> Diterima
                                </span>
                            <?php elseif ($row['status_hasil'] == 'Tidak Diterima'): ?>
                                <span class="badge badge-tidak px-3 py-2">
                                    <i class="bi bi-x-lg me-1"></i> Tidak Diterima
                                </span>
                            <?php else: ?>
                                <span class="badge badge-menunggu px-3 py-2">
                                    <i class="bi bi-dash me-1"></i> Belum Diproses
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php if ($row['is_published']): ?>
                                <span class="published-dot me-1"></span>
                                <small class="text-success">Publik</small>
                            <?php else: ?>
                                <span class="unpublished-dot me-1"></span>
                                <small class="text-muted">Draft</small>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php if ($isVerified): ?>
                                <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalHasil"
                                    data-uid="<?= $row['id']; ?>"
                                    data-nama="<?= htmlspecialchars($row['fullname']); ?>"
                                    data-jurusan="<?= htmlspecialchars($row['jurusan_pilihan'] ?? '-'); ?>"
                                    data-status="<?= $row['status_hasil']; ?>"
                                    data-catatan="<?= htmlspecialchars($row['catatan_hasil'] ?? ''); ?>">
                                    <i class="bi bi-pencil-square"></i> Set Hasil
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">Berkas belum terverifikasi</span>
                            <?php endif; ?>
                        </td>

                    </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- ============================================================ -->
<!-- MODAL SET HASIL -->
<!-- ============================================================ -->
<div class="modal fade" id="modalHasil" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="simpan_hasil" value="1">
                <input type="hidden" name="target_user_id" id="modalUserId">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-award me-2 text-primary"></i> Set Hasil Seleksi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="alert alert-light border mb-3">
                        <strong id="modalNama"></strong><br>
                        <small class="text-muted">Jurusan: <span id="modalJurusan"></span></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasil Seleksi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">

                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="hasil"
                                    id="hasilDiterima" value="Diterima">
                                <label class="form-check-label text-success fw-semibold" for="hasilDiterima">
                                    <i class="bi bi-check-circle-fill me-1"></i> Diterima
                                </label>
                            </div>

                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="hasil"
                                    id="hasilTidak" value="Tidak Diterima">
                                <label class="form-check-label text-danger fw-semibold" for="hasilTidak">
                                    <i class="bi bi-x-circle-fill me-1"></i> Tidak Diterima
                                </label>
                            </div>

                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="hasil"
                                    id="hasilMenunggu" value="Menunggu">
                                <label class="form-check-label text-muted" for="hasilMenunggu">
                                    <i class="bi bi-dash-circle me-1"></i> Reset
                                </label>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan untuk Mahasiswa <small class="text-muted">(opsional)</small></label>
                        <textarea name="catatan_hasil" id="modalCatatan" class="form-control" rows="3"
                            placeholder="Contoh: Selamat! Anda diterima di Prodi Informatika..."></textarea>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Hasil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Isi data modal dari tombol
const modalHasil = document.getElementById('modalHasil');
modalHasil.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('modalUserId').value  = btn.dataset.uid;
    document.getElementById('modalNama').textContent    = btn.dataset.nama;
    document.getElementById('modalJurusan').textContent = btn.dataset.jurusan;
    document.getElementById('modalCatatan').value       = btn.dataset.catatan;

    // Set radio sesuai status saat ini
    const status = btn.dataset.status;
    if (status === 'Diterima') {
        document.getElementById('hasilDiterima').checked = true;
    } else if (status === 'Tidak Diterima') {
        document.getElementById('hasilTidak').checked = true;
    } else {
        document.getElementById('hasilMenunggu').checked = true;
    }
});

// Search
document.getElementById('searchInput').addEventListener('keyup', function () {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#tabelHasil tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(kw) ? '' : 'none';
    });
});
</script>

<?php include '../../app/views/layouts/footer.php'; ?>