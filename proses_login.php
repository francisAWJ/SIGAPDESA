<?php
session_start();
include 'config.php';

$tipe = $_POST['tipe'];
$username = $_POST['username'];
$password = $_POST['password'];

if ($tipe == 'admin') {
    // LOGIN ADMIN
    $query = "SELECT * FROM admin WHERE username = '$username' AND password = MD5('$password')";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        // Simpan session global
        $_SESSION['username']  = $admin['username'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['admin']     = $admin;

        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Username atau password admin salah!";
        header("Location: login.php");
        exit();
    }

} elseif ($tipe == 'warga') {
    // LOGIN WARGA (pakai NIK & Tanggal Lahir format ddmmyyyy)
    $nik = mysqli_real_escape_string($conn, $username);
    $tgl_input = mysqli_real_escape_string($conn, $password);

    if (strlen($tgl_input) != 8) {
        $_SESSION['error'] = "Format tanggal salah! Gunakan format ddmmyyyy (contoh: 23082003)";
        header("Location: login.php");
        exit();
    }

    // Ubah format ke yyyy-mm-dd agar cocok dengan database
    $tgl_db = substr($tgl_input, 4, 4) . '-' . substr($tgl_input, 2, 2) . '-' . substr($tgl_input, 0, 2);

    $query = "SELECT * FROM warga WHERE nik = '$nik' AND tanggal_lahir = '$tgl_db'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $warga = mysqli_fetch_assoc($result);

        // Simpan session global
        $_SESSION['username']  = $warga['nik'];
        $_SESSION['user_role'] = 'warga';
        $_SESSION['warga']     = $warga;

        header("Location: warga_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "NIK atau tanggal lahir salah!";
        header("Location: login.php");
        exit();
    }

} else {
    $_SESSION['error'] = "Pilih tipe akun terlebih dahulu!";
    header("Location: login.php");
    exit();
}
?>
