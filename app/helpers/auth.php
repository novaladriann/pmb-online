<?php

session_start();

function isLogin()
{
    return isset($_SESSION['user']);
}

function auth()
{
    if (!isLogin()) {
        header("Location: ../login.php");
        exit;
    }
}

function onlyAdmin()
{
    if ($_SESSION['user']['role'] != 'admin') {
        header("Location: ../login.php");
        exit;
    }
}

function onlyMahasiswa()
{
    if ($_SESSION['user']['role'] != 'mahasiswa') {
        header("Location: ../login.php");
        exit;
    }
}
?>