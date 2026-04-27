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

<body id="page-top">
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Input Data Penduduk</h1>
                    <a href="daftar_warga.php" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-list fa-sm text-white-50"></i> Lihat Daftar Warga
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
                    <div class="form-section">
                        <h5><i class="fas fa-id-card"></i> Data Identitas</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>NIK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nik" maxlength="16" pattern="\d{16}"
                                    required placeholder="16 digit NIK">
                                <small class="form-text text-muted">Harus 16 digit angka</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_lengkap" required
                                    placeholder="Sesuai KTP">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Tempat Lahir</label>
                                <select class="form-control select2" name="tempat_lahir" required>
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <?php 
                                    mysqli_data_seek($kota_result, 0);
                                    while ($kota = mysqli_fetch_assoc($kota_result)): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($kota['nama_kabupaten']); ?>">
                                            <?php echo htmlspecialchars($kota['nama_kabupaten']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Tanggal Lahir</label>
                                <input type="date" class="form-control" name="tanggal_lahir">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-control" name="jenis_kelamin" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Data Pribadi -->
                    <div class="form-section">
                        <h5><i class="fas fa-user"></i> Data Pribadi</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Golongan Darah <span class="info-badge">Medis</span></label>
                                <select class="form-control" name="golongan_darah">
                                    <option value="">-- Pilih --</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                    <option value="-">Tidak Tahu</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Agama</label>
                                <select class="form-control" name="agama">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen">Kristen</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Status Perkawinan</label>
                                <select class="form-control" name="status_perkawinan">
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
                    <div class="form-section">
                        <h5><i class="fas fa-phone"></i> Data Kontak & Pekerjaan</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Nomor Telepon <span class="info-badge">Notifikasi</span></label>
                                <input type="tel" class="form-control" name="nomor_telepon" placeholder="08xxxxxxxxxx">
                                <small class="form-text text-muted">Penting untuk notifikasi bencana</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Pekerjaan</label>
                                <select class="form-control" name="pekerjaan" required>
                                    <option value="">-- Pilih Pekerjaan --</option>
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
                            <div class="col-md-4 mb-3">
                                <label>Jenis Penghasilan</label>
                                <select class="form-control" name="jenis_penghasilan">
                                    <option value="">-- Pilih --</option>
                                    <option value="Tetap">Tetap (Gaji Bulanan)</option>
                                    <option value="Tidak Tetap">Tidak Tetap (Harian/Proyek)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label>Status Domisili</label>
                                <select class="form-control" name="status_domisili">
                                    <option value="Menetap">Menetap (Tinggal di Desa)</option>
                                    <option value="Merantau">Merantau (KTP Desa, Kerja di Luar)</option>
                                    <option value="Pendatang">Pendatang (Non-KTP Desa)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kesehatan & Kondisi Khusus -->
                    <div class="form-section">
                        <h5><i class="fas fa-heartbeat"></i> Data Kesehatan & Kondisi Khusus</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="status_kehamilan"
                                        name="status_kehamilan" onchange="togglePerkiraanLahir()">
                                    <label class="custom-control-label" for="status_kehamilan">
                                        Sedang Hamil <span class="info-badge">Prioritas Evakuasi</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="perkiraan_lahir_group" style="display:none;">
                                <label>Perkiraan Tanggal Lahir (HPL)</label>
                                <input type="date" class="form-control" name="perkiraan_lahir">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Kategori Disabilitas <span class="info-badge">Asesmen Evakuasi</span></label>
                                <select class="form-control" name="kategori_disabilitas" id="kategori_disabilitas"
                                    onchange="toggleKeteranganDisabilitas()">
                                    <option value="Tidak Ada">Tidak Ada</option>
                                    <option value="Fisik">Fisik (Mobilitas Terbatas)</option>
                                    <option value="Intelektual">Intelektual</option>
                                    <option value="Mental">Mental</option>
                                    <option value="Sensorik">Sensorik (Pendengaran/Penglihatan)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="keterangan_disabilitas_group" style="display:none;">
                                <label>Keterangan Disabilitas</label>
                                <textarea class="form-control" name="keterangan_disabilitas" rows="2"
                                    placeholder="Contoh: Menggunakan kursi roda, Tuna rungu, dll"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kartu Keluarga (BARU) -->
                    <div class="form-section">
                        <h5><i class="fas fa-home"></i> Data Kartu Keluarga (KK)</h5>
                        <div class="kk-info-box">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Informasi:</strong> Masukkan nomor KK dan tentukan apakah warga ini adalah kepala keluarga.
                            Jika nomor KK sudah ada di sistem, warga akan ditambahkan ke KK tersebut.
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Nomor Kartu Keluarga (KK) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_kk" id="nomor_kk"
                                       maxlength="16" pattern="\d{16}" required placeholder="16 digit Nomor KK">
                                <small class="form-text text-muted">Harus 16 digit angka</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_kepala_keluarga"
                                           name="is_kepala_keluarga">
                                    <label class="custom-control-label" for="is_kepala_keluarga">
                                        <strong>Warga ini adalah Kepala Keluarga</strong>
                                        <span class="info-badge">Kepala KK</span>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-user-tie"></i> Centang jika warga ini kepala dari KK yang diinput
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Data Alamat -->
                    <div class="form-section">
                        <h5><i class="fas fa-map-marker-alt"></i> Data Alamat</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label>Alamat Lengkap</label>
                                <textarea class="form-control" name="alamat_lengkap" rows="2"
                                    placeholder="Contoh: Jl. Mawar No.12"></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Provinsi</label>
                                <select class="form-control" name="provinsi" id="provinsi" required>
                                    <option value="">-- Pilih Provinsi --</option>
                                    <?php 
                                    mysqli_data_seek($provinsi_result, 0);
                                    while ($p = mysqli_fetch_assoc($provinsi_result)): 
                                    ?>
                                        <option value="<?php echo $p['id_provinsi']; ?>">
                                            <?php echo $p['nama_provinsi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Kabupaten / Kota</label>
                                <select class="form-control" name="kabupaten" id="kabupaten" required>
                                    <option value="">-- Pilih Kabupaten --</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Kecamatan</label>
                                <select class="form-control" name="kecamatan" id="kecamatan" required>
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Desa</label>
                                <select class="form-control" name="desa" id="desa">
                                    <option value="">-- Pilih Desa --</option>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>RT</label>
                                <select class="form-control" name="id_rt">
                                    <option value="">-- RT --</option>
                                    <?php 
                                    mysqli_data_seek($rt_result, 0);
                                    while ($r = mysqli_fetch_assoc($rt_result)): 
                                    ?>
                                        <option value="<?php echo $r['id_rt']; ?>">
                                            RT <?php echo htmlspecialchars($r['nomor_rt']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>RW</label>
                                <select class="form-control" name="id_rw">
                                    <option value="">-- RW --</option>
                                    <?php 
                                    mysqli_data_seek($rw_result, 0);
                                    while ($rw = mysqli_fetch_assoc($rw_result)): 
                                    ?>
                                        <option value="<?php echo $rw['id_rw']; ?>">
                                            RW <?php echo htmlspecialchars($rw['nomor_rw']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Jenis Konstruksi</label>
                                <select class="form-control" name="jenis_konstruksi">
                                    <option value="">-- Pilih --</option>
                                    <option value="Kayu">Kayu</option>
                                    <option value="Semi Permanen">Semi Permanen</option>
                                    <option value="Permanen">Permanen</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Kerawanan Bencana</label>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Tsunami" id="chkTsunami">
                                    <label class="form-check-label" for="chkTsunami">Tsunami</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Longsor" id="chkLongsor">
                                    <label class="form-check-label" for="chkLongsor">Longsor</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Banjir" id="chkBanjir">
                                    <label class="form-check-label" for="chkBanjir">Banjir</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Pergerakan Tanah" id="chkPergerakanTanah">
                                    <label class="form-check-label" for="chkPergerakanTanah">Pergerakan Tanah</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Gunung Meletus" id="chkGunungMeletus">
                                    <label class="form-check-label" for="chkGunungMeletus">Gunung Meletus</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Kekeringan" id="chkKekeringan">
                                    <label class="form-check-label" for="chkKekeringan">Kekeringan</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="status_zona_tsunami[]"
                                        value="Puting Beliung" id="chkPutingBeliung">
                                    <label class="form-check-label" for="chkPutingBeliung">Puting Beliung</label>
                                </div>
                            </div>

                            <!-- Titik Koordinat -->
                            <div class="col-md-12 mb-3 mt-3">
                                <label class="fw-semibold">Titik Koordinat Lokasi Rumah</label>

                                <!-- Search Box -->
                                <input type="text" id="searchBox" class="form-control mb-2" placeholder="Cari alamat...">
                                <div id="suggestions"
                                    style="position:absolute; z-index:9999; background:white; width:100%; border:1px solid #ccc; display:none;">
                                </div>

                                <!-- MAP -->
                                <div id="map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ccc;">
                                </div>

                                <!-- Latitude & Longitude -->
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label>Latitude</label>
                                        <input type="text" id="latitude" name="latitude" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Longitude</label>
                                        <input type="text" id="longitude" name="longitude" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="text-right">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data Penduduk
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

    <!-- Bootstrap & Plugins -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

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