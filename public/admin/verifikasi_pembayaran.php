<?php

session_start();
require '../../app/config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role']!='admin'){
    header("Location: ../login.php");
    exit;
}


// PROSES VERIFIKASI
if ($_SERVER['REQUEST_METHOD']=='POST') {

    $id=$_POST['id'];
    $status=$_POST['status'];

    $catatan=mysqli_real_escape_string(
        $conn,
        $_POST['catatan_admin']
    );

    $statusDU=
    ($status=='Terverifikasi')
    ? 'Selesai'
    : 'Pending';

    mysqli_query($conn,"
    UPDATE daftar_ulang
    SET
    status_pembayaran='$status',
    status_daftar_ulang='$statusDU',
    catatan_admin='$catatan'
    WHERE id='$id'
    ");

    header("Location: verifikasi_pembayaran.php");
    exit;
}


// DATA
$query=mysqli_query($conn,"
SELECT
du.*,
u.fullname,
bm.jurusan_pilihan

FROM daftar_ulang du

JOIN users u
ON du.user_id=u.id

LEFT JOIN biodata_mahasiswa bm
ON bm.user_id=u.id

ORDER BY du.created_at DESC
");

include '../../app/views/layouts/header.php';

?>
<?php include '../../app/views/layouts/sidebar_admin.php'; ?>

<div class="main-content">

<h3 class="fw-bold mb-4">
<i class="bi bi-credit-card-fill me-2"></i>
Verifikasi Pembayaran
</h3>

<div class="card shadow border-0 rounded-4">

<div class="card-body">

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-light">

<tr>

<th>No</th>
<th>Mahasiswa</th>
<th>Jurusan</th>
<th>No Registrasi</th>
<th>Total</th>
<th>Bukti</th>
<th>Status</th>
<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php
$no=1;
while($row=mysqli_fetch_assoc($query)):
?>

<tr>

<td><?= $no++ ?></td>

<td><?= $row['fullname'] ?></td>

<td><?= $row['jurusan_pilihan'] ?></td>

<td><?= $row['nomor_registrasi'] ?></td>

<td>
Rp <?= number_format($row['total_pembayaran']) ?>
</td>

<td>

<a href="../uploads/pembayaran/<?= $row['bukti_pembayaran']?>"
target="_blank"
class="btn btn-sm btn-primary">

<i class="bi bi-eye"></i>

</a>

</td>

<td>

<?php

if($row['status_pembayaran']=="Terverifikasi"){
echo '<span class="badge bg-success">
Terverifikasi
</span>';
}

elseif($row['status_pembayaran']=="Ditolak"){
echo '<span class="badge bg-danger">
Ditolak
</span>';
}

else{
echo '<span class="badge bg-warning text-dark">
Menunggu
</span>';
}

?>

</td>

<td>

<form method="POST">

<input
type="hidden"
name="id"
value="<?= $row['id']?>">

<select
name="status"
class="form-select mb-2">

<option>Terverifikasi</option>
<option>Ditolak</option>

</select>

<textarea
name="catatan_admin"
class="form-control mb-2"
placeholder="Catatan"></textarea>

<button class="btn btn-success btn-sm w-100">

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