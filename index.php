<?php
session_start();
include 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home - SIGAP</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>

<body id="page-top">

    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon">
                    <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" style="width: 180px; height: 60px;">
                </div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-home"></i><span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="input_data.php">
                    <i class="fas fa-fw fa-user-edit"></i><span>Input Data Penduduk</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="visual_data.php">
                    <i class="fas fa-fw fa-chart-bar"></i><span>Visual Data</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="titik_evakuasi.php">
                    <i class="fas fa-fw fa-map-marker-alt"></i><span>Titik Evakuasi</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <!-- Tombol Logout -->
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php" onclick="return confirm('Yakin ingin logout?');">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </li>
        </ul>
        <!-- End Sidebar -->


        <!-- Content -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" class="p-4">
                <h1 class="h3 mb-4 text-gray-800">Selamat Datang di SIGAP</h1>
                <p>Gunakan menu di sidebar untuk navigasi fitur SIGAP seperti input data penduduk, visualisasi data, dan
                    titik evakuasi.</p>
            </div>
        </div>
        <!-- End Content -->
    </div>
    <!-- End Wrapper -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>