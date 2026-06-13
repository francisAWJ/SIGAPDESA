<?php
include 'config.php';
session_start();

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ==================== GET ID WARGA ====================
$id_warga = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_warga <= 0) {
    header("Location: daftar_warga.php");
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

    // Data Keluarga
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
        // 0. Ambil id_rumah dan id_kk lama dari warga
        $stmt_old = mysqli_prepare($conn, "SELECT id_rumah, id_kk FROM warga WHERE id_warga = ?");
        mysqli_stmt_bind_param($stmt_old, "i", $id_warga);
        mysqli_stmt_execute($stmt_old);
        $res_old = mysqli_stmt_get_result($stmt_old);
        $old = mysqli_fetch_assoc($res_old);

        $id_rumah = $old['id_rumah'];
        $id_kk_lama = $old['id_kk'];

        // 1. Update / Insert Rumah
        if ($id_rumah) {
            $sql_rumah = "UPDATE rumah SET
                alamat_lengkap = ?, id_rt = ?, id_rw = ?, id_desa = ?, id_kecamatan = ?,
                id_kabupaten = ?, id_provinsi = ?, jenis_konstruksi = ?, status_zona_tsunami = ?,
                latitude = ?, longitude = ?, updated_at = NOW()
                WHERE id_rumah = ?";

            $stmt_rumah = mysqli_prepare($conn, $sql_rumah);
            mysqli_stmt_bind_param(
                $stmt_rumah,
                "siiiiisssssi",
                $alamat_lengkap, $id_rt, $id_rw, $id_desa, $id_kecamatan,
                $id_kabupaten, $id_provinsi, $jenis_konstruksi, $status_zona_tsunami,
                $latitude, $longitude, $id_rumah
            );
            mysqli_stmt_execute($stmt_rumah);
        } else {
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
        }

        // 2. Cek atau buat KK
        $id_kk = NULL;
        if ($nomor_kk) {
            $check_kk = mysqli_prepare($conn, "SELECT id_kk FROM keluarga WHERE nomor_kk = ?");
            mysqli_stmt_bind_param($check_kk, "s", $nomor_kk);
            mysqli_stmt_execute($check_kk);
            $result_kk = mysqli_stmt_get_result($check_kk);

            if ($row_kk = mysqli_fetch_assoc($result_kk)) {
                // KK sudah ada
                $id_kk = $row_kk['id_kk'];
            } else {
                // Buat KK baru
                $insert_kk = mysqli_prepare($conn,
                    "INSERT INTO keluarga (nomor_kk, id_rumah, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
                );
                mysqli_stmt_bind_param($insert_kk, "si", $nomor_kk, $id_rumah);
                mysqli_stmt_execute($insert_kk);
                $id_kk = mysqli_insert_id($conn);
            }
        }

        // 3. Update Warga
        $sql_warga = "UPDATE warga SET
            nik = ?, nama_lengkap = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?,
            golongan_darah = ?, agama = ?, status_perkawinan = ?, pekerjaan = ?, nomor_telepon = ?,
            status_domisili = ?, status_kehamilan = ?, perkiraan_lahir = ?, kategori_disabilitas = ?,
            keterangan_disabilitas = ?, jenis_penghasilan = ?, nomor_kk = ?, is_kepala_keluarga = ?, id_kk = ?,
            alamat_lengkap = ?, id_provinsi = ?, id_kabupaten = ?, id_kecamatan = ?, id_desa = ?, id_rumah = ?,
            updated_at = NOW()
            WHERE id_warga = ?";

        $stmt_warga = mysqli_prepare($conn, $sql_warga);
        mysqli_stmt_bind_param(
            $stmt_warga,
            "sssssssssssissssiisiiiiiii",
            $nik, $nama_lengkap, $tempat_lahir, $tanggal_lahir, $jenis_kelamin,
            $golongan_darah, $agama, $status_perkawinan, $pekerjaan, $nomor_telepon,
            $status_domisili, $status_kehamilan, $perkiraan_lahir, $kategori_disabilitas,
            $keterangan_disabilitas, $jenis_penghasilan, $nomor_kk, $is_kepala_keluarga, $id_kk,
            $alamat_lengkap, $id_provinsi, $id_kabupaten, $id_kecamatan, $id_desa, $id_rumah,
            $id_warga
        );
        mysqli_stmt_execute($stmt_warga);

        // 4. Jika kepala keluarga dicentang, update tabel keluarga
        if ($is_kepala_keluarga && $id_kk) {
            $update_kk = mysqli_prepare($conn,
                "UPDATE keluarga SET id_kepala_keluarga = ?, id_rumah = ? WHERE id_kk = ?"
            );
            mysqli_stmt_bind_param($update_kk, "iii", $id_warga, $id_rumah, $id_kk);
            mysqli_stmt_execute($update_kk);
        } elseif (!$is_kepala_keluarga && $id_kk_lama && $id_kk_lama != $id_kk) {
            // Jika sebelumnya kepala keluarga dari KK lama, lepaskan status tsb
            $unset_kk = mysqli_prepare($conn,
                "UPDATE keluarga SET id_kepala_keluarga = NULL WHERE id_kk = ? AND id_kepala_keluarga = ?"
            );
            mysqli_stmt_bind_param($unset_kk, "ii", $id_kk_lama, $id_warga);
            mysqli_stmt_execute($unset_kk);
        }

        mysqli_commit($conn);
        $success_message = "Data penduduk berhasil diperbarui!";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Gagal memperbarui data: " . $e->getMessage();
    }
}

// ==================== LOAD DATA WARGA ====================
$sql_detail = "SELECT w.*, k.nomor_kk AS nomor_kk_kk, k.id_kepala_keluarga
               FROM warga w
               LEFT JOIN keluarga k ON w.id_kk = k.id_kk
               WHERE w.id_warga = ?";
$stmt_detail = mysqli_prepare($conn, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_warga);
mysqli_stmt_execute($stmt_detail);
$res_detail = mysqli_stmt_get_result($stmt_detail);
$warga = mysqli_fetch_assoc($res_detail);

if (!$warga) {
    header("Location: daftar_warga.php");
    exit;
}

// Recompute is_kepala_keluarga from latest DB state (in case of cross-checking)
$is_kk_checked = ($warga['is_kepala_keluarga'] == 1) ? true : false;

// ==================== LOAD DATA FOR DROPDOWNS ====================
$kota_query = "SELECT * FROM kabupaten ORDER BY nama_kabupaten ASC";
$kota_result = mysqli_query($conn, $kota_query);

$provinsi_result = mysqli_query($conn, "SELECT * FROM provinsi ORDER BY nama_provinsi ASC");
$rt_result = mysqli_query($conn, "SELECT * FROM rt ORDER BY nomor_rt ASC");
$rw_result = mysqli_query($conn, "SELECT * FROM rw ORDER BY nomor_rw ASC");

// ==================== LOAD WILAYAH UNTUK PRE-SELECT (KASKADING) ====================
$kabupaten_options = [];
if (!empty($warga['id_provinsi'])) {
    $stmt_kab = mysqli_prepare($conn, "SELECT id_kabupaten, nama_kabupaten FROM kabupaten WHERE id_provinsi = ? ORDER BY nama_kabupaten ASC");
    mysqli_stmt_bind_param($stmt_kab, "i", $warga['id_provinsi']);
    mysqli_stmt_execute($stmt_kab);
    $res_kab = mysqli_stmt_get_result($stmt_kab);
    while ($r = mysqli_fetch_assoc($res_kab)) $kabupaten_options[] = $r;
}

$kecamatan_options = [];
if (!empty($warga['id_kabupaten'])) {
    $stmt_kec = mysqli_prepare($conn, "SELECT id_kecamatan, nama_kecamatan FROM kecamatan WHERE id_kabupaten = ? ORDER BY nama_kecamatan ASC");
    mysqli_stmt_bind_param($stmt_kec, "i", $warga['id_kabupaten']);
    mysqli_stmt_execute($stmt_kec);
    $res_kec = mysqli_stmt_get_result($stmt_kec);
    while ($r = mysqli_fetch_assoc($res_kec)) $kecamatan_options[] = $r;
}

$desa_options = [];
if (!empty($warga['id_kecamatan'])) {
    $stmt_desa = mysqli_prepare($conn, "SELECT id_desa, nama_desa FROM desa WHERE id_kecamatan = ? ORDER BY nama_desa ASC");
    mysqli_stmt_bind_param($stmt_desa, "i", $warga['id_kecamatan']);
    mysqli_stmt_execute($stmt_desa);
    $res_desa = mysqli_stmt_get_result($stmt_desa);
    while ($r = mysqli_fetch_assoc($res_desa)) $desa_options[] = $r;
}

// ==================== LOAD DATA RUMAH (RT/RW/KONSTRUKSI/ZONA/KOORDINAT) ====================
$rumah = null;
if (!empty($warga['id_rumah'])) {
    $stmt_rumah_q = mysqli_prepare($conn, "SELECT * FROM rumah WHERE id_rumah = ?");
    mysqli_stmt_bind_param($stmt_rumah_q, "i", $warga['id_rumah']);
    mysqli_stmt_execute($stmt_rumah_q);
    $res_rumah = mysqli_stmt_get_result($stmt_rumah_q);
    $rumah = mysqli_fetch_assoc($res_rumah);
}

$selected_zona = $rumah && !empty($rumah['status_zona_tsunami'])
    ? array_map('trim', explode(',', $rumah['status_zona_tsunami']))
    : [];

$lat_value = $rumah['latitude'] ?? -6.967;
$lon_value = $rumah['longitude'] ?? 106.333;
$alamat_value = $rumah['alamat_lengkap'] ?? $warga['alamat_lengkap'];
$id_rt_value = $rumah['id_rt'] ?? null;
$id_rw_value = $rumah['id_rw'] ?? null;
$jenis_konstruksi_value = $rumah['jenis_konstruksi'] ?? null;

// Helper untuk select option
function selected_if($a, $b) {
    return ((string)$a === (string)$b) ? 'selected' : '';
}
function checked_if($cond) {
    return $cond ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Data Penduduk - SIGAP</title>

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
        body {
            font-family: 'Mozilla Text', sans-serif;
        }

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

                <a href="input_data.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                    <i class="fas fa-user-edit"></i>
                    <span>Input Data Penduduk</span>
                </a>

                <a href="peta_sebaran.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Peta Sebaran Evakuasi</span>
                </a>

                <a href="report.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                    <i class="fas fa-file-alt"></i>
                    <span>Buat Laporan</span>
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
                        Edit Data Penduduk
                    </h1>

                    <!-- Buttons -->
                    <div class="flex gap-2 flex-wrap">
                        <a href="daftar_warga.php"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-sm font-medium rounded hover:bg-rose-700 transition shadow-sm w-fit">
                            <i class="fas fa-arrow-left"></i>
                            <span>Kembali ke Daftar Warga</span>
                        </a>
                    </div>

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
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id_warga; ?>">

                    <!-- Data Identitas -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-id-card"></i> Data Identitas
                        </h5>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">NIK <span class="text-red-500">*</span></label>
                                <input type="text" name="nik" maxlength="16" pattern="\d{16}" required
                                    value="<?php echo htmlspecialchars($warga['nik']); ?>"
                                    class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                    placeholder="16 digit NIK">
                                <p class="text-xs text-gray-500">Harus 16 digit angka</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_lengkap" required
                                    value="<?php echo htmlspecialchars($warga['nama_lengkap']); ?>"
                                    class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                    placeholder="Sesuai KTP">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium">Tempat Lahir <span class="text-red-500">*</span></label>
                                <select name="tempat_lahir" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <?php
                                    mysqli_data_seek($kota_result, 0);
                                    while ($kota = mysqli_fetch_assoc($kota_result)):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($kota['nama_kabupaten']); ?>"
                                            <?php echo selected_if($kota['nama_kabupaten'], $warga['tempat_lahir']); ?>>
                                            <?php echo htmlspecialchars($kota['nama_kabupaten']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Tanggal Lahir <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_lahir" required
                                    value="<?php echo htmlspecialchars($warga['tanggal_lahir']); ?>"
                                    class="w-full mt-1 p-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <select name="jenis_kelamin" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="L" <?php echo selected_if('L', $warga['jenis_kelamin']); ?>>Laki-laki</option>
                                    <option value="P" <?php echo selected_if('P', $warga['jenis_kelamin']); ?>>Perempuan</option>
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
                                <label class="block text-sm font-medium">Golongan Darah <span class="text-red-500">*</span></label>
                                <select name="golongan_darah" class="w-full mt-1 p-2 border rounded" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="A" <?php echo selected_if('A', $warga['golongan_darah']); ?>>A</option>
                                    <option value="B" <?php echo selected_if('B', $warga['golongan_darah']); ?>>B</option>
                                    <option value="AB" <?php echo selected_if('AB', $warga['golongan_darah']); ?>>AB</option>
                                    <option value="O" <?php echo selected_if('O', $warga['golongan_darah']); ?>>O</option>
                                    <option value="-" <?php echo selected_if('-', $warga['golongan_darah']); ?>>Tidak Tahu</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Agama</label>
                                <select name="agama" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam" <?php echo selected_if('Islam', $warga['agama']); ?>>Islam</option>
                                    <option value="Kristen" <?php echo selected_if('Kristen', $warga['agama']); ?>>Kristen</option>
                                    <option value="Katolik" <?php echo selected_if('Katolik', $warga['agama']); ?>>Katolik</option>
                                    <option value="Hindu" <?php echo selected_if('Hindu', $warga['agama']); ?>>Hindu</option>
                                    <option value="Buddha" <?php echo selected_if('Buddha', $warga['agama']); ?>>Buddha</option>
                                    <option value="Konghucu" <?php echo selected_if('Konghucu', $warga['agama']); ?>>Konghucu</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Status Perkawinan</label>
                                <select name="status_perkawinan" class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Belum Kawin" <?php echo selected_if('Belum Kawin', $warga['status_perkawinan']); ?>>Belum Kawin</option>
                                    <option value="Kawin" <?php echo selected_if('Kawin', $warga['status_perkawinan']); ?>>Kawin</option>
                                    <option value="Cerai Hidup" <?php echo selected_if('Cerai Hidup', $warga['status_perkawinan']); ?>>Cerai Hidup</option>
                                    <option value="Cerai Mati" <?php echo selected_if('Cerai Mati', $warga['status_perkawinan']); ?>>Cerai Mati</option>
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
                                    value="<?php echo htmlspecialchars($warga['nomor_telepon'] ?? ''); ?>"
                                    class="w-full mt-1 p-2 border rounded"
                                    placeholder="08xxxxxxxxxx">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Pekerjaan <span class="text-red-500">*</span></label>
                                <select name="pekerjaan" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <?php
                                    $pekerjaan_options = [
                                        "Belum/Tidak Bekerja", "Pelajar/Mahasiswa", "Karyawan Honorer",
                                        "Pegawai Negeri Sipil", "Tentara Nasional Indonesia", "Kepolisian RI",
                                        "Karyawan Swasta", "Buruh Harian", "Perdagangan", "Petani/Pekebun",
                                        "Nelayan/Perikanan", "Penata Busana", "Guru", "Perawat", "Bidan",
                                        "Dokter", "Sopir", "Driver Online", "Mengurus Rumah Tangga",
                                        "Wiraswasta", "Pensiunan", "Lainnya"
                                    ];
                                    foreach ($pekerjaan_options as $opt):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($opt); ?>"
                                            <?php echo selected_if($opt, $warga['pekerjaan']); ?>>
                                            <?php echo htmlspecialchars($opt); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Jenis Penghasilan</label>
                                <select name="jenis_penghasilan" class="w-full mt-1 p-2 border rounded" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Tetap" <?php echo selected_if('Tetap', $warga['jenis_penghasilan']); ?>>Tetap</option>
                                    <option value="Tidak Tetap" <?php echo selected_if('Tidak Tetap', $warga['jenis_penghasilan']); ?>>Tidak Tetap</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium">Status Domisili <span class="text-red-500">*</span></label>
                            <select name="status_domisili" class="w-full mt-1 p-2 border rounded" required>
                                <option value="Menetap" <?php echo selected_if('Menetap', $warga['status_domisili']); ?>>Menetap</option>
                                <option value="Kerja di Luar" <?php echo selected_if('Kerja di Luar', $warga['status_domisili']); ?>>Kerja di Luar</option>
                                <option value="Pendatang" <?php echo selected_if('Pendatang', $warga['status_domisili']); ?>>Pendatang</option>
                                <option value="Tinggal Sementara" <?php echo selected_if('Tinggal Sementara', $warga['status_domisili']); ?>>Tinggal Sementara</option>
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
                                    class="accent-rose-500" onchange="togglePerkiraanLahir()"
                                    <?php echo checked_if($warga['status_kehamilan'] == 1); ?>>
                                Sedang Hamil
                            </label>

                            <div id="perkiraan_lahir_group" class="<?php echo $warga['status_kehamilan'] == 1 ? '' : 'hidden'; ?>">
                                <label class="block text-sm font-medium">Perkiraan Lahir</label>
                                <input type="date" name="perkiraan_lahir"
                                    value="<?php echo htmlspecialchars($warga['perkiraan_lahir'] ?? ''); ?>"
                                    class="w-full p-2 border rounded">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium">Kategori Disabilitas</label>
                                <select name="kategori_disabilitas" id="kategori_disabilitas"
                                    onchange="toggleKeteranganDisabilitas()"
                                    class="w-full p-2 border rounded">
                                    <option value="Tidak Ada" <?php echo selected_if('Tidak Ada', $warga['kategori_disabilitas']); ?>>Tidak Ada</option>
                                    <option value="Fisik" <?php echo selected_if('Fisik', $warga['kategori_disabilitas']); ?>>Fisik (Mobilitas Terbatas)</option>
                                    <option value="Intelektual" <?php echo selected_if('Intelektual', $warga['kategori_disabilitas']); ?>>Intelektual</option>
                                    <option value="Mental" <?php echo selected_if('Mental', $warga['kategori_disabilitas']); ?>>Mental</option>
                                    <option value="Sensorik" <?php echo selected_if('Sensorik', $warga['kategori_disabilitas']); ?>>Sensorik (Pendengaran/Penglihatan)</option>
                                </select>
                            </div>

                            <div id="keterangan_disabilitas_group" class="<?php echo (!empty($warga['kategori_disabilitas']) && $warga['kategori_disabilitas'] !== 'Tidak Ada') ? '' : 'hidden'; ?>">
                                <label class="block text-sm font-medium">Keterangan Disabilitas</label>
                                <textarea name="keterangan_disabilitas"
                                    class="w-full p-2 border rounded"><?php echo htmlspecialchars($warga['keterangan_disabilitas'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kartu Keluarga -->
                    <div class="bg-gray-50 p-5 rounded-lg mb-5">
                        <h5 class="text-rose-600 font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-home"></i> Data Keluarga & Domisili
                        </h5>

                        <div class="grid md:grid-cols-2 gap-4">

                            <!-- NOMOR KK -->
                            <div>
                                <label class="block text-sm font-medium">Nomor Kartu Keluarga (KK) <span class="text-red-500">*</span></label>
                                <input type="text" name="nomor_kk" id="nomor_kk"
                                    value="<?php echo htmlspecialchars($warga['nomor_kk'] ?? ''); ?>"
                                    class="w-full p-2 border rounded mb-2"
                                    maxlength="16" pattern="\d{16}" required placeholder="16 digit Nomor KK">

                                <p class="text-xs text-gray-500 mt-1">
                                    Harus 16 digit angka
                                </p>
                            </div>

                            <!-- Kepala Keluarga -->
                            <div class="flex items-start mt-6">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <input type="checkbox"
                                        id="is_kepala_keluarga"
                                        name="is_kepala_keluarga"
                                        class="accent-rose-500"
                                        <?php echo checked_if($is_kk_checked); ?>>
                                    Centang jika warga ini kepala dari KK yang diinput
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
                            <label class="block text-sm font-medium">Alamat Lengkap <span class="text-red-500">*</span></label>
                            <textarea name="alamat_lengkap" rows="2" required
                                class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-rose-400"
                                placeholder="Contoh: Jl. Mawar No.12"><?php echo htmlspecialchars($alamat_value ?? ''); ?></textarea>
                        </div>

                        <!-- Wilayah -->
                        <div class="grid md:grid-cols-3 gap-4">
                            <!-- Provinsi -->
                            <div>
                                <label class="block text-sm font-medium">Provinsi <span class="text-red-500">*</span></label>
                                <select name="provinsi" id="provinsi" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Provinsi --</option>
                                    <?php
                                    mysqli_data_seek($provinsi_result, 0);
                                    while ($p = mysqli_fetch_assoc($provinsi_result)):
                                    ?>
                                        <option value="<?= $p['id_provinsi']; ?>"
                                            <?php echo selected_if($p['id_provinsi'], $warga['id_provinsi']); ?>>
                                            <?= $p['nama_provinsi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Kabupaten -->
                            <div>
                                <label class="block text-sm font-medium">Kabupaten / Kota <span class="text-red-500">*</span></label>
                                <select name="kabupaten" id="kabupaten" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kabupaten --</option>
                                    <?php foreach ($kabupaten_options as $kab): ?>
                                        <option value="<?= $kab['id_kabupaten']; ?>"
                                            <?php echo selected_if($kab['id_kabupaten'], $warga['id_kabupaten']); ?>>
                                            <?= htmlspecialchars($kab['nama_kabupaten']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Kecamatan -->
                            <div>
                                <label class="block text-sm font-medium">Kecamatan <span class="text-red-500">*</span></label>
                                <select name="kecamatan" id="kecamatan" required
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatan_options as $kec): ?>
                                        <option value="<?= $kec['id_kecamatan']; ?>"
                                            <?php echo selected_if($kec['id_kecamatan'], $warga['id_kecamatan']); ?>>
                                            <?= htmlspecialchars($kec['nama_kecamatan']); ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                    <?php foreach ($desa_options as $desa): ?>
                                        <option value="<?= $desa['id_desa']; ?>"
                                            <?php echo selected_if($desa['id_desa'], $warga['id_desa']); ?>>
                                            <?= htmlspecialchars($desa['nama_desa']); ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                        <option value="<?php echo $r['id_rt']; ?>"
                                            <?php echo selected_if($r['id_rt'], $id_rt_value); ?>>
                                            RT <?php echo htmlspecialchars($r['nomor_rt']); ?>
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
                                        <option value="<?php echo $rw['id_rw']; ?>"
                                            <?php echo selected_if($rw['id_rw'], $id_rw_value); ?>>
                                            RW <?php echo htmlspecialchars($rw['nomor_rw']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Konstruksi -->
                            <div>
                                <label class="block text-sm font-medium">Jenis Konstruksi Dominan</label>
                                <select name="jenis_konstruksi"
                                    class="w-full mt-1 p-2 border rounded">
                                    <option value="">-- Pilih --</option>
                                    <option value="Kayu" <?php echo selected_if('Kayu', $jenis_konstruksi_value); ?>>Kayu</option>
                                    <option value="Beton" <?php echo selected_if('Beton', $jenis_konstruksi_value); ?>>Beton</option>
                                    <option value="Baja" <?php echo selected_if('Baja', $jenis_konstruksi_value); ?>>Baja</option>
                                    <option value="Batu Alam" <?php echo selected_if('Batu Alam', $jenis_konstruksi_value); ?>>Batu Alam</option>
                                    <option value="Bambu" <?php echo selected_if('Bambu', $jenis_konstruksi_value); ?>>Bambu</option>
                                </select>
                            </div>
                        </div>

                        <!-- Kerawanan Bencana -->
                        <div class="mt-6">
                            <label class="block text-sm font-semibold mb-2">Kerawanan Bencana</label>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <?php
                                $zona_options = ["Tsunami", "Longsor", "Banjir", "Pergerakan Tanah", "Gunung Meletus", "Kekeringan", "Puting Beliung"];
                                foreach ($zona_options as $zona):
                                ?>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="status_zona_tsunami[]" value="<?php echo $zona; ?>" class="accent-rose-500"
                                            <?php echo in_array($zona, $selected_zona) ? 'checked' : ''; ?>>
                                        <?php echo $zona; ?>
                                    </label>
                                <?php endforeach; ?>
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
                            <div id="map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ccc;">
                            </div>

                            <!-- Lat Long -->
                            <div class="grid md:grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-sm">Latitude</label>
                                    <input type="text" id="latitude" name="latitude"
                                        value="<?php echo htmlspecialchars($lat_value); ?>"
                                        class="w-full p-2 border rounded bg-gray-100" readonly>
                                </div>

                                <div>
                                    <label class="block text-sm">Longitude</label>
                                    <input type="text" id="longitude" name="longitude"
                                        value="<?php echo htmlspecialchars($lon_value); ?>"
                                        class="w-full p-2 border rounded bg-gray-100" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-3">
                        <a href="daftar_warga.php"
                            class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                            Batal
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-rose-600 text-white rounded hover:bg-rose-700">
                            Simpan Perubahan
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
        // Init map dengan koordinat data warga (jika ada)
        var initLat = <?php echo json_encode((float) $lat_value); ?>;
        var initLon = <?php echo json_encode((float) $lon_value); ?>;

        var map = L.map('map').setView([initLat, initLon], 17);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        var marker = L.marker([initLat, initLon], { draggable: true }).addTo(map);

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
