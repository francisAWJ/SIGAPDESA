<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Ambil embed dashboard terbaru
$q = $conn->query("SELECT embed_code FROM tableau_embed ORDER BY id DESC LIMIT 1");
$currentEmbed = $q->fetch_assoc()['embed_code'] ?? "<p class='text-danger'>Belum ada embed code.</p>";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Titik Evakuasi - SIGAP</title>
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

            <li class="nav-item"><a class="nav-link" href="index.php"><i
                        class="fas fa-fw fa-home"></i><span>Home</span></a></li>
            <li class="nav-item"><a class="nav-link" href="input_data.php"><i
                        class="fas fa-fw fa-user-edit"></i><span>Input Data Penduduk</span></a></li>
            <li class="nav-item"><a class="nav-link" href="visual_data.php"><i
                        class="fas fa-fw fa-chart-bar"></i><span>Visual Data</span></a></li>
            <li class="nav-item active"><a class="nav-link" href="titik_evakuasi.php"><i
                        class="fas fa-fw fa-map-marker-alt"></i><span>Titik Evakuasi</span></a></li>

            <hr class="sidebar-divider">

            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </li>
        </ul>
        <!-- End Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" class="p-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h3 mb-4 text-gray-800">Peta Titik Evakuasi</h1>

                    <!-- 🔘 Tombol Edit Embed Tableau -->
                    <a href="edit_embed.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Embed Tableau
                    </a>
                </div>

                <p>Berikut ini visualisasi titik evakuasi berdasarkan data Tableau.</p>

                <!-- ============ EMBED TABLEAU DINAMIS ============ -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Dashboard Tableau</h5>

                        <div style="width: 100%;">
                            <?= $currentEmbed ?>
                        </div>
                    </div>
                </div>
                <!-- ================================================= -->

            </div>
        </div>

    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>