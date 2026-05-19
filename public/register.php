<?php
require_once '../app/config/database.php';

$message = "";

if(isset($_POST['register'])){

    $fullname = htmlspecialchars($_POST['fullname']);
    $email    = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if($password != $confirm){
        $message = "Konfirmasi password tidak cocok!";
    } else {

        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

        if(mysqli_num_rows($check) > 0){
            $message = "Email sudah digunakan!";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $query = mysqli_query($conn,
                "INSERT INTO users(fullname,email,password)
                 VALUES('$fullname','$email','$hash')"
            );

            if($query){
                header("Location: login.php");
            } else {
                $message = "Registrasi gagal!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f7fb;
}

.register-card{
    border:none;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}
</style>

</head>
<body>

<div class="container">

<div class="row justify-content-center align-items-center vh-100">

<div class="col-md-5">

<div class="card register-card">

<div class="card-body p-5">

<h2 class="fw-bold text-center mb-4">
Daftar PMB
</h2>

<?php if($message): ?>
<div class="alert alert-danger">
    <?= $message; ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
<label>Nama Lengkap</label>
<input type="text" name="fullname" class="form-control" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label>Konfirmasi Password</label>
<input type="password" name="confirm" class="form-control" required>
</div>

<button type="submit" name="register" class="btn btn-primary w-100">
Daftar
</button>

</form>

<div class="text-center mt-3">
Sudah punya akun?
<a href="login.php">Login</a>
</div>

</div>
</div>

</div>
</div>
</div>

</body>
</html>