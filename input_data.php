<?php
include 'config.php';
session_start();

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ==================== FORM PROCESSING ====================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Data Identitas
    $nik = clean_input($_POST['nik']);
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $tempat_lahir = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? clean_input($_POST['tanggal_lahir']) : NULL;
    $jenis_kelamin = clean_input($_POST['jenis_kelamin']);

    // Data Pribadi
    $golongan_darah = !empty($_POST['golongan_darah']) ? clean_input($_POST['golongan_darah']) : NULL;
    $agama = !empty($_POST['agama']) ? clean_input($_POST['agama']) : NULL;
    $status_perkawinan = !empty($_POST['status_perkawinan']) ? clean_input($_POST['status_perkawinan']) : NULL;

    // Data Kontak & Pekerjaan
    $pekerjaan = !empty($_POST['pekerjaan']) ? clean_input($_POST['pekerjaan']) : NULL;
    $nomor_telepon = !empty($_POST['nomor_telepon']) ? clean_input($_POST['nomor_telepon']) : NULL;
    $jenis_penghasilan = !empty($_POST['jenis_penghasilan']) ? clean_input($_POST['jenis_penghasilan']) : NULL;
    $status_domisili = !empty($_POST['status_domisili']) ? clean_input($_POST['status_domisili']) : NULL;

    // Data Kesehatan
    $status_kehamilan = isset($_POST['status_kehamilan']) ? 1 : 0;
    $perkiraan_lahir = !empty($_POST['perkiraan_lahir']) ? clean_input($_POST['perkiraan_lahir']) : NULL;
    $kategori_disabilitas = !empty($_POST['kategori_disabilitas']) ? clean_input($_POST['kategori_disabilitas']) : 'Tidak Ada';
    $keterangan_disabilitas = !empty($_POST['keterangan_disabilitas']) ? clean_input($_POST['keterangan_disabilitas']) : NULL;

    // Data Keluarga (BARU)
    $nomor_kk = !empty($_POST['nomor_kk']) ? clean_input($_POST['nomor_kk']) : NULL;
    $is_kepala_keluarga = isset($_POST['is_kepala_keluarga']) ? 1 : 0;

    // Data Alamat
    $alamat_lengkap = !empty($_POST['alamat_lengkap']) ? clean_input($_POST['alamat_lengkap']) : NULL;
    $id_rt = !empty($_POST['id_rt']) ? (int) $_POST['id_rt'] : NULL;
    $id_rw = !empty($_POST['id_rw']) ? (int) $_POST['id_rw'] : NULL;
    $id_desa = !empty($_POST['desa']) ? (int) $_POST['desa'] : NULL;
    $id_kecamatan = !empty($_POST['kecamatan']) ? (int) $_POST['kecamatan'] : NULL;
    $id_kabupaten = !empty($_POST['kabupaten']) ? (int) $_POST['kabupaten'] : NULL;
    $id_provinsi = !empty($_POST['provinsi']) ? (int) $_POST['provinsi'] : NULL;

    // Data Rumah
    $jenis_konstruksi = !empty($_POST['jenis_konstruksi']) ? clean_input($_POST['jenis_konstruksi']) : NULL;
    $status_zona_tsunami = !empty($_POST['status_zona_tsunami'])
        ? clean_input(implode(', ', $_POST['status_zona_tsunami']))
        : NULL;

    $latitude = !empty($_POST['latitude']) ? clean_input($_POST['latitude']) : NULL;
    $longitude = !empty($_POST['longitude']) ? clean_input($_POST['longitude']) : NULL;

    // ==================== DATABASE TRANSACTION ====================
    mysqli_begin_transaction($conn);

    try {
        // 1. Insert Rumah
        $sql_rumah = "INSERT INTO rumah (
            alamat_lengkap, id_rt, id_rw, id_desa, id_kecamatan, id_kabupaten, id_provinsi, 
            jenis_konstruksi, status_zona_tsunami, latitude, longitude, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt_rumah = mysqli_prepare($conn, $sql_rumah);
        mysqli_stmt_bind_param(
            $stmt_rumah,
            "siiiiisssss",
            $alamat_lengkap, $id_rt, $id_rw, $id_desa, $id_kecamatan,
            $id_kabupaten, $id_provinsi, $jenis_konstruksi, $status_zona_tsunami,
            $latitude, $longitude
        );
        mysqli_stmt_execute($stmt_rumah);
        $id_rumah = mysqli_insert_id($conn);

        // 2. Cek atau buat KK baru
        $id_kk = NULL;
        if ($nomor_kk) {
            // Cek apakah nomor KK sudah ada
            $check_kk = mysqli_prepare($conn, "SELECT id_kk FROM keluarga WHERE nomor_kk = ?");
            mysqli_stmt_bind_param($check_kk, "s", $nomor_kk);
            mysqli_stmt_execute($check_kk);
            $result_kk = mysqli_stmt_get_result($check_kk);
            
            if ($row_kk = mysqli_fetch_assoc($result_kk)) {
                // KK sudah ada
                $id_kk = $row_kk['id_kk'];
            } else {
                // Buat KK baru (tanpa kepala keluarga dulu)
                $insert_kk = mysqli_prepare($conn, 
                    "INSERT INTO keluarga (nomor_kk, id_rumah, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
                );
                mysqli_stmt_bind_param($insert_kk, "si", $nomor_kk, $id_rumah);
                mysqli_stmt_execute($insert_kk);
                $id_kk = mysqli_insert_id($conn);
            }
        }

        // 3. Insert Warga
        $sql_warga = "INSERT INTO warga (
            nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, 
            golongan_darah, agama, status_perkawinan, pekerjaan, nomor_telepon,
            status_domisili, status_kehamilan, perkiraan_lahir, kategori_disabilitas,
            keterangan_disabilitas, jenis_penghasilan, nomor_kk, is_kepala_keluarga, id_kk,
            alamat_lengkap, id_provinsi, id_kabupaten, id_kecamatan, id_desa, id_rumah, 
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt_warga = mysqli_prepare($conn, $sql_warga);
        mysqli_stmt_bind_param(
            $stmt_warga,
            "sssssssssssissssiisiiiiii",
            $nik, $nama_lengkap, $tempat_lahir, $tanggal_lahir, $jenis_kelamin,
            $golongan_darah, $agama, $status_perkawinan, $pekerjaan, $nomor_telepon,
            $status_domisili, $status_kehamilan, $perkiraan_lahir, $kategori_disabilitas,
            $keterangan_disabilitas, $jenis_penghasilan, $nomor_kk, $is_kepala_keluarga, $id_kk,
            $alamat_lengkap, $id_provinsi, $id_kabupaten, $id_kecamatan, $id_desa, $id_rumah
        );
        mysqli_stmt_execute($stmt_warga);
        $id_warga = mysqli_insert_id($conn);

        // 4. Jika warga adalah kepala keluarga, update tabel keluarga
        if ($is_kepala_keluarga && $id_kk) {
            $update_kk = mysqli_prepare($conn, 
                "UPDATE keluarga SET id_kepala_keluarga = ?, id_rumah = ? WHERE id_kk = ?"
            );
            mysqli_stmt_bind_param($update_kk, "iii", $id_warga, $id_rumah, $id_kk);
            mysqli_stmt_execute($update_kk);
        }

        mysqli_commit($conn);
        $success_message = "Data penduduk berhasil disimpan!";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Gagal menyimpan data: " . $e->getMessage();
    }
}

// ==================== LOAD DATA FOR DROPDOWNS ====================
$kota_query = "SELECT * FROM kabupaten ORDER BY nama_kabupaten ASC";
$kota_result = mysqli_query($conn, $kota_query);

$provinsi_result = mysqli_query($conn, "SELECT * FROM provinsi ORDER BY nama_provinsi ASC");
$rt_result = mysqli_query($conn, "SELECT * FROM rt ORDER BY nomor_rt ASC");
$rw_result = mysqli_query($conn, "SELECT * FROM rw ORDER BY nomor_rw ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Input Data Penduduk - SIGAP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="./output.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        .form-section {
            background: #f8f9fc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-section h5 {
            color: #4e73df;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .info-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 5px;
        }

        .kk-info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex flex-col md:flex-row min-h-screen">

        <!-- ==================== SIDEBAR ==================== -->
        <aside class="w-full md:w-64 bg-gradient-to-b from-rose-600 to-rose-400 text-white flex flex-col">
    
            <!-- Logo -->
            <div class="flex items-center justify-center py-4">
                <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" class="w-40 h-auto">
            </div>

            <!-- Menu -->
            <nav class="flex-1 px-4 py-6 space-y-2">

                <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>

                <a href="input_data.php" class="flex items-center gap-3 px-3 py-2 rounded bg-rose-800">
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

                <a href="logout.php"
                onclick="return confirm('Yakin ingin logout?');"
                class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>

            </nav>
        </aside>

        <!-- ==================== CONTENT ==================== -->
        <main class="flex-1 p-4 md:p-6">

                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-3">

                    <!-- Title -->
                    <h1 class="text-2xl font-semibold text-gray-800">
                        Input Data Penduduk
                    </h1>

                    <!-- Button -->
                    <a href="daftar_warga.php"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-sm font-medium rounded hover:bg-rose-700 transition shadow-sm w-fit">

                        <i class="fas fa-list"></i>
                        <span>Lihat Daftar Warga</span>
                    </a>

                </div>

                <!-- Alert Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- ==================== FORM ==================== -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <!-- Data Identitas -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-id-card"></i> Data Identitas
                        </h5>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">NIK <span class="text-red-500">*</span></label>
                                <input type="text" name="nik" maxlength="16" pattern="\d{16}" required
                                    class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                    placeholder="16 digit NIK">
                                <p class="text-xs text-gray-500">Harus 16 digit angka</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_lengkap" required
                                    class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                    placeholder="Sesuai KTP">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium">Tempat Lahir</label>
                                <select name="tempat_lahir" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <?php while ($kota = mysqli_fetch_assoc($kota_result)): ?>
                                        <option value="<?= htmlspecialchars($kota['nama_kabupaten']); ?>">
                                            <?= htmlspecialchars($kota['nama_kabupaten']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir"
                                    class="w-full mt-1 p-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <select name="jenis_kelamin" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Data Pribadi -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-user"></i> Data Pribadi
                        </h5>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Golongan Darah</label>
                                <select name="golongan_darah" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                    <option value="-">Tidak Tahu</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Agama</label>
                                <select name="agama" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen">Kristen</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Status Perkawinan</label>
                                <select name="status_perkawinan" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Belum Kawin">Belum Kawin</option>
                                    <option value="Kawin">Kawin</option>
                                    <option value="Cerai Hidup">Cerai Hidup</option>
                                    <option value="Cerai Mati">Cerai Mati</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kontak & Pekerjaan -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-phone"></i> Data Kontak & Pekerjaan
                        </h5>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Nomor Telepon</label>
                                <input type="tel" name="nomor_telepon"
                                    class="w-full mt-1 p-2 border rounded"
                                    placeholder="08xxxxxxxxxx">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Pekerjaan</label>
                                <select name="pekerjaan" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Tidak Bekerja">Tidak Bekerja</option>
                                    <option value="Pelajar / Mahasiswa">Pelajar / Mahasiswa</option>
                                    <option value="PNS">PNS</option>
                                    <option value="TNI">TNI</option>
                                    <option value="POLRI">POLRI</option>
                                    <option value="Karyawan Swasta">Karyawan Swasta</option>
                                    <option value="Buruh Harian">Buruh Harian</option>
                                    <option value="Petani">Petani</option>
                                    <option value="Nelayan">Nelayan</option>
                                    <option value="Pedagang">Pedagang</option>
                                    <option value="Guru">Guru</option>
                                    <option value="Perawat">Perawat</option>
                                    <option value="Dokter">Dokter</option>
                                    <option value="Sopir">Sopir</option>
                                    <option value="Driver Online">Driver Online</option>
                                    <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Jenis Penghasilan</label>
                                <select name="jenis_penghasilan" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Tetap">Tetap</option>
                                    <option value="Tidak Tetap">Tidak Tetap</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium">Status Domisili</label>
                            <select name="status_domisili" class="w-full mt-1 p-2 border rounded">
                                <option value="Menetap">Menetap</option>
                                <option value="Merantau">Merantau</option>
                                <option value="Pendatang">Pendatang</option>
                            </select>
                        </div>
                    </div>

                    <!-- Data Kesehatan & Kondisi Khusus -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-heartbeat"></i> Data Kesehatan
                        </h5>

                        <div class="grid md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="status_kehamilan" id="status_kehamilan"
                                    class="accent-rose-500" onchange="togglePerkiraanLahir()">
                                Sedang Hamil
                            </label>

                            <div id="perkiraan_lahir_group" class="hidden">
                                <label class="block text-sm font-medium">Perkiraan Lahir</label>
                                <input type="date" name="perkiraan_lahir"
                                    class="w-full p-2 border rounded">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium">Kategori Disabilitas</label>
                                <select name="kategori_disabilitas" id="kategori_disabilitas"
                                    onchange="toggleKeteranganDisabilitas()"
                                    class="w-full p-2 border rounded">
                                    <option value="Tidak Ada">Tidak Ada</option>
                                    <option value="Fisik">Fisik (Mobilitas Terbatas)</option>
                                    <option value="Intelektual">Intelektual</option>
                                    <option value="Mental">Mental</option>
                                    <option value="Sensorik">Sensorik (Pendengaran/Penglihatan)</option>
                                </select>
                            </div>

                            <div id="keterangan_disabilitas_group" class="hidden">
                                <label class="block text-sm font-medium">Keterangan Disabilitas</label>
                                <textarea name="keterangan_disabilitas"
                                    class="w-full p-2 border rounded"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kartu Keluarga (BARU) -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-home"></i> Data Keluarga & Domisili
                        </h5>

                        <div class="grid md:grid-cols-2 gap-4">

                            <!-- NOMOR KK -->
                            <div>
                                <label class="block text-sm font-medium">Nomor Kartu Keluarga (KK)</label>
                                <select name="nomor_kk" id="nomor_kk"
                                    class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400">
                                    <option value="">-- Pilih KK (Jika Sudah Terdaftar) --</option>

                                    <?php
                                    // OPTIONAL: if you later pass existing KK list from DB
                                    // foreach ($kk_list as $kk):
                                    ?>
                                    <!-- <option value="<?= $kk['nomor_kk']; ?>"><?= $kk['nomor_kk']; ?></option> -->
                                    <?php // endforeach; ?>
                                </select>

                                <p class="text-xs text-gray-500 mt-1">
                                    Jika KK belum terdaftar, kosongkan saja. 
                                </p>
                            </div>

                            <!-- Kepala Keluarga -->
                            <div class="flex items-start mt-6">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <input type="checkbox"
                                        name="is_kepala_keluarga"
                                        class="accent-rose-500">
                                    Warga ini adalah Kepala Keluarga
                                </label>
                            </div>

                        </div>
                    </div>

                    <!-- Data Alamat -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt"></i> Data Alamat
                        </h5>

                        <!-- Alamat -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" rows="2"
                                class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                placeholder="Contoh: Jl. Mawar No.12"></textarea>
                        </div>

                        <!-- Wilayah -->
                        <div class="grid md:grid-cols-3 gap-4">
                            <!-- Provinsi -->
                            <div>
                                <label class="block text-sm font-medium">Provinsi</label>
                                <select name="provinsi" id="provinsi" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Provinsi --</option>
                                    <?php 
                                    mysqli_data_seek($provinsi_result, 0);
                                    while ($p = mysqli_fetch_assoc($provinsi_result)): 
                                    ?>
                                        <option value="<?= $p['id_provinsi']; ?>">
                                            <?= $p['nama_provinsi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Kabupaten -->
                            <div>
                                <label class="block text-sm font-medium">Kabupaten / Kota</label>
                                <select name="kabupaten" id="kabupaten" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kabupaten --</option>
                                </select>
                            </div>

                            <!-- Kecamatan -->
                            <div>
                                <label class="block text-sm font-medium">Kecamatan</label>
                                <select name="kecamatan" id="kecamatan" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Desa + RT RW + Konstruksi -->
                        <div class="grid md:grid-cols-4 gap-4 mt-4">
                            <!-- Desa -->
                            <div>
                                <label class="block text-sm font-medium">Desa</label>
                                <select name="desa" id="desa"
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Desa --</option>
                                </select>
                            </div>

                            <!-- RT -->
                            <div>
                                <label class="block text-sm font-medium">RT</label>
                                <select name="id_rt" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- RT --</option>
                                    <?php 
                                    mysqli_data_seek($rt_result, 0);
                                    while ($r = mysqli_fetch_assoc($rt_result)): 
                                    ?>
                                        <option value="<?= $r['id_rt']; ?>">
                                            RT <?= htmlspecialchars($r['nomor_rt']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- RW -->
                            <div>
                                <label class="block text-sm font-medium">RW</label>
                                <select name="id_rw" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- RW --</option>
                                    <?php 
                                    mysqli_data_seek($rw_result, 0);
                                    while ($rw = mysqli_fetch_assoc($rw_result)): 
                                    ?>
                                        <option value="<?= $rw['id_rw']; ?>">
                                            RW <?= htmlspecialchars($rw['nomor_rw']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Konstruksi -->
                            <div>
                                <label class="block text-sm font-medium">Jenis Konstruksi</label>
                                <select name="jenis_konstruksi"
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Kayu">Kayu</option>
                                    <option value="Semi Permanen">Semi Permanen</option>
                                    <option value="Permanen">Permanen</option>
                                </select>
                            </div>
                        </div>

                        <!-- Kerawanan Bencana -->
                        <div class="mt-6">
                            <label class="block text-sm font-semibold mb-2">Kerawanan Bencana</label>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Tsunami" class="accent-rose-500">
                                    Tsunami
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Longsor" class="accent-rose-500">
                                    Longsor
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Banjir" class="accent-rose-500">
                                    Banjir
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Pergerakan Tanah" class="accent-rose-500">
                                    Pergerakan Tanah
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Gunung Meletus" class="accent-rose-500">
                                    Gunung Meletus
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Kekeringan" class="accent-rose-500">
                                    Kekeringan
                                </label>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="status_zona_tsunami[]" value="Puting Beliung" class="accent-rose-500">
                                    Puting Beliung
                                </label>
                            </div>
                        </div>

                        <!-- Koordinat + Map -->
                        <div class="mt-6">
                            <label class="block text-sm font-semibold mb-2">Titik Koordinat Lokasi Rumah</label>

                            <!-- Search -->
                            <input type="text" id="searchBox"
                                class="w-full p-2 border rounded mb-2"
                                placeholder="Cari alamat...">

                            <div id="suggestions"
                                class="absolute z-50 bg-white w-full border rounded shadow hidden">
                            </div>

                            <!-- Map -->
                            <div id="map"
                                class="h-72 w-full rounded border mt-2">
                            </div>

                            <!-- Lat Long -->
                            <div class="grid md:grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-sm">Latitude</label>
                                    <input type="text" id="latitude" name="latitude"
                                        class="w-full p-2 border rounded bg-gray-100" readonly>
                                </div>

                                <div>
                                    <label class="block text-sm">Longitude</label>
                                    <input type="text" id="longitude" name="longitude"
                                        class="w-full p-2 border rounded bg-gray-100" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-3">
                        <button type="reset"
                            class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                            Reset
                        </button>

                        <button type="submit"
                            class="px-4 py-2 bg-rose-600 text-white rounded hover:bg-rose-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== SCRIPTS ==================== -->
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- jQuery -->
    <script src="vendor/jquery/jquery.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // ==================== INITIALIZE SELECT2 ====================
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });
        });

        // ==================== TOGGLE FUNCTIONS ====================
        function togglePerkiraanLahir() {
            const checkbox = document.getElementById('status_kehamilan');
            const group = document.getElementById('perkiraan_lahir_group');
            const input = group.querySelector('input');

            if (checkbox.checked) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                input.value = '';
            }
        }

        function toggleKeteranganDisabilitas() {
            const select = document.getElementById('kategori_disabilitas');
            const group = document.getElementById('keterangan_disabilitas_group');
            const textarea = group.querySelector('textarea');

            if (select.value !== 'Tidak Ada' && select.value !== '') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                textarea.value = '';
            }
        }

        // ==================== INPUT FORMATTING ====================
        $(document).ready(function () {
            // Format Nomor Telepon
            $('input[name="nomor_telepon"]').on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 0 && value[0] !== '0') {
                    value = '0' + value;
                }
                $(this).val(value);
            });

            // Format NIK (hanya angka)
            $('input[name="nik"]').on('input', function () {
                const value = $(this).val().replace(/\D/g, '');
                $(this).val(value);
            });

            // Format Nomor KK (hanya angka)
            $('input[name="nomor_kk"]').on('input', function () {
                const value = $(this).val().replace(/\D/g, '');
                $(this).val(value);
            });
        });

        // ==================== CASCADING DROPDOWN (WILAYAH) ====================
        $(document).ready(function () {
            // Load Kabupaten when Provinsi changes
            $('#provinsi').change(function () {
                const provId = $(this).val();

                $('#kabupaten').html('<option value="">Loading...</option>');
                $('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>');
                $('#desa').html('<option value="">-- Pilih Desa --</option>');

                if (provId) {
                    $.get('get_kabupaten.php', { provinsi_id: provId }, function (data) {
                        const kabupaten = JSON.parse(data);
                        let html = '<option value="">-- Pilih Kabupaten --</option>';

                        kabupaten.forEach(function (item) {
                            html += `<option value="${item.id_kabupaten}">${item.nama_kabupaten}</option>`;
                        });

                        $('#kabupaten').html(html);
                    });
                }
            });

            // Load Kecamatan when Kabupaten changes
            $('#kabupaten').change(function () {
                const kabId = $(this).val();

                $('#kecamatan').html('<option value="">Loading...</option>');
                $('#desa').html('<option value="">-- Pilih Desa --</option>');

                if (kabId) {
                    $.get('get_kecamatan.php', { kabupaten_id: kabId }, function (data) {
                        const kecamatan = JSON.parse(data);
                        let html = '<option value="">-- Pilih Kecamatan --</option>';

                        kecamatan.forEach(function (item) {
                            html += `<option value="${item.id_kecamatan}">${item.nama_kecamatan}</option>`;
                        });

                        $('#kecamatan').html(html);
                    });
                }
            });

            // Load Desa when Kecamatan changes
            $('#kecamatan').change(function () {
                const kecId = $(this).val();

                $('#desa').html('<option value="">Loading...</option>');

                if (kecId) {
                    $.get('get_desa.php', { kecamatan_id: kecId }, function (data) {
                        const desa = JSON.parse(data);
                        let html = '<option value="">-- Pilih Desa --</option>';

                        desa.forEach(function (item) {
                            html += `<option value="${item.id_desa}">${item.nama_desa}</option>`;
                        });

                        $('#desa').html(html);
                    });
                }
            });
        });

        // ==================== LEAFLET MAP ====================
        // Init map
        var map = L.map('map').setView([-6.967, 106.333], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        var marker = L.marker([-6.967, 106.333], { draggable: true }).addTo(map);

        // Update input saat marker digeser
        marker.on("dragend", function () {
            let pos = marker.getLatLng();
            document.getElementById("latitude").value = pos.lat;
            document.getElementById("longitude").value = pos.lng;
        });

        // Klik map → update marker
        map.on("click", function (e) {
            marker.setLatLng(e.latlng);
            document.getElementById("latitude").value = e.latlng.lat;
            document.getElementById("longitude").value = e.latlng.lng;
        });

        // ==================== AUTOCOMPLETE SEARCH ====================
        const searchBox = document.getElementById("searchBox");
        const suggestions = document.getElementById("suggestions");
        let timeout = null;

        // ketika mengetik
        searchBox.addEventListener("input", function () {
            clearTimeout(timeout);

            let query = this.value.trim();
            if (query.length < 3) {
                suggestions.style.display = "none";
                return;
            }

            timeout = setTimeout(() => {
                fetch(`https://photon.komoot.io/api/?q=${query}`)
                    .then(res => res.json())
                    .then(data => {
                        let results = data.features;

                        if (results.length === 0) {
                            suggestions.style.display = "none";
                            return;
                        }

                        let html = "";
                        results.slice(0, 8).forEach(item => {
                            let name = item.properties.name || "Tanpa Nama";
                            let city = item.properties.city || "";
                            let country = item.properties.country || "";
                            let lat = item.geometry.coordinates[1];
                            let lon = item.geometry.coordinates[0];

                            html += `
                                <div class="p-2 suggestion-item" 
                                    style="cursor:pointer; border-bottom:1px solid #eee;"
                                    data-lat="${lat}"
                                    data-lon="${lon}">
                                    <strong>${name}</strong><br>
                                    <small>${city}, ${country}</small>
                                </div>
                            `;
                        });

                        suggestions.innerHTML = html;
                        suggestions.style.display = "block";

                        // Klik suggestion
                        document.querySelectorAll(".suggestion-item").forEach(item => {
                            item.addEventListener("click", function () {
                                let lat = this.getAttribute("data-lat");
                                let lon = this.getAttribute("data-lon");

                                // Update map
                                marker.setLatLng([lat, lon]);
                                map.setView([lat, lon], 17);

                                // Update input
                                document.getElementById("latitude").value = lat;
                                document.getElementById("longitude").value = lon;

                                // Isi searchBox
                                searchBox.value = this.innerText;

                                // Tutup suggestion
                                suggestions.style.display = "none";
                            });
                        });

                    });
            }, 400); // delay 400ms
        });

        // Tutup suggestions jika klik di luar
        document.addEventListener("click", function (e) {
            if (!searchBox.contains(e.target)) {
                suggestions.style.display = "none";
            }
        });
    </script>
</body>
</html>