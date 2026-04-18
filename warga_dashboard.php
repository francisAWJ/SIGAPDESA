<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'warga') {
    header("Location: login.php");
    exit();
}


$warga = $_SESSION['warga'];
$id_warga = $warga['id_warga'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_lengkap'];
    $telepon = $_POST['nomor_telepon'];
    $pekerjaan = $_POST['pekerjaan'];

    $update = "UPDATE warga SET 
                nama_lengkap='$nama',
                nomor_telepon='$telepon',
                pekerjaan='$pekerjaan',
                updated_at=NOW()
                WHERE id_warga=$id_warga";
    mysqli_query($conn, $update);

    // Refresh data
    $warga = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM warga WHERE id_warga=$id_warga"));
    $_SESSION['warga'] = $warga;
    echo "<script>alert('Data berhasil diperbarui'); window.location='warga_dashboard.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard Warga - SIGAP</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .bg-sigap {
            background-color: #b30000 !important; /* nuansa merah */
        }
        .card {
            border-radius: 12px;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-sigap sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon">
                    <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" style="width: 150px; height: 120px;">
                </div>
            </a>
            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="warga_dashboard.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Profil Saya</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        <!-- End Sidebar -->

        <!-- Content -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" class="p-4">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Halo, <?= htmlspecialchars($warga['nama_lengkap']); ?> 👋</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header bg-sigap text-white">
                            <h6 class="m-0 font-weight-bold">Data Pribadi</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>NIK:</label>
                                    <input type="text" class="form-control" value="<?= $warga['nik']; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lengkap:</label>
                                    <input type="text" name="nama_lengkap" value="<?= $warga['nama_lengkap']; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Nomor Telepon:</label>
                                    <input type="text" name="nomor_telepon" value="<?= $warga['nomor_telepon']; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Pekerjaan:</label>
                                    <input type="text" name="pekerjaan" value="<?= $warga['pekerjaan']; ?>" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-danger">Update Data</button>
                                <a href="logout.php" class="btn btn-secondary">Logout</a>
                            </form>
                        </div>
                    </div>

                    <p class="text-muted small text-center">© SIGAP Desa - <?= date('Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
</body>
</html>
