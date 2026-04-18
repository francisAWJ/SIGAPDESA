<?php
include 'config.php';
session_start();

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ==================== QUERY DATA DARI DATABASE ====================

// 1. Jumlah berdasarkan Jenis Kelamin
$query_gender = "SELECT jenis_kelamin, COUNT(*) as jumlah FROM warga GROUP BY jenis_kelamin";
$result_gender = mysqli_query($conn, $query_gender);

$gender_labels = [];
$gender_data = [];

while ($row = mysqli_fetch_assoc($result_gender)) {
    $label = $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan';
    $gender_labels[] = $label;
    $gender_data[] = (int) $row['jumlah'];
}

// 2. Total Warga
$query_total = "SELECT COUNT(*) as total FROM warga";
$result_total = mysqli_query($conn, $query_total);
$total_warga = mysqli_fetch_assoc($result_total)['total'];

// 7. Total KK Terdaftar
$query_kk = "SELECT COUNT(*) AS total_kk FROM keluarga";
$result_kk = mysqli_query($conn, $query_kk);
$total_kk = mysqli_fetch_assoc($result_kk)['total_kk'];

// 3. Jumlah berdasarkan Agama
$query_agama = "SELECT agama, COUNT(*) as jumlah FROM warga WHERE agama IS NOT NULL AND agama != '' GROUP BY agama ORDER BY jumlah DESC";
$result_agama = mysqli_query($conn, $query_agama);

$agama_labels = [];
$agama_data = [];

while ($row = mysqli_fetch_assoc($result_agama)) {
    $agama_labels[] = $row['agama'];
    $agama_data[] = (int) $row['jumlah'];
}

// 4. Jumlah berdasarkan Pekerjaan (Top 5)
$query_pekerjaan = "SELECT pekerjaan, COUNT(*) as jumlah FROM warga WHERE pekerjaan IS NOT NULL AND pekerjaan != '' GROUP BY pekerjaan ORDER BY jumlah DESC LIMIT 5";
$result_pekerjaan = mysqli_query($conn, $query_pekerjaan);

$pekerjaan_labels = [];
$pekerjaan_data = [];

while ($row = mysqli_fetch_assoc($result_pekerjaan)) {
    $pekerjaan_labels[] = $row['pekerjaan'];
    $pekerjaan_data[] = (int) $row['jumlah'];
}

// 5. Statistik Kelompok Usia
$query_usia = "
    SELECT 
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 18 THEN 1 ELSE 0 END) as anak,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as dewasa,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia
    FROM warga
";
$result_usia = mysqli_query($conn, $query_usia);
$usia_data = mysqli_fetch_assoc($result_usia);

// 6. Jumlah Ibu Hamil
// 6. Jumlah Ibu Hamil (0 = tidak hamil, 1 = hamil)
$query_hamil = "SELECT COUNT(*) AS jumlah_hamil FROM warga WHERE status_kehamilan = 1";
$result_hamil = mysqli_query($conn, $query_hamil);
$jumlah_hamil = mysqli_fetch_assoc($result_hamil)['jumlah_hamil'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Visual Data - SIGAP</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 20px;
        }

        .stats-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .stats-box h2 {
            font-size: 3rem;
            margin: 0;
        }

        .stats-box-hamil {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
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
            <li class="nav-item active"><a class="nav-link" href="visual_data.php"><i
                        class="fas fa-fw fa-chart-bar"></i><span>Visual Data</span></a></li>
            <li class="nav-item"><a class="nav-link" href="titik_evakuasi.php"><i
                        class="fas fa-fw fa-map-marker-alt"></i><span>Titik Evakuasi</span></a></li>
            <hr class="sidebar-divider">

            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </li>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" class="p-4">
                <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-chart-pie"></i> Visualisasi Data Penduduk</h1>

                <!-- Total Warga dan Ibu Hamil -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-box">
                            <h2><?php echo number_format($total_warga); ?></h2>
                            <p class="mb-0"><i class="fas fa-users"></i> Total Warga Terdaftar</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box stats-box-hamil">
                            <h2><?php echo number_format($jumlah_hamil); ?></h2>
                            <p class="mb-0"><i class="fas fa-female"></i> Ibu Hamil</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box" style="background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);">
                            <h2><?php echo number_format($total_kk); ?></h2>
                            <p class="mb-0"><i class="fas fa-home"></i> KK Terdaftar</p>
                        </div>
                    </div>
                </div>

                <!-- Chart Jenis Kelamin -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-venus-mars"></i> Berdasarkan
                            Jenis Kelamin</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartGender"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart Kelompok Usia -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Berdasarkan Kelompok
                            Usia</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartUsia"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart Agama -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-praying-hands"></i> Berdasarkan
                            Agama</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartAgama"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart Top 5 Pekerjaan -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Top 5 Pekerjaan
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartPekerjaan"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ==================== CHART 1: JENIS KELAMIN ====================
        const ctxGender = document.getElementById('chartGender');
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($gender_labels); ?>,
                datasets: [{
                    label: 'Jumlah',
                    data: <?php echo json_encode($gender_data); ?>,
                    backgroundColor: ['#36b9cc', '#e74a3b'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // ==================== CHART 2: KELOMPOK USIA ====================
        const ctxUsia = document.getElementById('chartUsia');
        new Chart(ctxUsia, {
            type: 'bar',
            data: {
                labels: ['Anak-anak (<18 tahun)', 'Dewasa (18-59 tahun)', 'Lansia (≥60 tahun)'],
                datasets: [{
                    label: 'Jumlah',
                    data: [
                        <?php echo $usia_data['anak']; ?>,
                        <?php echo $usia_data['dewasa']; ?>,
                        <?php echo $usia_data['lansia']; ?>
                    ],
                    backgroundColor: ['#1cc88a', '#4e73df', '#f6c23e'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // ==================== CHART 3: AGAMA ====================
        const ctxAgama = document.getElementById('chartAgama');
        new Chart(ctxAgama, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($agama_labels); ?>,
                datasets: [{
                    label: 'Jumlah',
                    data: <?php echo json_encode($agama_data); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
                        '#e74a3b', '#858796', '#5a5c69'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // ==================== CHART 4: TOP 5 PEKERJAAN ====================
        const ctxPekerjaan = document.getElementById('chartPekerjaan');
        new Chart(ctxPekerjaan, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pekerjaan_labels); ?>,
                datasets: [{
                    label: 'Jumlah',
                    data: <?php echo json_encode($pekerjaan_data); ?>,
                    backgroundColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>