<?php

include '../../app/helpers/auth.php';
include '../../app/config/database.php';

auth();
onlyMahasiswa();

$user = $_SESSION['user'];

$message = "";

$query = mysqli_query($conn,
"SELECT * FROM biodata_mahasiswa WHERE user_id='{$user['id']}'");

$data = mysqli_fetch_assoc($query);

if(isset($_POST['simpan'])){

    $nik = $_POST['nik'];
    $nisn = $_POST['nisn'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    $asal_sekolah = $_POST['asal_sekolah'];
    $jurusan_pilihan = $_POST['jurusan_pilihan'];

    if($data){

        mysqli_query($conn,"
        UPDATE biodata_mahasiswa SET
        nik='$nik',
        nisn='$nisn',
        tempat_lahir='$tempat_lahir',
        tanggal_lahir='$tanggal_lahir',
        jenis_kelamin='$jenis_kelamin',
        alamat='$alamat',
        no_hp='$no_hp',
        asal_sekolah='$asal_sekolah',
        jurusan_pilihan='$jurusan_pilihan'
        WHERE user_id='{$user['id']}'
        ");

    } else {

        mysqli_query($conn,"
        INSERT INTO biodata_mahasiswa
        (user_id,nik,nisn,tempat_lahir,tanggal_lahir,jenis_kelamin,alamat,no_hp,asal_sekolah,jurusan_pilihan)
        VALUES
        ('{$user['id']}','$nik','$nisn','$tempat_lahir','$tanggal_lahir','$jenis_kelamin','$alamat','$no_hp','$asal_sekolah','$jurusan_pilihan')
        ");
    }

    header("Location: biodata.php");
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

<a href="biodata.php" style="background:rgba(255,255,255,0.2);">
<i class="bi bi-person"></i> Biodata
</a>

<a href="upload.php">
<i class="bi bi-file-earmark-arrow-up"></i> Upload Berkas
</a>

<a href="pengumuman.php">
<i class="bi bi-megaphone"></i> Pengumuman
</a>

<?php if (isset($data) && ($data['status_hasil'] ?? '') === 'Diterima' && ($data['is_published'] ?? 0)): ?>
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
Form Biodata Mahasiswa
</h3>

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">
<label>NIK</label>
<input type="text" name="nik" class="form-control"
value="<?= $data['nik'] ?? '' ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>NISN</label>
<input type="text" name="nisn" class="form-control"
value="<?= $data['nisn'] ?? '' ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>Tempat Lahir</label>
<input type="text" name="tempat_lahir" class="form-control"
value="<?= $data['tempat_lahir'] ?? '' ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>Tanggal Lahir</label>
<input type="date" name="tanggal_lahir" class="form-control"
value="<?= $data['tanggal_lahir'] ?? '' ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>Jenis Kelamin</label>

<select name="jenis_kelamin" class="form-select">

<option value="Laki-laki">Laki-laki</option>
<option value="Perempuan">Perempuan</option>

</select>

</div>

<div class="col-md-6 mb-3">
<label>No HP</label>
<input type="text" name="no_hp" class="form-control"
value="<?= $data['no_hp'] ?? '' ?>" required>
</div>

<div class="col-md-12 mb-3">
<label>Alamat</label>

<textarea name="alamat" class="form-control" rows="3"><?= $data['alamat'] ?? '' ?></textarea>
</div>

<div class="col-md-6 mb-3">
<label>Asal Sekolah</label>

<input type="text" name="asal_sekolah"
class="form-control"
value="<?= $data['asal_sekolah'] ?? '' ?>">
</div>

<div class="col-md-6 mb-3">
<label>Jurusan Pilihan</label>

<input type="text" name="jurusan_pilihan"
class="form-control"
value="<?= $data['jurusan_pilihan'] ?? '' ?>">
</div>

</div>

<button type="submit" name="simpan"
class="btn btn-primary">
Simpan Biodata
</button>

</form>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>