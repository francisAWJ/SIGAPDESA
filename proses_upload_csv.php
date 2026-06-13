<?php
include 'config.php';
session_start();

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// ==================== VALIDATE REQUEST ====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

$file = $_FILES['csv_file'];

// File upload error check
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupload file.']);
    exit;
}

// Validate extension and MIME
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$mime = mime_content_type($file['tmp_name']);
$allowed_mimes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];

if ($ext !== 'csv' || !in_array($mime, $allowed_mimes)) {
    echo json_encode(['success' => false, 'message' => 'File harus berformat CSV.']);
    exit;
}

// ==================== DEFAULT VALUES ====================
// Used when a CSV column is empty / null
$defaults = [
    'tempat_lahir'           => 'Tidak Diketahui',
    'tanggal_lahir'          => NULL,
    'jenis_kelamin'          => 'Laki-laki',
    'golongan_darah'         => '-',          // "Tidak Tahu"
    'agama'                  => 'Islam',
    'status_perkawinan'      => 'Belum Kawin',
    'pekerjaan'              => 'Belum/Tidak Bekerja',
    'nomor_telepon'          => NULL,
    'jenis_penghasilan'      => 'Tidak Tetap',
    'status_domisili'        => 'Menetap',
    'status_kehamilan'       => 0,
    'perkiraan_lahir'        => NULL,
    'kategori_disabilitas'   => 'Tidak Ada',
    'keterangan_disabilitas' => NULL,
    'nomor_kk'               => NULL,
    'is_kepala_keluarga'     => 0,
    'alamat_lengkap'         => NULL,
    'id_rt'                  => NULL,
    'id_rw'                  => NULL,
    'id_desa'                => NULL,
    'id_kecamatan'           => NULL,
    'id_kabupaten'           => NULL,
    'id_provinsi'            => NULL,
    'jenis_konstruksi'       => NULL,
    'status_zona_tsunami'    => NULL,
    'latitude'               => NULL,
    'longitude'              => NULL,
];

// ==================== HELPER: sanitize a single value ====================
function sanitize_csv_value($value) {
    return trim(stripslashes(htmlspecialchars(strip_tags($value))));
}

function csv_val($row, $key, $defaults) {
    if (!isset($row[$key]) || trim($row[$key]) === '') {
        return $defaults[$key] ?? NULL;
    }
    return sanitize_csv_value($row[$key]);
}

// ==================== LOOKUP HELPERS (name → ID) ====================
// Each function queries by name and returns the integer ID, or NULL if not found.
// $conn is global.

function lookup_provinsi($conn, $name) {
    if (empty($name)) return NULL;
    $stmt = mysqli_prepare($conn, "SELECT id_provinsi FROM provinsi WHERE LOWER(nama_provinsi) = LOWER(?) LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_provinsi'] : NULL;
}

function lookup_kabupaten($conn, $name, $id_provinsi = NULL) {
    if (empty($name)) return NULL;
    if ($id_provinsi) {
        $stmt = mysqli_prepare($conn,
            "SELECT id_kabupaten FROM kabupaten WHERE LOWER(nama_kabupaten) = LOWER(?) AND id_provinsi = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $name, $id_provinsi);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id_kabupaten FROM kabupaten WHERE LOWER(nama_kabupaten) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $name);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_kabupaten'] : NULL;
}

function lookup_kecamatan($conn, $name, $id_kabupaten = NULL) {
    if (empty($name)) return NULL;
    if ($id_kabupaten) {
        $stmt = mysqli_prepare($conn,
            "SELECT id_kecamatan FROM kecamatan WHERE LOWER(nama_kecamatan) = LOWER(?) AND id_kabupaten = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $name, $id_kabupaten);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id_kecamatan FROM kecamatan WHERE LOWER(nama_kecamatan) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $name);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_kecamatan'] : NULL;
}

function lookup_desa($conn, $name, $id_kecamatan = NULL) {
    if (empty($name)) return NULL;
    if ($id_kecamatan) {
        $stmt = mysqli_prepare($conn,
            "SELECT id_desa FROM desa WHERE LOWER(nama_desa) = LOWER(?) AND id_kecamatan = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $name, $id_kecamatan);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id_desa FROM desa WHERE LOWER(nama_desa) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $name);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_desa'] : NULL;
}

function lookup_rt($conn, $nomor, $id_rw = NULL) {
    if (empty($nomor)) return NULL;
    // Strip leading zeros for flexible matching: "01" matches "1"
    $nomor_clean = ltrim($nomor, '0') ?: '0';
    if ($id_rw) {
        $stmt = mysqli_prepare($conn,
            "SELECT id_rt FROM rt WHERE (nomor_rt = ? OR nomor_rt = ?) AND id_rw = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ssi', $nomor, $nomor_clean, $id_rw);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id_rt FROM rt WHERE nomor_rt = ? OR nomor_rt = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $nomor, $nomor_clean);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_rt'] : NULL;
}

function lookup_rw($conn, $nomor, $id_desa = NULL) {
    if (empty($nomor)) return NULL;
    $nomor_clean = ltrim($nomor, '0') ?: '0';
    if ($id_desa) {
        $stmt = mysqli_prepare($conn,
            "SELECT id_rw FROM rw WHERE (nomor_rw = ? OR nomor_rw = ?) AND id_desa = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ssi', $nomor, $nomor_clean, $id_desa);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id_rw FROM rw WHERE nomor_rw = ? OR nomor_rw = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $nomor, $nomor_clean);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_rw'] : NULL;
}

// ==================== PARSE CSV ====================
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat membaca file CSV.']);
    exit;
}

// Read header row
$header = fgetcsv($handle, 0, ',');
if (!$header) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'File CSV kosong atau header tidak ditemukan.']);
    exit;
}

// Normalize headers: trim and lowercase for flexible matching
$header = array_map(fn($h) => strtolower(trim($h)), $header);

// Required columns
$required_columns = ['nik', 'nama_lengkap'];
foreach ($required_columns as $col) {
    if (!in_array($col, $header)) {
        fclose($handle);
        echo json_encode([
            'success' => false,
            'message' => "Kolom wajib '$col' tidak ditemukan dalam CSV."
        ]);
        exit;
    }
}

// ==================== PROCESS ROWS ====================
$success_count = 0;
$error_rows    = [];
$row_number    = 1; // 1 = header

mysqli_begin_transaction($conn);

try {
    while (($raw = fgetcsv($handle, 0, ',')) !== false) {
        $row_number++;

        // Skip completely empty lines
        if (empty(array_filter($raw, fn($v) => trim($v) !== ''))) {
            continue;
        }

        // Map CSV row to associative array using header
        $row = array_combine($header, $raw);
        if ($row === false) {
            // Column count mismatch — pad with empty strings
            $raw = array_pad($raw, count($header), '');
            $row = array_combine($header, $raw);
        }

        // ---- Validate required fields ----
        $nik         = sanitize_csv_value($row['nik'] ?? '');
        $nama_lengkap = sanitize_csv_value($row['nama_lengkap'] ?? '');

        if (empty($nik) || empty($nama_lengkap)) {
            $error_rows[] = "Baris $row_number: NIK dan Nama Lengkap wajib diisi.";
            continue;
        }
        if (!preg_match('/^\d{16}$/', $nik)) {
            $error_rows[] = "Baris $row_number: NIK '$nik' harus 16 digit angka.";
            continue;
        }

        // ---- Check for duplicate NIK ----
        $check_nik = mysqli_prepare($conn, "SELECT id_warga FROM warga WHERE nik = ?");
        mysqli_stmt_bind_param($check_nik, 's', $nik);
        mysqli_stmt_execute($check_nik);
        mysqli_stmt_store_result($check_nik);
        if (mysqli_stmt_num_rows($check_nik) > 0) {
            mysqli_stmt_close($check_nik);
            $error_rows[] = "Baris $row_number: NIK '$nik' sudah terdaftar, dilewati.";
            continue;
        }
        mysqli_stmt_close($check_nik);

        // ---- Map all fields with defaults ----
        $tempat_lahir           = csv_val($row, 'tempat_lahir', $defaults);
        $tanggal_lahir          = csv_val($row, 'tanggal_lahir', $defaults);
        $jenis_kelamin          = csv_val($row, 'jenis_kelamin', $defaults);
        $golongan_darah         = csv_val($row, 'golongan_darah', $defaults);
        $agama                  = csv_val($row, 'agama', $defaults);
        $status_perkawinan      = csv_val($row, 'status_perkawinan', $defaults);
        $pekerjaan              = csv_val($row, 'pekerjaan', $defaults);
        $nomor_telepon          = csv_val($row, 'nomor_telepon', $defaults);
        $jenis_penghasilan      = csv_val($row, 'jenis_penghasilan', $defaults);
        $status_domisili        = csv_val($row, 'status_domisili', $defaults);
        $status_kehamilan       = !empty($row['status_kehamilan']) ? (int)$row['status_kehamilan'] : $defaults['status_kehamilan'];
        $perkiraan_lahir        = csv_val($row, 'perkiraan_lahir', $defaults);
        $kategori_disabilitas   = csv_val($row, 'kategori_disabilitas', $defaults);
        $keterangan_disabilitas = csv_val($row, 'keterangan_disabilitas', $defaults);
        $nomor_kk               = csv_val($row, 'nomor_kk', $defaults);
        $is_kepala_keluarga     = !empty($row['is_kepala_keluarga']) ? (int)$row['is_kepala_keluarga'] : $defaults['is_kepala_keluarga'];
        $alamat_lengkap         = csv_val($row, 'alamat_lengkap', $defaults);
        // ---- Resolve wilayah: id_* columns take priority; fall back to name-based lookup ----
        // 1. Provinsi
        if (!empty($row['id_provinsi']) && trim($row['id_provinsi']) !== '-') {
            // Menggunakan fungsi floatval lalu di-int agar format "43.0" bersih menjadi 43
            $id_provinsi = (int)floatval($row['id_provinsi']);
            if ($id_provinsi <= 0) $id_provinsi = NULL;
        } elseif (!empty($row['provinsi'])) {
            $id_provinsi = lookup_provinsi($conn, trim($row['provinsi']));
            if (!$id_provinsi) {
                $error_rows[] = "Baris $row_number: Provinsi '{$row['provinsi']}' tidak ditemukan, baris dilewati.";
                continue;
            }
        } else {
            $id_provinsi = $defaults['id_provinsi'];
        }

        // 2. Kabupaten
        if (!empty($row['id_kabupaten']) && trim($row['id_kabupaten']) !== '-') {
            $id_kabupaten = (int)floatval($row['id_kabupaten']);
            if ($id_kabupaten <= 0) $id_kabupaten = NULL;
        } elseif (!empty($row['kabupaten'])) {
            $id_kabupaten = lookup_kabupaten($conn, trim($row['kabupaten']), $id_provinsi);
            if (!$id_kabupaten) {
                $error_rows[] = "Baris $row_number: Kabupaten '{$row['kabupaten']}' tidak ditemukan, baris dilewati.";
                continue;
            }
        } else {
            $id_kabupaten = $defaults['id_kabupaten'];
        }

        // 3. Kecamatan
        if (!empty($row['id_kecamatan']) && trim($row['id_kecamatan']) !== '-') {
            $id_kecamatan = (int)floatval($row['id_kecamatan']);
            if ($id_kecamatan <= 0) $id_kecamatan = NULL;
        } elseif (!empty($row['kecamatan'])) {
            $id_kecamatan = lookup_kecamatan($conn, trim($row['kecamatan']), $id_kabupaten);
            if (!$id_kecamatan) {
                $error_rows[] = "Baris $row_number: Kecamatan '{$row['kecamatan']}' tidak ditemukan, baris dilewati.";
                continue;
            }
        } else {
            $id_kecamatan = $defaults['id_kecamatan'];
        }

        // 4. Desa
        if (!empty($row['id_desa']) && trim($row['id_desa']) !== '-') {
            $id_desa = (int)floatval($row['id_desa']);
            if ($id_desa <= 0) $id_desa = NULL;
        } elseif (!empty($row['desa'])) {
            $id_desa = lookup_desa($conn, trim($row['desa']), $id_kecamatan);
            if (!$id_desa) {
                $error_rows[] = "Baris $row_number: Desa '{$row['desa']}' tidak ditemukan, baris dilewati.";
                continue;
            }
        } else {
            $id_desa = $defaults['id_desa'];
        }

        // 5. RW
        if (!empty($row['id_rw']) && trim($row['id_rw']) !== '-') {
            $id_rw = (int)floatval($row['id_rw']);
            if ($id_rw <= 0) $id_rw = NULL;
        } elseif (!empty($row['rw'])) {
            $id_rw = lookup_rw($conn, trim($row['rw']), $id_desa);
            if (!$id_rw) {
                $error_rows[] = "Baris $row_number (peringatan): RW '{$row['rw']}' tidak ditemukan, diisi NULL.";
                $id_rw = NULL;
            }
        } else {
            $id_rw = $defaults['id_rw'];
        }

        // 6. RT
        if (!empty($row['id_rt']) && trim($row['id_rt']) !== '-') {
            $id_rt = (int)floatval($row['id_rt']);
            if ($id_rt <= 0) $id_rt = NULL;
        } elseif (!empty($row['rt'])) {
            $id_rt = lookup_rt($conn, trim($row['rt']), $id_rw);
            if (!$id_rt) {
                $error_rows[] = "Baris $row_number (peringatan): RT '{$row['rt']}' tidak ditemukan, diisi NULL.";
                $id_rt = NULL;
            }
        } else {
            $id_rt = $defaults['id_rt'];
        }
        $jenis_konstruksi       = csv_val($row, 'jenis_konstruksi', $defaults);
        $status_zona_tsunami    = csv_val($row, 'status_zona_tsunami', $defaults);
        $latitude               = csv_val($row, 'latitude', $defaults);
        $longitude              = csv_val($row, 'longitude', $defaults);

        // Validate tanggal_lahir format
        if ($tanggal_lahir !== NULL && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
            $tanggal_lahir = NULL; // reset invalid dates
        }
        // Same for perkiraan_lahir
        if ($perkiraan_lahir !== NULL && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $perkiraan_lahir)) {
            $perkiraan_lahir = NULL;
        }

        // ---- 1. Insert Rumah ----
        $sql_rumah = "INSERT INTO rumah (
            alamat_lengkap, id_rt, id_rw, id_desa, id_kecamatan, id_kabupaten, id_provinsi,
            jenis_konstruksi, status_zona_tsunami, latitude, longitude, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt_rumah = mysqli_prepare($conn, $sql_rumah);
        mysqli_stmt_bind_param(
            $stmt_rumah,
            'siiiiisssss',
            $alamat_lengkap, $id_rt, $id_rw, $id_desa, $id_kecamatan,
            $id_kabupaten, $id_provinsi, $jenis_konstruksi, $status_zona_tsunami,
            $latitude, $longitude
        );
        mysqli_stmt_execute($stmt_rumah);
        $id_rumah = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_rumah);

        // ---- 2. Handle KK ----
        $id_kk = NULL;
        if ($nomor_kk) {
            $check_kk = mysqli_prepare($conn, "SELECT id_kk FROM keluarga WHERE nomor_kk = ?");
            mysqli_stmt_bind_param($check_kk, 's', $nomor_kk);
            mysqli_stmt_execute($check_kk);
            $result_kk = mysqli_stmt_get_result($check_kk);

            if ($row_kk = mysqli_fetch_assoc($result_kk)) {
                $id_kk = $row_kk['id_kk'];
            } else {
                $insert_kk = mysqli_prepare($conn,
                    "INSERT INTO keluarga (nomor_kk, id_rumah, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
                );
                mysqli_stmt_bind_param($insert_kk, 'si', $nomor_kk, $id_rumah);
                mysqli_stmt_execute($insert_kk);
                $id_kk = mysqli_insert_id($conn);
                mysqli_stmt_close($insert_kk);
            }
            mysqli_stmt_close($check_kk);
        }

        // ---- 3. Insert Warga ----
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
            'sssssssssssissssiisiiiiii',
            $nik, $nama_lengkap, $tempat_lahir, $tanggal_lahir, $jenis_kelamin,
            $golongan_darah, $agama, $status_perkawinan, $pekerjaan, $nomor_telepon,
            $status_domisili, $status_kehamilan, $perkiraan_lahir, $kategori_disabilitas,
            $keterangan_disabilitas, $jenis_penghasilan, $nomor_kk, $is_kepala_keluarga, $id_kk,
            $alamat_lengkap, $id_provinsi, $id_kabupaten, $id_kecamatan, $id_desa, $id_rumah
        );
        mysqli_stmt_execute($stmt_warga);
        $id_warga = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_warga);

        // ---- 4. Update kepala keluarga ----
        if ($is_kepala_keluarga && $id_kk) {
            $update_kk = mysqli_prepare($conn,
                "UPDATE keluarga SET id_kepala_keluarga = ?, id_rumah = ? WHERE id_kk = ?"
            );
            mysqli_stmt_bind_param($update_kk, 'iii', $id_warga, $id_rumah, $id_kk);
            mysqli_stmt_execute($update_kk);
            mysqli_stmt_close($update_kk);
        }

        $success_count++;
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    fclose($handle);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    exit;
}

fclose($handle);

// ==================== RESPONSE ====================
$response = [
    'success'       => true,
    'inserted'      => $success_count,
    'errors'        => $error_rows,
    'error_count'   => count($error_rows),
    'message'       => "$success_count data berhasil diimport."
        . (count($error_rows) > 0 ? ' ' . count($error_rows) . ' baris dilewati.' : '')
];

header('Content-Type: application/json');
echo json_encode($response);