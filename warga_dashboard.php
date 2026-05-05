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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Warga - SIGAP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link href="output.css" rel="stylesheet">
    <!-- Font Awesome -->
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
            <a href="warga_dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded bg-rose-800">
                <i class="fas fa-user"></i>
                <span>Profil Saya</span>
            </a>
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
 
        <!-- Page Heading -->
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">
            Halo, <?= htmlspecialchars($warga['nama_lengkap']); ?> 👋
        </h1>
 
        <!-- Data Pribadi Card -->
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-rose-600 to-rose-400 px-5 py-3">
                <h6 class="text-white font-semibold">Data Pribadi</h6>
            </div>
            <div class="p-5">
                <form method="POST" class="space-y-4">
 
                    <!-- NIK (readonly) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                        <input type="text"
                               value="<?= htmlspecialchars($warga['nik']); ?>"
                               readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm cursor-not-allowed">
                    </div>
 
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text"
                               name="nama_lengkap"
                               value="<?= htmlspecialchars($warga['nama_lengkap']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent transition">
                    </div>
 
                    <!-- Nomor Telepon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                        <input type="text"
                               name="nomor_telepon"
                               value="<?= htmlspecialchars($warga['nomor_telepon']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent transition">
                    </div>
 
                    <!-- Pekerjaan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan</label>
                        <input type="text"
                               name="pekerjaan"
                               value="<?= htmlspecialchars($warga['pekerjaan']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent transition">
                    </div>
 
                    <!-- Buttons -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>Update Data
                        </button>
                        <a href="logout.php"
                           onclick="return confirm('Yakin ingin logout?');"
                           class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
 
                </form>
            </div>
        </div>
 
        <!-- Footer -->
        <p class="text-center text-gray-400 text-xs">© SIGAP Desa - <?= date('Y'); ?></p>
 
    </main>
</div>
 
</body>
</html>
