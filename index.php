<?php
session_start();
include 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ==================== PARSE FILTERS ====================
$where_clauses = [];
$joins = [];

if (!empty($_GET['provinsi_id'])) {
    $prov_id = mysqli_real_escape_string($conn, $_GET['provinsi_id']);
    $where_clauses[] = "w.id_provinsi = '$prov_id'";
}
if (!empty($_GET['kabupaten_id'])) {
    $kab_id = mysqli_real_escape_string($conn, $_GET['kabupaten_id']);
    $where_clauses[] = "w.id_kabupaten = '$kab_id'";
}
if (!empty($_GET['kecamatan_id'])) {
    $kec_id = mysqli_real_escape_string($conn, $_GET['kecamatan_id']);
    $where_clauses[] = "w.id_kecamatan = '$kec_id'";
}
if (!empty($_GET['desa_id'])) {
    $desa_id = mysqli_real_escape_string($conn, $_GET['desa_id']);
    $where_clauses[] = "w.id_desa = '$desa_id'";
}
if (!empty($_GET['rt'])) {
    $rt = mysqli_real_escape_string($conn, $_GET['rt']);
    $joins['rumah'] = "LEFT JOIN rumah r ON w.id_rumah = r.id_rumah";
    $joins['rt'] = "LEFT JOIN rt ON r.id_rt = rt.id_rt";
    $where_clauses[] = "rt.nomor_rt = '$rt'";
}
if (!empty($_GET['rw'])) {
    $rw = mysqli_real_escape_string($conn, $_GET['rw']);
    $joins['rumah'] = "LEFT JOIN rumah r ON w.id_rumah = r.id_rumah";
    $joins['rw'] = "LEFT JOIN rw ON r.id_rw = rw.id_rw";
    $where_clauses[] = "rw.nomor_rw = '$rw'";
}

$join_sql = implode(" ", $joins);
$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Query untuk KK Terdaftar (berdasarkan keluarga)
$where_clauses_kk = [];
$joins_kk = ["LEFT JOIN rumah r ON k.id_rumah = r.id_rumah"];

if (!empty($_GET['provinsi_id'])) {
    $prov_id = mysqli_real_escape_string($conn, $_GET['provinsi_id']);
    $where_clauses_kk[] = "r.id_provinsi = '$prov_id'";
}
if (!empty($_GET['kabupaten_id'])) {
    $kab_id = mysqli_real_escape_string($conn, $_GET['kabupaten_id']);
    $where_clauses_kk[] = "r.id_kabupaten = '$kab_id'";
}
if (!empty($_GET['kecamatan_id'])) {
    $kec_id = mysqli_real_escape_string($conn, $_GET['kecamatan_id']);
    $where_clauses_kk[] = "r.id_kecamatan = '$kec_id'";
}
if (!empty($_GET['desa_id'])) {
    $desa_id = mysqli_real_escape_string($conn, $_GET['desa_id']);
    $where_clauses_kk[] = "r.id_desa = '$desa_id'";
}
if (!empty($_GET['rt'])) {
    $rt = mysqli_real_escape_string($conn, $_GET['rt']);
    $joins_kk['rt'] = "LEFT JOIN rt ON r.id_rt = rt.id_rt";
    $where_clauses_kk[] = "rt.nomor_rt = '$rt'";
}
if (!empty($_GET['rw'])) {
    $rw = mysqli_real_escape_string($conn, $_GET['rw']);
    $joins_kk['rw'] = "LEFT JOIN rw ON r.id_rw = rw.id_rw";
    $where_clauses_kk[] = "rw.nomor_rw = '$rw'";
}

$join_sql_kk = implode(" ", $joins_kk);
$where_sql_kk = "";
if (count($where_clauses_kk) > 0) {
    $where_sql_kk = "WHERE " . implode(" AND ", $where_clauses_kk);
}

// ==================== QUERY DATA DARI DATABASE ====================

// 1. Jumlah berdasarkan Jenis Kelamin
$query_gender = "SELECT w.jenis_kelamin, COUNT(*) as jumlah FROM warga w $join_sql $where_sql GROUP BY w.jenis_kelamin";
$result_gender = mysqli_query($conn, $query_gender);

$gender_labels = [];
$gender_data = [];

while ($row = mysqli_fetch_assoc($result_gender)) {
    $label = $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan';
    $gender_labels[] = $label;
    $gender_data[] = (int) $row['jumlah'];
}

// 2. Total Warga
$query_total = "SELECT COUNT(*) as total FROM warga w $join_sql $where_sql";
$result_total = mysqli_query($conn, $query_total);
$total_warga = mysqli_fetch_assoc($result_total)['total'];

// 7. Total KK Terdaftar
$query_kk = "SELECT COUNT(*) AS total_kk FROM keluarga k $join_sql_kk $where_sql_kk";
$result_kk = mysqli_query($conn, $query_kk);
$total_kk = mysqli_fetch_assoc($result_kk)['total_kk'];

// 3. Jumlah berdasarkan Agama
$where_agama = count($where_clauses) > 0 ? implode(" AND ", $where_clauses) . " AND w.agama IS NOT NULL AND w.agama != ''" : "w.agama IS NOT NULL AND w.agama != ''";
$query_agama = "SELECT w.agama, COUNT(*) as jumlah FROM warga w $join_sql WHERE $where_agama GROUP BY w.agama ORDER BY jumlah DESC";
$result_agama = mysqli_query($conn, $query_agama);

$agama_labels = [];
$agama_data = [];

while ($row = mysqli_fetch_assoc($result_agama)) {
    $agama_labels[] = $row['agama'];
    $agama_data[] = (int) $row['jumlah'];
}

// 4. Jumlah berdasarkan Pekerjaan (Top 5)
$where_pekerjaan = count($where_clauses) > 0 ? implode(" AND ", $where_clauses) . " AND w.pekerjaan IS NOT NULL AND w.pekerjaan != ''" : "w.pekerjaan IS NOT NULL AND w.pekerjaan != ''";
$query_pekerjaan = "SELECT w.pekerjaan, COUNT(*) as jumlah FROM warga w $join_sql WHERE $where_pekerjaan GROUP BY w.pekerjaan ORDER BY jumlah DESC LIMIT 5";
$result_pekerjaan = mysqli_query($conn, $query_pekerjaan);

$pekerjaan_labels = [];
$pekerjaan_data = [];

while ($row = mysqli_fetch_assoc($result_pekerjaan)) {
    $pekerjaan_labels[] = $row['pekerjaan'];
    $pekerjaan_data[] = (int) $row['jumlah'];
}

// 5. Statistik Kelompok Usia
$where_usia = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query_usia = "
    SELECT 
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) < 18 THEN 1 ELSE 0 END) as anak,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as dewasa,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia
    FROM warga w
    $join_sql
    $where_usia
";
$result_usia = mysqli_query($conn, $query_usia);
$usia_data = mysqli_fetch_assoc($result_usia);

// 6. Jumlah Ibu Hamil (0 = tidak hamil, 1 = hamil)
$where_hamil = count($where_clauses) > 0 ? implode(" AND ", $where_clauses) . " AND w.status_kehamilan = 1" : "w.status_kehamilan = 1";
$query_hamil = "SELECT COUNT(*) AS jumlah_hamil FROM warga w $join_sql WHERE $where_hamil";
$result_hamil = mysqli_query($conn, $query_hamil);
$jumlah_hamil = mysqli_fetch_assoc($result_hamil)['jumlah_hamil'];

// ==================== AJAX RESPONSE ====================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $response = [
        'total_warga' => (int) $total_warga,
        'jumlah_hamil' => (int) $jumlah_hamil,
        'total_kk' => (int) $total_kk,
        'gender' => [
            'labels' => $gender_labels,
            'data' => $gender_data
        ],
        'usia' => [
            'anak' => (int) ($usia_data['anak'] ?? 0),
            'dewasa' => (int) ($usia_data['dewasa'] ?? 0),
            'lansia' => (int) ($usia_data['lansia'] ?? 0)
        ],
        'agama' => [
            'labels' => $agama_labels,
            'data' => $agama_data
        ],
        'pekerjaan' => [
            'labels' => $pekerjaan_labels,
            'data' => $pekerjaan_data
        ]
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - SIGAP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link href="output.css" rel="stylesheet">

    <!-- Font Awesome (optional) -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

<div class="flex flex-col md:flex-row min-h-screen">

    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-gradient-to-b from-rose-600 to-rose-400 text-white flex flex-col">
        
        <!-- Logo -->
        <div class="flex items-center justify-center py-4">
            <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" class="w-40 h-auto">
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-4 py-6 space-y-2">

            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded bg-rose-800">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>

            <a href="input_data.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-user-edit"></i>
                <span>Input Data Penduduk</span>
            </a>

            <a href="peta_sebaran.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-map-marker-alt"></i>
                <span>Peta Sebaran Evakuasi</span>
            </a>

            <!-- Logout -->
            <a href="logout.php"
               onclick="return confirm('Yakin ingin logout?');"
               class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>

        </nav>
    </aside>

    <!-- Content -->
    <main class="flex-1 p-4 md:p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-semibold text-gray-800">
                Selamat Datang di SIGAP DESA GMLS!
            </h1>
            <button id="btnContributors" type="button" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 transition shadow">
                <i class="fas fa-users"></i> Kontributor
            </button>
        </div>

        <!-- Contributors Modal -->
        <div id="contributorsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6 relative pt-10" style="position:relative;">
                <button id="closeContributorsModal" type="button" class="absolute top-4 right-4 text-gray-400 hover:text-rose-600 transition" style="position:absolute; top:1rem; right:1rem; left:auto;">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <h6 class="font-bold text-rose-600 text-lg flex items-center gap-2 mb-4">
                    <i class="fas fa-users"></i> Kontributor
                </h6>
                <ul class="space-y-2 text-sm text-gray-700 max-h-96 overflow-y-auto">
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">Anis Faisal Reza</span> - Direktur GMLS
                    </li>
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">Fernando Agustino Hutahaean</span> - Mahasiswa FTI Batch 4
                    </li>
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">Alfreando Moza Siagian</span> - Mahasiswa FTI Batch 4
                    </li>
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">Abigail Tesalonika</span> - Mahasiswa FTI Batch 4
                    </li>
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">F Adiwidya Wirawan J</span> - Mahasiswa FTI Batch 5
                    </li>
                    <li class="border-b border-gray-100 pb-2">
                        <span class="font-semibold">Rocky</span> - Mahasiswa FTI Batch 5
                    </li>
                    <li class="pb-2">
                        <span class="font-semibold">Yoel Beny Christian</span> - Mahasiswa FTI Batch 5
                    </li>
                </ul>
            </div>
        </div>

        <!-- ==================== VISUAL DATA CODES ==================== -->
        <style>
            body {
                font-family: 'Mozilla Text', sans-serif;
            }

            .chart-container {
                position: relative;
                height: 300px;
                margin-bottom: 20px;
            }

            .stats-box {
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .stats-box h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin: 0;
                line-height: 1;
            }

            .stats-box-1 {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .stats-box-2 {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            }

            .stats-box-3 {
                background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            }
        </style>

        <!-- Filter Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
            <div class="border-b border-gray-100 pb-3 mb-4 flex justify-between items-center">
                <h6 class="m-0 font-bold text-rose-600 flex items-center gap-2 text-lg">
                    <i class="fas fa-filter"></i> Filter Data Visualisasi
                </h6>
                <button id="btnToggleFilter" class="text-sm text-gray-500 hover:text-rose-600 transition flex items-center gap-1 focus:outline-none">
                    <span id="toggleText">Sembunyikan</span> <i id="toggleIcon" class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div id="filterFields" class="transition-all duration-300">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <!-- Provinsi -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Provinsi</label>
                        <select id="filterProvinsi" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none">
                            <option value="">Semua Provinsi</option>
                            <?php
                            $q = $conn->query("SELECT * FROM provinsi ORDER BY nama_provinsi");
                            while ($p = $q->fetch_assoc()) {
                                echo "<option value='{$p['id_provinsi']}'>{$p['nama_provinsi']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Kabupaten -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Kabupaten</label>
                        <select id="filterKabupaten" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none" disabled>
                            <option value="">Semua Kabupaten</option>
                        </select>
                    </div>
                    <!-- Kecamatan -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                        <select id="filterKecamatan" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none" disabled>
                            <option value="">Semua Kecamatan</option>
                        </select>
                    </div>
                    <!-- Desa -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Desa</label>
                        <select id="filterDesa" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none" disabled>
                            <option value="">Semua Desa</option>
                        </select>
                    </div>
                    <!-- RT -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">RT</label>
                        <input type="text" id="filterRT" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none" placeholder="Contoh: 001">
                    </div>
                    <!-- RW -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">RW</label>
                        <input type="text" id="filterRW" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none" placeholder="Contoh: 002">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                    <button id="btnResetFilter" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition text-sm font-semibold flex items-center gap-2 focus:outline-none">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button id="btnApplyFilter" class="px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition text-sm font-semibold shadow-md shadow-rose-200 flex items-center gap-2 focus:outline-none">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Total Warga dan Ibu Hamil -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stats-box stats-box-1">
                <h2 id="statTotalWarga"><?php echo number_format($total_warga); ?></h2>
                <p class="mb-0 flex items-center justify-center gap-2 mt-2"><i class="fas fa-users"></i> Total Warga Terdaftar</p>
            </div>
            <div class="stats-box stats-box-2">
                <h2 id="statIbuHamil"><?php echo number_format($jumlah_hamil); ?></h2>
                <p class="mb-0 flex items-center justify-center gap-2 mt-2"><i class="fas fa-female"></i> Ibu Hamil</p>
            </div>
            <div class="stats-box stats-box-3">
                <h2 id="statTotalKK"><?php echo number_format($total_kk); ?></h2>
                <p class="mb-0 flex items-center justify-center gap-2 mt-2"><i class="fas fa-home"></i> KK Terdaftar</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Chart Jenis Kelamin -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="border-b border-gray-100 pb-3 mb-4">
                    <h6 class="m-0 font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-venus-mars"></i> Berdasarkan Jenis Kelamin
                    </h6>
                </div>
                <div class="chart-container">
                    <canvas id="chartGender"></canvas>
                </div>
            </div>

            <!-- Chart Kelompok Usia -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="border-b border-gray-100 pb-3 mb-4">
                    <h6 class="m-0 font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-users"></i> Berdasarkan Kelompok Usia
                    </h6>
                </div>
                <div class="chart-container">
                    <canvas id="chartUsia"></canvas>
                </div>
            </div>

            <!-- Chart Agama -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="border-b border-gray-100 pb-3 mb-4">
                    <h6 class="m-0 font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-praying-hands"></i> Berdasarkan Agama
                    </h6>
                </div>
                <div class="chart-container">
                    <canvas id="chartAgama"></canvas>
                </div>
            </div>

            <!-- Chart Top 5 Pekerjaan -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="border-b border-gray-100 pb-3 mb-4">
                    <h6 class="m-0 font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-briefcase"></i> Top 5 Pekerjaan
                    </h6>
                </div>
                <div class="chart-container">
                    <canvas id="chartPekerjaan"></canvas>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Declare chart instances globally
            let chartGender, chartUsia, chartAgama, chartPekerjaan;

            $(document).ready(function() {
                // ==================== CHART 1: JENIS KELAMIN ====================
                const ctxGender = document.getElementById('chartGender');
                chartGender = new Chart(ctxGender, {
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
                chartUsia = new Chart(ctxUsia, {
                    type: 'bar',
                    data: {
                        labels: ['Anak-anak (<18 tahun)', 'Dewasa (18-59 tahun)', 'Lansia (≥60 tahun)'],
                        datasets: [{
                            label: 'Jumlah',
                            data: [
                                <?php echo $usia_data['anak'] ?? 0; ?>,
                                <?php echo $usia_data['dewasa'] ?? 0; ?>,
                                <?php echo $usia_data['lansia'] ?? 0; ?>
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
                chartAgama = new Chart(ctxAgama, {
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
                chartPekerjaan = new Chart(ctxPekerjaan, {
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

                // ==================== CONTRIBUTORS MODAL ====================
                $('#btnContributors').click(function() {
                    $('#contributorsModal').removeClass('hidden').addClass('flex');
                });
                $('#closeContributorsModal').click(function() {
                    $('#contributorsModal').addClass('hidden').removeClass('flex');
                });
                $('#contributorsModal').click(function(e) {
                    if (e.target === this) {
                        $('#contributorsModal').addClass('hidden').removeClass('flex');
                    }
                });

                // ==================== FILTER PANEL INTERACTION ====================
                $('#btnToggleFilter').click(function() {
                    $('#filterFields').slideToggle(300, function() {
                        const isVisible = $(this).is(':visible');
                        $('#toggleText').text(isVisible ? 'Sembunyikan' : 'Tampilkan');
                        $('#toggleIcon').toggleClass('fa-chevron-up fa-chevron-down');
                    });
                });

                // Cascading Dropdowns
                $('#filterProvinsi').change(function() {
                    const provId = $(this).val();
                    $('#filterKabupaten').html('<option value="">Semua Kabupaten</option>').prop('disabled', true);
                    $('#filterKecamatan').html('<option value="">Semua Kecamatan</option>').prop('disabled', true);
                    $('#filterDesa').html('<option value="">Semua Desa</option>').prop('disabled', true);

                    if (provId) {
                        $.get('get_kabupaten.php?provinsi_id=' + provId, function(data) {
                            try {
                                const kab = JSON.parse(data);
                                if (kab.length > 0) {
                                    $('#filterKabupaten').prop('disabled', false);
                                    kab.forEach(k => {
                                        $('#filterKabupaten').append(`<option value="${k.id_kabupaten}">${k.nama_kabupaten}</option>`);
                                    });
                                }
                            } catch (e) { console.error("Error parsing kabupaten", e); }
                        });
                    }
                });

                $('#filterKabupaten').change(function() {
                    const kabId = $(this).val();
                    $('#filterKecamatan').html('<option value="">Semua Kecamatan</option>').prop('disabled', true);
                    $('#filterDesa').html('<option value="">Semua Desa</option>').prop('disabled', true);

                    if (kabId) {
                        $.get('get_kecamatan.php?kabupaten_id=' + kabId, function(data) {
                            try {
                                const kec = JSON.parse(data);
                                if (kec.length > 0) {
                                    $('#filterKecamatan').prop('disabled', false);
                                    kec.forEach(k => {
                                        $('#filterKecamatan').append(`<option value="${k.id_kecamatan}">${k.nama_kecamatan}</option>`);
                                    });
                                }
                            } catch (e) { console.error("Error parsing kecamatan", e); }
                        });
                    }
                });

                $('#filterKecamatan').change(function() {
                    const kecId = $(this).val();
                    $('#filterDesa').html('<option value="">Semua Desa</option>').prop('disabled', true);

                    if (kecId) {
                        $.get('get_desa.php?kecamatan_id=' + kecId, function(data) {
                            try {
                                const des = JSON.parse(data);
                                if (des.length > 0) {
                                    $('#filterDesa').prop('disabled', false);
                                    des.forEach(d => {
                                        $('#filterDesa').append(`<option value="${d.id_desa}">${d.nama_desa}</option>`);
                                    });
                                }
                            } catch (e) { console.error("Error parsing desa", e); }
                        });
                    }
                });

                // Terapkan Filter Button
                $('#btnApplyFilter').click(function() {
                    const params = {
                        ajax: 1,
                        provinsi_id: $('#filterProvinsi').val(),
                        kabupaten_id: $('#filterKabupaten').val(),
                        kecamatan_id: $('#filterKecamatan').val(),
                        desa_id: $('#filterDesa').val(),
                        rt: $('#filterRT').val(),
                        rw: $('#filterRW').val()
                    };

                    // Add loading indicator
                    const origHtml = $('#btnApplyFilter').html();
                    $('#btnApplyFilter').html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...').prop('disabled', true);

                    $.get('index.php', params, function(response) {
                        $('#btnApplyFilter').html(origHtml).prop('disabled', false);

                        // Update stats boxes
                        $('#statTotalWarga').text(new Intl.NumberFormat().format(response.total_warga));
                        $('#statIbuHamil').text(new Intl.NumberFormat().format(response.jumlah_hamil));
                        $('#statTotalKK').text(new Intl.NumberFormat().format(response.total_kk));

                        // Update Gender Chart
                        chartGender.data.labels = response.gender.labels;
                        chartGender.data.datasets[0].data = response.gender.data;
                        chartGender.update();

                        // Update Usia Chart
                        chartUsia.data.datasets[0].data = [
                            response.usia.anak,
                            response.usia.dewasa,
                            response.usia.lansia
                        ];
                        chartUsia.update();

                        // Update Agama Chart
                        chartAgama.data.labels = response.agama.labels;
                        chartAgama.data.datasets[0].data = response.agama.data;
                        chartAgama.update();

                        // Update Pekerjaan Chart
                        chartPekerjaan.data.labels = response.pekerjaan.labels;
                        chartPekerjaan.data.datasets[0].data = response.pekerjaan.data;
                        chartPekerjaan.update();
                    }).fail(function() {
                        $('#btnApplyFilter').html(origHtml).prop('disabled', false);
                        alert('Gagal memproses filter. Silakan coba lagi.');
                    });
                });

                // Reset Filter Button
                $('#btnResetFilter').click(function() {
                    $('#filterProvinsi').val('');
                    $('#filterKabupaten').html('<option value="">Semua Kabupaten</option>').prop('disabled', true);
                    $('#filterKecamatan').html('<option value="">Semua Kecamatan</option>').prop('disabled', true);
                    $('#filterDesa').html('<option value="">Semua Desa</option>').prop('disabled', true);
                    $('#filterRT').val('');
                    $('#filterRW').val('');
                    
                    // Trigger reload of default unfiltered data
                    $('#btnApplyFilter').trigger('click');
                });
            });
        </script>
        <!-- ==================== END OF VISUAL DATA CODES ==================== -->
    </main>

</div>

</body>
</html>