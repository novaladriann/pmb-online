<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

$query = mysqli_query($conn,
"SELECT * FROM documents WHERE user_id='{$user['id']}'");

$data = mysqli_fetch_assoc($query);

// Untuk cek link Daftar Ulang di sidebar
$biodataQuery = mysqli_query($conn, "SELECT status_hasil, is_published FROM biodata_mahasiswa WHERE user_id='{$user['id']}'");
$biodataSidebar = mysqli_fetch_assoc($biodataQuery);

$message = "";

if(isset($_POST['upload'])){

    $foto = $_FILES['foto'];
    $ijazah = $_FILES['ijazah'];
    $rapor = $_FILES['rapor'];
    $ktp = $_FILES['ktp'];

    function uploadFile($file, $folder){

        if($file['name'] == ''){
            return null;
        }

        $filename = time() . "_" . basename($file['name']);

        $target = "../../public/uploads/$folder/" . $filename;

        move_uploaded_file($file['tmp_name'], $target);

        return $filename;
    }

    $fotoName = uploadFile($foto, "foto");
    $ijazahName = uploadFile($ijazah, "ijazah");
    $raporName = uploadFile($rapor, "rapor");
    $ktpName = uploadFile($ktp, "ktp");

    if($data){

        mysqli_query($conn,"
        UPDATE documents SET
        foto='$fotoName',
        ijazah='$ijazahName',
        rapor='$raporName',
        ktp='$ktpName',
        status_verifikasi='Menunggu Verifikasi'
        WHERE user_id='{$user['id']}'
        ");

    } else {

        mysqli_query($conn,"
        INSERT INTO documents
        (user_id,foto,ijazah,rapor,ktp,status_verifikasi)
        VALUES
        (
            '{$user['id']}',
            '$fotoName',
            '$ijazahName',
            '$raporName',
            '$ktpName',
            'Menunggu Verifikasi'
        )
        ");
    }

    header("Location: upload.php");
}

include '../../app/views/layouts/header.php';

?>

<div class="sidebar">

<h4 class="text-center fw-bold mb-4">
PMB ONLINE
</h4>

<a href="dashboard.php">
<i class="bi bi-grid"></i> Dashboard
</a>

<a href="biodata.php">
<i class="bi bi-person"></i> Biodata
</a>

<a href="upload.php">
<i class="bi bi-file-earmark-arrow-up"></i> Upload Berkas
</a>

<a href="pengumuman.php">
<i class="bi bi-megaphone"></i> Pengumuman
</a>

<?php if (isset($biodataSidebar) && ($biodataSidebar['status_hasil'] ?? '') === 'Diterima' && ($biodataSidebar['is_published'] ?? 0)): ?>
<a href="daftar_ulang.php">
<i class="bi bi-clipboard-check"></i> Daftar Ulang
</a>
<?php endif; ?>

<a href="../logout.php">
<i class="bi bi-box-arrow-right"></i> Logout
</a>

</div>

<div class="main-content">

<div class="card card-modern p-4">

<h3 class="fw-bold mb-4">
Upload Dokumen PMB
</h3>

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-6 mb-3">

<label>Foto</label>

<input type="file"
name="foto"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Ijazah</label>

<input type="file"
name="ijazah"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Rapor</label>

<input type="file"
name="rapor"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>KTP / KK</label>

<input type="file"
name="ktp"
class="form-control">

</div>

</div>

<button type="submit"
name="upload"
class="btn btn-primary">

Upload Dokumen

</button>

</form>

<hr class="my-4">

<h5>Status Dokumen</h5>

<?php if($data): ?>

<?php

$status = $data['status_verifikasi'];

if($status == 'Terverifikasi'){
    echo "<span class='badge bg-success'>Terverifikasi</span>";
}
elseif($status == 'Menunggu Verifikasi'){
    echo "<span class='badge bg-warning'>Menunggu Verifikasi</span>";
}
elseif($status == 'Ditolak'){
    echo "<span class='badge bg-danger'>Ditolak</span>";
}
else{
    echo "<span class='badge bg-secondary'>Belum Upload</span>";
}
?>

<?php else: ?>

<span class="badge bg-secondary">
Belum Upload
</span>

<?php endif; ?>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>