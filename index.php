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

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-gradient-to-b from-rose-700 to-rose-500 text-white flex flex-col">
        
        <!-- Logo -->
        <div class="flex items-center justify-center py-4 border-b border-gray-700">
            <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" class="w-40 h-auto">
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-4 py-6 space-y-2">

            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded bg-gray-800">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>

            <a href="input_data.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-user-edit"></i>
                <span>Input Data Penduduk</span>
            </a>

            <a href="visual_data.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-chart-bar"></i>
                <span>Visual Data</span>
            </a>

            <a href="titik_evakuasi.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-map-marker-alt"></i>
                <span>Titik Evakuasi</span>
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
    <main class="flex-1 p-6">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">
            Selamat Datang di SIGAP
        </h1>
        <p class="text-gray-600">
            Gunakan menu di sidebar untuk navigasi fitur SIGAP seperti input data penduduk, visualisasi data, dan titik evakuasi.
        </p>
    </main>

</div>

</body>
</html>