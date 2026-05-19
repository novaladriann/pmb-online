<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PMB Online</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>

    body{
        background:#f5f7fb;
    }

    .hero{
        min-height:90vh;
        display:flex;
        align-items:center;
    }

    .hero-title{
        font-size:55px;
        font-weight:800;
        color:#0d1b52;
    }

    .hero-text{
        color:#6c757d;
        font-size:18px;
    }

    .btn-modern{
        padding:14px 30px;
        border-radius:12px;
        font-weight:600;
    }

    .hero-image{
        width:100%;
    }

    .navbar{
        background:white;
    }

    .feature-card{
        border:none;
        border-radius:20px;
        box-shadow:0 5px 15px rgba(0,0,0,0.08);
        transition:0.3s;
    }

    .feature-card:hover{
        transform:translateY(-5px);
    }

    </style>
</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg shadow-sm py-3">
    <div class="container">

        <a class="navbar-brand fw-bold text-primary" href="#">
            PMB ONLINE
        </a>

        <div>

            <a href="login.php" class="btn btn-outline-primary me-2">
                Login
            </a>

            <a href="register.php" class="btn btn-primary">
                Register
            </a>

        </div>

    </div>
</nav>

<!-- HERO SECTION -->

<section class="hero">

<div class="container">

<div class="row align-items-center">

<div class="col-md-6">

<h1 class="hero-title mb-4">
Penerimaan Mahasiswa Baru Online
</h1>

<p class="hero-text mb-4">
Daftar kuliah menjadi lebih mudah, cepat, dan modern.
Lengkapi data, upload berkas, dan pantau status pendaftaran langsung secara online.
</p>

<a href="register.php" class="btn btn-primary btn-modern me-3">
    Daftar Sekarang
</a>

<a href="login.php" class="btn btn-outline-primary btn-modern">
    Login
</a>

</div>

<div class="col-md-6 text-center">

<img 
src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png"
class="hero-image"
width="450"
>

</div>

</div>

</div>

</section>

<!-- FITUR -->

<section class="pb-5">

<div class="container">

<div class="row">

<div class="col-md-4 mb-4">

<div class="card feature-card p-4 text-center">

<i class="bi bi-person-plus display-4 text-primary"></i>

<h4 class="mt-3">
Registrasi Online
</h4>

<p class="text-muted">
Calon mahasiswa dapat mendaftar secara online kapan saja.
</p>

</div>

</div>

<div class="col-md-4 mb-4">

<div class="card feature-card p-4 text-center">

<i class="bi bi-file-earmark-arrow-up display-4 text-success"></i>

<h4 class="mt-3">
Upload Dokumen
</h4>

<p class="text-muted">
Upload berkas persyaratan secara mudah dan aman.
</p>

</div>

</div>

<div class="col-md-4 mb-4">

<div class="card feature-card p-4 text-center">

<i class="bi bi-megaphone display-4 text-warning"></i>

<h4 class="mt-3">
Pengumuman Online
</h4>

<p class="text-muted">
Lihat hasil seleksi dan informasi daftar ulang secara online.
</p>

</div>

</div>

</div>

</div>

</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>