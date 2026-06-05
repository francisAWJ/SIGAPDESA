<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$selected_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$warga_result = mysqli_query($conn, "SELECT id_warga, nik, nama_lengkap FROM warga ORDER BY nama_lengkap ASC");
$report = null;

if ($selected_id) {
    $sql = "SELECT w.*, r.alamat_lengkap AS alamat_rumah, r.jenis_konstruksi, r.status_zona_tsunami, r.latitude, r.longitude,
            d.nama_desa, c.nama_kecamatan, kb.nama_kabupaten, p.nama_provinsi,
            rt.nomor_rt, rw.nomor_rw
        FROM warga w
        LEFT JOIN rumah r ON w.id_rumah = r.id_rumah
        LEFT JOIN desa d ON w.id_desa = d.id_desa
        LEFT JOIN kecamatan c ON w.id_kecamatan = c.id_kecamatan
        LEFT JOIN kabupaten kb ON w.id_kabupaten = kb.id_kabupaten
        LEFT JOIN provinsi p ON w.id_provinsi = p.id_provinsi
        LEFT JOIN rt ON r.id_rt = rt.id_rt
        LEFT JOIN rw ON r.id_rw = rw.id_rw
        WHERE w.id_warga = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $selected_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $report = mysqli_fetch_assoc($result);
}

function display_field($value, $default = '-') {
    if ($value === null || $value === '') {
        return $default;
    }
    return htmlspecialchars($value);
}

function format_gender($value) {
    if ($value === 'L') return 'Laki-laki';
    if ($value === 'P') return 'Perempuan';
    return '-';
}

function to_yesno($value) {
    return $value ? 'Ya' : 'Tidak';
}

function format_date($value) {
    if (empty($value) || $value === '0000-00-00') {
        return '-';
    }
    return htmlspecialchars(format_tanggal($value));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Darurat Gempa - SIGAP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">
    <link href="output.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Mozilla Text', sans-serif;
            background-color: #f8fafc;
        }

        .report-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .report-section {
            margin-bottom: 1.75rem;
        }

        .report-section h2 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: #b91c1c;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .report-row {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .report-label {
            font-size: 0.85rem;
            color: #475569;
        }

        .report-value {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .report-action {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .report-action button,
        .report-action a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 9999px;
            padding: 0.9rem 1.25rem;
            font-weight: 600;
            text-decoration: none;
        }

        .report-action button {
            background: #dc2626;
            color: #fff;
            border: none;
        }

        .report-action a {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .report-action button:hover,
        .report-action a:hover {
            opacity: 0.95;
        }

        .report-highlight {
            background: #fef2f2;
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid #fecaca;
        }

        .report-highlight p {
            margin: 0;
            line-height: 1.75;
            color: #991b1b;
            font-weight: 600;
        }

        @media print {
            .report-action,
            .top-actions {
                display: none;
            }

            body {
                background: #fff;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
<div class="flex flex-col md:flex-row min-h-screen">

    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-gradient-to-b from-rose-600 to-rose-400 text-white flex flex-col">
        <div class="flex items-center justify-center py-4">
            <img src="img/gmls_logo.png" alt="Logo SIGAP DESA" class="w-40 h-auto">
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
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
            <a href="report.php" class="flex items-center gap-3 px-3 py-2 rounded bg-rose-800">
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

    <main class="flex-1 p-4 md:p-6">
        <div class="mx-auto min-h-screen px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900">Laporan Darurat Gempa</h1>
                    <p class="mt-2 text-sm text-slate-600">Gunakan laporan ini ketika merespons bencana gempa. Informasi ditarik langsung dari data penduduk.</p>
                </div>
                <div class="report-action top-actions">
                    <a href="input_data.php" class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-rose-700">
                        <i class="fas fa-plus"></i>
                        Input Data Penduduk
                    </a>
                    <a href="daftar_warga.php" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 border border-slate-200 shadow-sm hover:bg-slate-50">
                        <i class="fas fa-list"></i>
                        Daftar Warga
                    </a>
                </div>
            </div>

            <div class="report-card">
        <div class="report-section">
            <form method="GET" action="report.php" class="grid gap-4 md:grid-cols-3 items-end">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Pilih Warga</label>
                    <select name="id" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100" onchange="this.form.submit()">
                        <option value="">-- Pilih nama warga --</option>
                        <?php while ($row = mysqli_fetch_assoc($warga_result)) : ?>
                            <option value="<?= $row['id_warga']; ?>" <?= $selected_id == $row['id_warga'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($row['nama_lengkap'] . ' (' . $row['nik'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow hover:bg-rose-700">
                        <i class="fas fa-print"></i>
                        Cetak Laporan
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_id && !$report) : ?>
            <div class="report-highlight">
                <p>Data warga tidak ditemukan. Pastikan Anda memilih warga yang valid.</p>
            </div>
        <?php endif; ?>

        <?php if ($report) : ?>
            <div class="report-section report-highlight">
                <p>Gunakan laporan ini untuk informasi evakuasi dan bantuan darurat saat gempa.</p>
            </div>

            <div class="report-section">
                <h2>Data Pribadi & Kontak</h2>
                <div class="report-grid">
                    <div class="report-row"><span class="report-label">NIK</span><span class="report-value"><?= display_field($report['nik']); ?></span></div>
                    <div class="report-row"><span class="report-label">Nama Lengkap</span><span class="report-value"><?= display_field($report['nama_lengkap']); ?></span></div>
                    <div class="report-row"><span class="report-label">Tempat, Tanggal Lahir</span><span class="report-value"><?= display_field($report['tempat_lahir']); ?>, <?= format_date($report['tanggal_lahir']); ?></span></div>
                    <div class="report-row"><span class="report-label">Jenis Kelamin</span><span class="report-value"><?= format_gender($report['jenis_kelamin']); ?></span></div>
                    <div class="report-row"><span class="report-label">Golongan Darah</span><span class="report-value"><?= display_field($report['golongan_darah']); ?></span></div>
                    <div class="report-row"><span class="report-label">Agama</span><span class="report-value"><?= display_field($report['agama']); ?></span></div>
                    <div class="report-row"><span class="report-label">Status Perkawinan</span><span class="report-value"><?= display_field($report['status_perkawinan']); ?></span></div>
                    <div class="report-row"><span class="report-label">Nomor Telepon</span><span class="report-value"><?= display_field($report['nomor_telepon']); ?></span></div>
                    <div class="report-row"><span class="report-label">Pekerjaan</span><span class="report-value"><?= display_field($report['pekerjaan']); ?></span></div>
                    <div class="report-row"><span class="report-label">Status Domisili</span><span class="report-value"><?= display_field($report['status_domisili']); ?></span></div>
                </div>
            </div>

            <div class="report-section">
                <h2>Informasi Kesehatan</h2>
                <div class="report-grid">
                    <div class="report-row"><span class="report-label">Sedang Hamil</span><span class="report-value"><?= to_yesno($report['status_kehamilan']); ?></span></div>
                    <div class="report-row"><span class="report-label">Perkiraan Lahir</span><span class="report-value"><?= format_date($report['perkiraan_lahir']); ?></span></div>
                    <div class="report-row"><span class="report-label">Disabilitas</span><span class="report-value"><?= display_field($report['kategori_disabilitas']); ?></span></div>
                    <div class="report-row"><span class="report-label">Keterangan Disabilitas</span><span class="report-value"><?= display_field($report['keterangan_disabilitas']); ?></span></div>
                </div>
            </div>

            <div class="report-section">
                <h2>Alamat & Rumah</h2>
                <div class="report-grid">
                    <div class="report-row"><span class="report-label">Alamat Lengkap</span><span class="report-value"><?= display_field($report['alamat_rumah']); ?></span></div>
                    <div class="report-row"><span class="report-label">RT/RW</span><span class="report-value">RT <?= display_field($report['nomor_rt']); ?> / RW <?= display_field($report['nomor_rw']); ?></span></div>
                    <div class="report-row"><span class="report-label">Desa</span><span class="report-value"><?= display_field($report['nama_desa']); ?></span></div>
                    <div class="report-row"><span class="report-label">Kecamatan</span><span class="report-value"><?= display_field($report['nama_kecamatan']); ?></span></div>
                    <div class="report-row"><span class="report-label">Kabupaten</span><span class="report-value"><?= display_field($report['nama_kabupaten']); ?></span></div>
                    <div class="report-row"><span class="report-label">Provinsi</span><span class="report-value"><?= display_field($report['nama_provinsi']); ?></span></div>
                    <div class="report-row"><span class="report-label">Jenis Konstruksi</span><span class="report-value"><?= display_field($report['jenis_konstruksi']); ?></span></div>
                    <div class="report-row"><span class="report-label">Zona Tsunami</span><span class="report-value"><?= display_field($report['status_zona_tsunami']); ?></span></div>
                    <div class="report-row"><span class="report-label">Latitude / Longitude</span><span class="report-value"><?= display_field($report['latitude']); ?> / <?= display_field($report['longitude']); ?></span></div>
                </div>
            </div>

            <div class="report-section">
                <h2>Detail Keluarga & Identitas</h2>
                <div class="report-grid">
                    <div class="report-row"><span class="report-label">Nomor KK</span><span class="report-value"><?= display_field($report['nomor_kk']); ?></span></div>
                    <div class="report-row"><span class="report-label">Kepala Keluarga</span><span class="report-value"><?= to_yesno($report['is_kepala_keluarga']); ?></span></div>
                </div>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>

</html>
