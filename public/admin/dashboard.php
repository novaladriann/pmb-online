# File: public/admin/verifikasi_pembayaran.php

```php
<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyAdmin();

$user = $_SESSION['user'];

// ============================================================
// HANDLE VERIFIKASI PEMBAYARAN
// ============================================================
if (isset($_POST['verifikasi'])) {

    $id      = (int) $_POST['id'];
    $status  = $_POST['status_pembayaran'];
    $catatan = mysqli_real_escape_string(
        $conn,
        $_POST['catatan_admin'] ?? ''
    );

    $allowed = [
        'Menunggu Verifikasi',
        'Terverifikasi',
        'Ditolak'
    ];

    if (in_array($status, $allowed)) {

        $statusDaftarUlang = (
            $status == 'Terverifikasi'
        )
        ? 'Selesai'
        : 'Pending';

        mysqli_query($conn, "
            UPDATE daftar_ulang
            SET
                status_pembayaran = '$status',
                status_daftar_ulang = '$statusDaftarUlang',
                catatan_admin = '$catatan'
            WHERE id = '$id'
        ");
    }

    header('Location: verifikasi_pembayaran.php?pesan=success');
    exit;
}

// ============================================================
// FILTER
// ============================================================
$filter = $_GET['filter'] ?? 'semua';

$whereClause = '';

if ($filter == 'menunggu') {
    $whereClause = "AND du.status_pembayaran = 'Menunggu Verifikasi'";
}
elseif ($filter == 'terverifikasi') {
    $whereClause = "AND du.status_pembayaran = 'Terverifikasi'";
}
elseif ($filter == 'ditolak') {
    $whereClause = "AND du.status_pembayaran = 'Ditolak'";
}

// ============================================================
// DATA PEMBAYARAN
// ============================================================
$data = mysqli_query($conn, "
    SELECT
        du.*,
        u.fullname,
        u.email,
        b.jurusan_pilihan,
        b.asal_sekolah

    FROM daftar_ulang du

    JOIN users u
        ON du.user_id = u.id

    LEFT JOIN biodata_mahasiswa b
        ON b.user_id = u.id

    WHERE 1=1
    $whereClause

    ORDER BY du.created_at DESC
");

// ============================================================
// STATISTIK
// ============================================================
$statQuery = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status_pembayaran = 'Menunggu Verifikasi'
                THEN 1 ELSE 0
            END
        ) AS menunggu,

        SUM(
            CASE
                WHEN status_pembayaran = 'Terverifikasi'
                THEN 1 ELSE 0
            END
        ) AS terverifikasi,

        SUM(
            CASE
                WHEN status_pembayaran = 'Ditolak'
                THEN 1 ELSE 0
            END
        ) AS ditolak

    FROM daftar_ulang
");

$stat = mysqli_fetch_assoc($statQuery);

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

    <a href="verifikasi.php">
        <i class="bi bi-file-earmark-check"></i> Verifikasi Berkas
    </a>

    <a href="pengumuman.php">
        <i class="bi bi-megaphone"></i> Pengumuman
    </a>

    <a href="verifikasi_pembayaran.php"
       style="background:rgba(255,255,255,0.2);">
        <i class="bi bi-patch-check-fill"></i>
        Verifikasi Pembayaran
    </a>

    <a href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>

<div class="main-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Verifikasi Pembayaran
            </h2>

            <p class="text-muted mb-0">
                Kelola pembayaran daftar ulang mahasiswa
            </p>
        </div>

    </div>

    <!-- ALERT -->
    <?php if(isset($_GET['pesan'])): ?>

        <div class="alert alert-success alert-dismissible fade show">
            Verifikasi pembayaran berhasil diperbarui.
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>

    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <a href="?filter=semua" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-primary border-4">
                    <h6 class="text-muted">Total</h6>
                    <h3 class="fw-bold text-dark">
                        <?= $stat['total'] ?>
                    </h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="?filter=menunggu" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-warning border-4">
                    <h6 class="text-muted">Menunggu</h6>
                    <h3 class="fw-bold text-warning">
                        <?= $stat['menunggu'] ?>
                    </h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="?filter=terverifikasi" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-success border-4">
                    <h6 class="text-muted">Terverifikasi</h6>
                    <h3 class="fw-bold text-success">
                        <?= $stat['terverifikasi'] ?>
                    </h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="?filter=ditolak" class="text-decoration-none">
                <div class="card card-modern p-3 border-start border-danger border-4">
                    <h6 class="text-muted">Ditolak</h6>
                    <h3 class="fw-bold text-danger">
                        <?= $stat['ditolak'] ?>
                    </h3>
                </div>
            </a>
        </div>

    </div>

    <!-- TABEL -->
    <div class="card card-modern">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle table-hover">

                    <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Mahasiswa</th>
                        <th>Jurusan</th>
                        <th>No Registrasi</th>
                        <th>Total Bayar</th>
                        <th>Bukti Transfer</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php
                    $no = 1;
                    while($row = mysqli_fetch_assoc($data)):
                    ?>

                    <tr>

                        <td><?= $no++ ?></td>

                        <td>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($row['email']) ?>
                            </small>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['jurusan_pilihan']) ?>
                        </td>

                        <td>
                            <span class="badge bg-dark">
                                <?= $row['nomor_registrasi'] ?>
                            </span>
                        </td>

                        <td>
                            <strong class="text-success">
                                Rp <?= number_format($row['total_pembayaran']) ?>
                            </strong>
                        </td>

                        <td>

                            <?php if(!empty($row['bukti_pembayaran'])): ?>

                                <a href="../uploads/pembayaran/<?= $row['bukti_pembayaran'] ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-primary">

                                   <i class="bi bi-image"></i>
                                   Lihat

                                </a>

                            <?php else: ?>

                                <span class="text-muted">
                                    Belum Upload
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php

                            if($row['status_pembayaran'] == 'Terverifikasi') {

                                echo '<span class="badge bg-success">Terverifikasi</span>';

                            }
                            elseif($row['status_pembayaran'] == 'Ditolak') {

                                echo '<span class="badge bg-danger">Ditolak</span>';

                            }
                            else {

                                echo '<span class="badge bg-warning text-dark">Menunggu</span>';

                            }

                            ?>

                        </td>

                        <td style="min-width:240px;">

                            <form method="POST">

                                <input type="hidden"
                                       name="id"
                                       value="<?= $row['id'] ?>">

                                <select name="status_pembayaran"
                                        class="form-select form-select-sm mb-2"
                                        required>

                                    <option value="">
                                        Pilih Status
                                    </option>

                                    <option value="Terverifikasi">
                                        Terverifikasi
                                    </option>

                                    <option value="Ditolak">
                                        Ditolak
                                    </option>

                                </select>

                                <textarea
                                    name="catatan_admin"
                                    class="form-control form-control-sm mb-2"
                                    rows="2"
                                    placeholder="Catatan admin..."></textarea>

                                <button type="submit"
                                        name="verifikasi"
                                        class="btn btn-success btn-sm w-100">

                                    <i class="bi bi-check-circle"></i>
                                    Simpan

                                </button>

                            </form>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
