<?php
// ==========================================
// KONFIGURASI DATABASE & OTENTIKASI ADMIN
// ==========================================
include 'config.php';
session_start();

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$rumahData = [];
$dbError = null;

try {
    // Ambil data rumah + RT/RW/Desa + jumlah warga rentan secara terperinci.
    $sql = "
        SELECT
            r.id_rumah,
            r.alamat_lengkap,
            r.latitude,
            r.longitude,
            COALESCE(rt.nomor_rt, '') AS nomor_rt,
            COALESCE(rw.nomor_rw, '') AS nomor_rw,
            COALESCE(d.nama_desa, '') AS nama_desa,
            COUNT(DISTINCT w.id_warga) AS jumlah_warga,
            MIN(w.nama_lengkap) AS nama_warga,
            COALESCE(SUM(CASE
                WHEN w.tanggal_lahir IS NOT NULL
                AND TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60
                THEN 1 ELSE 0
            END), 0) AS jumlah_lansia,
            COALESCE(SUM(CASE
                WHEN w.tanggal_lahir IS NOT NULL
                AND TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) <= 5
                THEN 1 ELSE 0
            END), 0) AS jumlah_balita,
            COALESCE(SUM(CASE
                WHEN w.status_kehamilan = 1
                THEN 1 ELSE 0
            END), 0) AS jumlah_ibu_hamil,
            COALESCE(SUM(CASE
                WHEN w.kategori_disabilitas IS NOT NULL
                AND w.kategori_disabilitas <> 'Tidak Ada'
                THEN 1 ELSE 0
            END), 0) AS jumlah_disabilitas
        FROM rumah r
        LEFT JOIN rt ON r.id_rt = rt.id_rt
        LEFT JOIN rw ON r.id_rw = rw.id_rw
        LEFT JOIN desa d ON r.id_desa = d.id_desa
        LEFT JOIN warga w ON w.id_rumah = r.id_rumah
        WHERE r.latitude IS NOT NULL
          AND r.longitude IS NOT NULL
          AND TRIM(r.latitude) <> ''
          AND TRIM(r.longitude) <> ''
        GROUP BY
            r.id_rumah,
            r.alamat_lengkap,
            r.latitude,
            r.longitude,
            rt.nomor_rt,
            rw.nomor_rw,
            d.nama_desa
        ORDER BY rt.nomor_rt ASC, r.id_rumah ASC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $kelompok = [];

        if ((int)$row["jumlah_lansia"] > 0) {
            $kelompok[] = "lansia";
        }
        if ((int)$row["jumlah_balita"] > 0) {
            $kelompok[] = "balita";
        }
        if ((int)$row["jumlah_ibu_hamil"] > 0) {
            $kelompok[] = "ibu_hamil";
        }
        if ((int)$row["jumlah_disabilitas"] > 0) {
            $kelompok[] = "disabilitas";
        }

        $namaRumah = !empty($row["nama_warga"])
            ? "Kel. " . $row["nama_warga"]
            : "Rumah " . $row["id_rumah"];

        $rumahData[] = [
            "id" => (int)$row["id_rumah"],
            "nama" => $namaRumah,
            "alamat" => $row["alamat_lengkap"],
            "desa" => $row["nama_desa"],
            "rt" => "RT " . str_pad($row["nomor_rt"], 3, "0", STR_PAD_LEFT),
            "rw" => "RW " . str_pad($row["nomor_rw"], 3, "0", STR_PAD_LEFT),
            "lat" => (float)$row["latitude"],
            "lng" => (float)$row["longitude"],
            "kelompok" => $kelompok,
            "anggota" => (int)$row["jumlah_warga"],
            "detail_warga" => [
                "lansia" => (int)$row["jumlah_lansia"],
                "balita" => (int)$row["jumlah_balita"],
                "ibu_hamil" => (int)$row["jumlah_ibu_hamil"],
                "disabilitas" => (int)$row["jumlah_disabilitas"]
            ]
        ];
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Peta Sebaran Penduduk — SIGAP</title>
    
    <!-- Pustaka CSS Utama -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet"/>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="output.css" rel="stylesheet">

    <style>
        /* Map-specific styles */
        #map-section {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        #map {
            width: 100%;
            height: 100%;
        }

        /* SEARCH BAR PREMIUM (Glassmorphism) */
        #search-wrap {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            width: 340px;
            max-width: calc(100% - 40px);
        }
        #search-bar {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #search-bar:focus-within {
            border-color: #dc2626;
            box-shadow: 0 20px 25px -5px rgba(220, 38, 38, 0.15), 0 10px 10px -5px rgba(220, 38, 38, 0.1);
            transform: translateY(-1px);
        }
        #search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 13px;
            font-family: inherit;
            color: #1e293b;
            padding: 12px 0;
            background: transparent;
            font-weight: 500;
        }
        #search-input::placeholder {
            color: #94a3b8;
        }
        #search-icon-el {
            font-size: 14px;
            margin-right: 10px;
            color: #64748b;
        }
        #search-clear {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #64748b;
            padding: 4px;
            display: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        #search-clear:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        #search-clear.vis {
            display: block;
        }
        #search-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid #e2e8f0;
            border-top-color: #dc2626;
            border-radius: 50%;
            animation: rot .6s linear infinite;
            display: none;
            margin-left: 6px;
        }
        #search-spinner.vis {
            display: block;
        }
        #search-results {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-top: 8px;
            overflow: hidden;
            display: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-height: 300px;
            overflow-y: auto;
        }
        .sr-sec {
            padding: 8px 16px;
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }
        .sr-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }
        .sr-item:last-child {
            border-bottom: none;
        }
        .sr-item:hover {
            background: #fef2f2;
        }
        .sr-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .sr-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 1px;
        }

        /* FILTER PANEL TABLEAU-STYLE (Kanan) */
        #filter-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 900;
            width: 220px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            max-height: calc(100% - 80px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        .fp-title {
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fp-sec-lbl {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .fp-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            font-weight: 500;
            transition: color 0.2s;
        }
        .fp-row:hover {
            color: #0f172a;
        }
        .fp-row input[type=checkbox] {
            accent-color: #dc2626;
            width: 15px;
            height: 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        .fp-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .fp-sep {
            height: 1px;
            background: #f1f5f9;
            margin: 12px 0;
        }
        .rt-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .rt-chip {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
            background: #fff;
            color: #475569;
        }
        .rt-chip:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .rt-chip.active {
            background: #dc2626 !important;
            color: #fff !important;
            border-color: #dc2626 !important;
        }

        /* STATS BAR PREMIUM (Bawah Kiri) */
        #stats-bar {
            position: absolute;
            bottom: 30px;
            left: 20px;
            z-index: 900;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            max-width: 70%;
        }
        .stat-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .stat-chip:hover {
            transform: translateY(-2px);
        }
        .sc-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .stat-num {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        /* TILE MAP SWITCHER */
        #bottom-bar {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 900;
            display: flex;
            gap: 6px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .tile-btn {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            color: #475569;
            background: transparent;
            transition: all 0.2s;
        }
        .tile-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .tile-btn.active {
            background: #dc2626;
            color: #fff;
        }

        /* ZOOM INFO */
        #zoom-info {
            position: absolute;
            bottom: 30px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            z-index: 900;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* POPUP TOOLTIP PREMIUM */
        .custom-popup-content {
            padding: 12px;
            font-family: 'Mozilla Text', sans-serif;
            min-width: 200px;
        }
        .popup-title {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }
        .popup-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
            width: 220px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            max-height: calc(100% - 80px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        .fp-title {
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fp-sec-lbl {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .fp-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            font-weight: 500;
            transition: color 0.2s;
        }
        .fp-row:hover {
            color: #0f172a;
        }
        .fp-row input[type=checkbox] {
            accent-color: #dc2626;
            width: 15px;
            height: 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        .fp-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .fp-sep {
            height: 1px;
            background: #f1f5f9;
            margin: 12px 0;
        }
        .rt-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .rt-chip {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
            background: #fff;
            color: #475569;
        }
        .rt-chip:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .rt-chip.active {
            background: #dc2626 !important;
            color: #fff !important;
            border-color: #dc2626 !important;
        }

        /* ── STATS BAR PREMIUM (Bawah Kiri) ── */
        #stats-bar {
            position: absolute;
            bottom: 30px;
            left: 20px;
            z-index: 900;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            max-width: 70%;
        }
        .stat-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .stat-chip:hover {
            transform: translateY(-2px);
        }
        .sc-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .stat-num {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        /* ── TILE SWITCHER (Bawah Tengah) ── */
        #bottom-bar {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 900;
            display: flex;
            gap: 6px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .tile-btn {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            color: #475569;
            background: transparent;
            transition: all 0.2s;
        }
        .tile-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .tile-btn.active {
            background: #dc2626;
            color: #fff;
        }

        /* ── ZOOM INFO ── */
        #zoom-info {
            position: absolute;
            bottom: 30px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            z-index: 900;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* ── POPUP TOOLTIP PREMIUM ── */
        .custom-popup-content {
            padding: 12px;
            font-family: 'Outfit', sans-serif;
            min-width: 200px;
        }
        .popup-title {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }
        .popup-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }
        .popup-label {
            color: #64748b;
            font-weight: 500;
        }
        .popup-value {
            color: #0f172a;
            font-weight: 700;
        }
        .popup-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #dc2626;
            color: #fff;
            border: none;
            padding: 6px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .popup-btn:hover {
            background: #991b1b;
        }

        /* ── CUSTOM MARKER ── */
        .pulse-marker {
            background: #dc2626;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        .pulse-marker.vulnerable {
            background: #f43f5e; /* Rose 500 */
            border-color: #ffe4e6;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.7);
        }
        .evac-marker {
            background: #10b981; /* Emerald 500 */
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        /* ── MODAL ELEGAN (Glassmorphism) ── */
        #modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        #modal-overlay.show {
            display: flex;
        }
        #modal {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            border: 1px solid #f1f5f9;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        #modal-overlay.show #modal {
            transform: scale(1);
        }
        #modal h3 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        .m-sub {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .m-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f1f5f9;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .m-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .m-item-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
        }
        .m-label {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }
        .m-val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        .m-val.highlight {
            color: #dc2626;
        }
        .m-emergency {
            margin-top: 16px;
            padding: 12px 14px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            font-size: 11px;
            color: #991b1b;
            line-height: 1.6;
            font-weight: 500;
        }
        .m-footer {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
            font-weight: 500;
        }
        @keyframes rot { to { transform: rotate(360deg); } }
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

            <a href="peta_sebaran.php" class="flex items-center gap-3 px-3 py-2 rounded bg-rose-800">
                <i class="fas fa-users"></i>
                <span>Peta Sebaran Evakuasi</span>
            </a>

            <a href="report.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-file-alt"></i>
                <span>Buat Laporan</span>
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

    <!-- ==================== MAIN CONTENT ==================== -->
    <div class="flex-1 flex flex-col bg-white">

        <!-- Top Bar -->
        <div class="border-b border-gray-200 px-6 py-4 flex-shrink-0">
            <h1 class="text-2xl font-bold text-gray-800">Peta Sebaran Penduduk</h1>
            <p class="text-sm text-gray-600 mt-1">Visualisasi sebaran spasial penduduk, keluarga, dan kelompok rentan di wilayah desa.</p>
        </div>

        <div id="map-section">
            <div id="map"></div>

            <!-- SEARCH BAR -->
            <div id="search-wrap">
                <div id="search-bar">
                    <i class="fas fa-search" id="search-icon-el"></i>
                    <input type="text" id="search-input" placeholder="Cari nama keluarga, NIK, atau RT/RW..."/>
                    <button id="search-clear" onclick="clearSearch()"><i class="fas fa-times"></i></button>
                    <div id="search-spinner"></div>
                </div>
                <div id="search-results"></div>
            </div>

            <!-- TABLEAU-STYLE FILTERS -->
            <div id="filter-panel">
                <div class="fp-title"><i class="fas fa-filter"></i> Saring Data</div>
                
                <div class="fp-sec-lbl">Kelompok Warga</div>
                <label class="fp-row">
                    <input type="checkbox" id="chk-umum" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#3b82f6;"></div> Warga Umum
                </label>
                <label class="fp-row">
                    <input type="checkbox" id="chk-lansia" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#eab308;"></div> Lansia (≥60 Thn)
                </label>
                <label class="fp-row">
                    <input type="checkbox" id="chk-balita" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#f43f5e;"></div> Balita (≤5 Thn)
                </label>
                <label class="fp-row">
                    <input type="checkbox" id="chk-ibu_hamil" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#a855f7;"></div> Ibu Hamil
                </label>
                <label class="fp-row">
                    <input type="checkbox" id="chk-disabilitas" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#10b981;"></div> Disabilitas
                </label>
                <label class="fp-row">
                    <input type="checkbox" id="chk-evakuasi" checked onchange="applyFilters()"/>
                    <div class="fp-dot" style="background:#10b981;"></div> Titik Evakuasi
                </label>

                <div class="fp-sep"></div>

                <div class="fp-sec-lbl">Filter Berdasarkan RW</div>
                <div class="rt-row" id="rw-filter-container">
                    <!-- Chip RW akan dibuat secara dinamis oleh JS -->
                </div>
            </div>

            <!-- DYNAMIC STATS BAR -->
            <div id="stats-bar">
                <div class="stat-chip">
                    <div class="sc-dot" style="background:#3b82f6;"></div>
                    <span>Total Warga: <span class="stat-num" id="stat-total-warga">0</span></span>
                </div>
                <div class="stat-chip">
                    <div class="sc-dot" style="background:#eab308;"></div>
                    <span>Lansia: <span class="stat-num" id="stat-lansia">0</span></span>
                </div>
                <div class="stat-chip">
                    <div class="sc-dot" style="background:#f43f5e;"></div>
                    <span>Balita: <span class="stat-num" id="stat-balita">0</span></span>
                </div>
                <div class="stat-chip">
                    <div class="sc-dot" style="background:#a855f7;"></div>
                    <span>Ibu Hamil: <span class="stat-num" id="stat-ibu_hamil">0</span></span>
                </div>
                <div class="stat-chip">
                    <div class="sc-dot" style="background:#10b981;"></div>
                    <span>Disabilitas: <span class="stat-num" id="stat-disabilitas">0</span></span>
                </div>
            </div>

            <!-- TILE MAP SWITCHER -->
            <div id="bottom-bar">
                <button class="tile-btn active" id="btn-tile-street" onclick="switchTile('street')">Jalanan</button>
                <button class="tile-btn" id="btn-tile-dark" onclick="switchTile('dark')">Gelap</button>
                <button class="tile-btn" id="btn-tile-satellite" onclick="switchTile('satellite')">Satelit</button>
            </div>

            <!-- ZOOM INFO -->
            <div id="zoom-info">Zoom: <span id="zoom-level">14</span></div>
        </div>
    </div>

</div>

<!-- Modal -->
<div id="modal-overlay">
    <div id="modal">
        <button class="m-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        <h3 id="modal-title">Nama Keluarga</h3>
        <div class="m-sub" id="modal-sub">Alamat lengkap rumah</div>
        <div id="modal-body">
            <!-- Isi detail data dimasukkan via JS -->
        </div>
        <div class="m-emergency">
            <i class="fas fa-exclamation-triangle mr-1"></i> <b>Kontak Darurat:</b> 112 &nbsp;|&nbsp; <b>BPBD Lebak:</b> 0252-201234
        </div>
        <div class="m-footer">SIGAP DESA — Penanggulangan Risiko Bencana Spasial</div>
    </div>
</div>

<!-- PUSTAKA JAVASCRIPT UTAMA -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
<script src="vendor/jquery/jquery.min.js"></script>

<script>
    // Ambil data dari server PHP
    const rawRumahData = <?php echo json_encode($rumahData); ?>;
    
    // Inisialisasi variabel peta
    let map;
    let markerCluster;
    let activeTile = 'street';
    let markersList = [];
    let selectedRw = 'Semua';

    // Layer Peta
    const tileLayers = {
        street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }),
        dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '© CartoDB'
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        })
    };

    // Jalankan inisialisasi ketika dokumen siap
    $(document).ready(function() {
        initMap();
        initRwFilters();
        renderMapMarkers();
        initSearch();
    });

    function initMap() {
        // Tentukan titik tengah peta (default ke Lebak Banten / Panggarangan)
        let defaultCenter = [-6.967, 106.333];
        
        // Cari koordinat rata-rata jika ada data koordinat rumah dari DB
        if (rawRumahData.length > 0) {
            let totalLat = 0, totalLng = 0;
            rawRumahData.forEach(r => {
                totalLat += r.lat;
                totalLng += r.lng;
            });
            defaultCenter = [totalLat / rawRumahData.length, totalLng / rawRumahData.length];
        }

        map = L.map('map', {
            zoomControl: true,
            layers: [tileLayers.street],
            doubleClickZoom: false // Nonaktifkan double-click zoom agar bisa digunakan untuk menambah titik evakuasi
        }).setView(defaultCenter, 14);

        // Klik Dua Kali di Peta untuk Menambah Titik Evakuasi
        map.on('dblclick', function(e) {
            openAddEvacuationModal(e.latlng.lat, e.latlng.lng);
        });

        // Update zoom level indicator
        map.on('zoomend', function() {
            $('#zoom-level').text(map.getZoom());
        });

        // Load Titik Evakuasi
        loadEvacuationPoints();
    }

    // Inisialisasi daftar RW dari data
    function initRwFilters() {
        const uniqueRw = ['Semua'];
        rawRumahData.forEach(r => {
            if (r.rw && !uniqueRw.includes(r.rw)) {
                uniqueRw.push(r.rw);
            }
        });

        // Urutkan RW
        uniqueRw.sort();

        let html = '';
        uniqueRw.forEach(rw => {
            const isActive = rw === 'Semua' ? 'active' : '';
            html += `<span class="rt-chip ${isActive}" data-rw="${rw}" onclick="selectRwFilter('${rw}')">${rw}</span>`;
        });
        $('#rw-filter-container').html(html);
    }

    function selectRwFilter(rw) {
        selectedRw = rw;
        $('.rt-chip').removeClass('active');
        $(`.rt-chip[data-rw="${rw}"]`).addClass('active');
        applyFilters();
    }

    // Tukar jenis dasar peta
    function switchTile(tileName) {
        map.removeLayer(tileLayers[activeTile]);
        tileLayers[tileName].addTo(map);
        
        $('.tile-btn').removeClass('active');
        $(`#btn-tile-${tileName}`).addClass('active');
        activeTile = tileName;
    }

    // Render penanda peta dengan logic filtering
    function renderMapMarkers() {
        if (markerCluster) {
            map.removeLayer(markerCluster);
        }

        markerCluster = L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius: 50
        });

        markersList = [];

        // Filter data rumah aktif
        const isUmum = $('#chk-umum').is(':checked');
        const isLansia = $('#chk-lansia').is(':checked');
        const isBalita = $('#chk-balita').is(':checked');
        const isIbuHamil = $('#chk-ibu_hamil').is(':checked');
        const isDisabilitas = $('#chk-disabilitas').is(':checked');

        let statTotalWarga = 0;
        let statLansia = 0;
        let statBalita = 0;
        let statIbuHamil = 0;
        let statDisabilitas = 0;

        rawRumahData.forEach(rumah => {
            // Saring RW
            if (selectedRw !== 'Semua' && rumah.rw !== selectedRw) {
                return;
            }

            // Saring Kelompok
            let matchFilter = false;
            let isVulnerable = rumah.kelompok.length > 0;

            if (isUmum && !isVulnerable) matchFilter = true;
            if (isLansia && rumah.kelompok.includes('lansia')) matchFilter = true;
            if (isBalita && rumah.kelompok.includes('balita')) matchFilter = true;
            if (isIbuHamil && rumah.kelompok.includes('ibu_hamil')) matchFilter = true;
            if (isDisabilitas && rumah.kelompok.includes('disabilitas')) matchFilter = true;

            if (!matchFilter) return;

            // Update data statistik dinamis
            statTotalWarga += rumah.anggota;
            statLansia += rumah.detail_warga.lansia;
            statBalita += rumah.detail_warga.balita;
            statIbuHamil += rumah.detail_warga.ibu_hamil;
            statDisabilitas += rumah.detail_warga.disabilitas;

            // Membuat Custom HTML Pin (Pulse)
            const isVulnClass = isVulnerable ? 'vulnerable' : '';
            const customIcon = L.divIcon({
                className: `pulse-marker ${isVulnClass}`,
                html: `<div>${rumah.anggota}</div>`,
                iconSize: [26, 26]
            });

            const marker = L.marker([rumah.lat, rumah.lng], { icon: customIcon });

            // Popup Konten Elegan
            const popupContent = `
                <div class="custom-popup-content">
                    <div class="popup-title">${rumah.nama}</div>
                    <div class="popup-row"><span class="popup-label">Alamat:</span><span class="popup-value">${rumah.alamat}</span></div>
                    <div class="popup-row"><span class="popup-label">RT/RW:</span><span class="popup-value">${rumah.rt} / ${rumah.rw}</span></div>
                    <div class="popup-row"><span class="popup-label">Desa:</span><span class="popup-value">${rumah.desa}</span></div>
                    <div class="popup-row"><span class="popup-label">Total Warga:</span><span class="popup-value">${rumah.anggota} Jiwa</span></div>
                    <button class="popup-btn" onclick="openDetails(${rumah.id})">Lihat Detail Anggota</button>
                </div>
            `;

            marker.bindPopup(popupContent);
            markerCluster.addLayer(marker);

            // Simpan referensi ke markersList untuk pencarian
            markersList.push({
                id: rumah.id,
                nama: rumah.nama,
                alamat: rumah.alamat,
                rt: rumah.rt,
                rw: rumah.rw,
                lat: rumah.lat,
                lng: rumah.lng,
                markerRef: marker
            });
        });

        map.addLayer(markerCluster);

        // Update display angka statistik
        $('#stat-total-warga').text(statTotalWarga);
        $('#stat-lansia').text(statLansia);
        $('#stat-balita').text(statBalita);
        $('#stat-ibu_hamil').text(statIbuHamil);
        $('#stat-disabilitas').text(statDisabilitas);
    }

    function applyFilters() {
        renderMapMarkers();
        renderEvacuationPoints();
    }

    // ── SISTEM INTERAKTIF TITIK EVAKUASI (AJAX + MODAL) ──
    let evacLayerGroup;
    let customEvacuationPoints = [];

    function loadEvacuationPoints() {
        $.get('get_evakuasi.php', function(res) {
            if (res.success) {
                customEvacuationPoints = res.data;
                renderEvacuationPoints();
            }
        });
    }

    function renderEvacuationPoints() {
        if (evacLayerGroup) {
            map.removeLayer(evacLayerGroup);
        }
        evacLayerGroup = L.layerGroup();

        const showEvacuation = $('#chk-evakuasi').is(':checked');
        if (!showEvacuation) return;

        customEvacuationPoints.forEach(pt => {
            const customIcon = L.divIcon({
                className: 'evac-marker',
                html: '<div><i class="fas fa-shield-alt"></i></div>',
                iconSize: [28, 28]
            });

            const marker = L.marker([pt.lat, pt.lng], { icon: customIcon });

            const popupContent = `
                <div class="custom-popup-content">
                    <div class="popup-title" style="color: #10b981;"><i class="fas fa-shield-alt mr-1"></i> Titik Evakuasi</div>
                    <div class="popup-row"><span class="popup-label">Nama:</span><span class="popup-value">${pt.nama}</span></div>
                    <div class="popup-row"><span class="popup-label">Kapasitas:</span><span class="popup-value">${pt.kapasitas} Jiwa</span></div>
                    <div class="popup-row"><span class="popup-label">Keterangan:</span><span class="popup-value">${pt.keterangan}</span></div>
                </div>
            `;
            marker.bindPopup(popupContent);
            evacLayerGroup.addLayer(marker);
        });

        evacLayerGroup.addTo(map);
    }

    function openAddEvacuationModal(lat, lng, name = "") {
        $('#modal-title').html('<i class="fas fa-shield-alt text-emerald-500 mr-2"></i>Tambah Titik Evakuasi');
        $('#modal-sub').text('Tandai titik koordinat ini sebagai lokasi aman evakuasi warga.');

        let formHtml = `
            <form id="form-evakuasi" onsubmit="submitEvakuasi(event)">
                <div style="margin-bottom: 12px;">
                    <label class="m-label" style="display:block; margin-bottom: 4px;">Nama Lokasi Evakuasi *</label>
                    <input type="text" id="evac-nama" name="nama_lokasi" value="${name}" required placeholder="Contoh: Lapangan Situregen, Masjid Jatake" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; outline:none; font-family:inherit;" />
                </div>
                <div style="display:flex; gap:10px; margin-bottom: 12px;">
                    <div style="flex:1;">
                        <label class="m-label" style="display:block; margin-bottom: 4px;">Latitude</label>
                        <input type="text" id="evac-lat" name="latitude" value="${lat.toFixed(6)}" readonly style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; background:#f8fafc; color:#64748b; font-family:inherit;" />
                    </div>
                    <div style="flex:1;">
                        <label class="m-label" style="display:block; margin-bottom: 4px;">Longitude</label>
                        <input type="text" id="evac-lng" name="longitude" value="${lng.toFixed(6)}" readonly style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; background:#f8fafc; color:#64748b; font-family:inherit;" />
                    </div>
                </div>
                <div style="margin-bottom: 12px;">
                    <label class="m-label" style="display:block; margin-bottom: 4px;">Kapasitas Maksimal (Jiwa)</label>
                    <input type="number" id="evac-kapasitas" name="kapasitas" placeholder="Contoh: 300" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; outline:none; font-family:inherit;" />
                </div>
                <div style="margin-bottom: 16px;">
                    <label class="m-label" style="display:block; margin-bottom: 4px;">Keterangan & Fasilitas</label>
                    <textarea id="evac-keterangan" name="keterangan" rows="3" placeholder="Contoh: Berada di ketinggian 25 mdpl, persediaan air bersih memadai" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; outline:none; font-family:inherit; resize:none;"></textarea>
                </div>
                <button type="submit" class="popup-btn" style="background:#10b981; border:none; padding:10px; border-radius:8px; font-size:13px; font-weight:700; width:100%; color:white; cursor:pointer; font-family:inherit;">
                    <i class="fas fa-save mr-2"></i>Simpan Titik Evakuasi
                </button>
            </form>
        `;

        $('#modal-body').html(formHtml);
        $('#modal-overlay').addClass('show');
    }

    function submitEvakuasi(e) {
        e.preventDefault();
        
        const nama = $('#evac-nama').val();
        const lat = $('#evac-lat').val();
        const lng = $('#evac-lng').val();
        const kapasitas = $('#evac-kapasitas').val();
        const keterangan = $('#evac-keterangan').val();

        $.post('simpan_evakuasi.php', {
            nama_lokasi: nama,
            latitude: lat,
            longitude: lng,
            kapasitas: kapasitas,
            keterangan: keterangan
        }, function(res) {
            if (res.success) {
                alert(res.message);
                closeModal();
                loadEvacuationPoints(); // Reload and render
            } else {
                alert('Eror: ' + res.message);
            }
        });
    }

    // Detail Modal Box
    function openDetails(rumahId) {
        const rumah = rawRumahData.find(r => r.id === rumahId);
        if (!rumah) return;

        $('#modal-title').text(rumah.nama);
        $('#modal-sub').html(`<i class="fas fa-map-marker-alt text-red-500 mr-1"></i> ${rumah.alamat} (${rumah.rt} / ${rumah.rw})`);

        let detailsHtml = `
            <div class="m-item-detail">
                <span class="m-label"><i class="fas fa-users text-blue-500 mr-2"></i>Total Warga Tinggal</span>
                <span class="m-val">${rumah.anggota} Orang</span>
            </div>
            <div class="m-item-detail">
                <span class="m-label"><i class="fas fa-user-clock text-amber-500 mr-2"></i>Lansia (>= 60 Thn)</span>
                <span class="m-val ${rumah.detail_warga.lansia > 0 ? 'highlight' : ''}">${rumah.detail_warga.lansia} Orang</span>
            </div>
            <div class="m-item-detail">
                <span class="m-label"><i class="fas fa-baby text-rose-500 mr-2"></i>Balita (<= 5 Thn)</span>
                <span class="m-val ${rumah.detail_warga.balita > 0 ? 'highlight' : ''}">${rumah.detail_warga.balita} Anak</span>
            </div>
            <div class="m-item-detail">
                <span class="m-label"><i class="fas fa-female text-purple-500 mr-2"></i>Ibu Hamil</span>
                <span class="m-val ${rumah.detail_warga.ibu_hamil > 0 ? 'highlight' : ''}">${rumah.detail_warga.ibu_hamil} Orang</span>
            </div>
            <div class="m-item-detail">
                <span class="m-label"><i class="fas fa-wheelchair text-emerald-500 mr-2"></i>Penyandang Disabilitas</span>
                <span class="m-val ${rumah.detail_warga.disabilitas > 0 ? 'highlight' : ''}">${rumah.detail_warga.disabilitas} Orang</span>
            </div>
        `;

        $('#modal-body').html(detailsHtml);
        $('#modal-overlay').addClass('show');
    }

    function closeModal() {
        $('#modal-overlay').removeClass('show');
    }

    // Menutup modal jika klik diluar panel modal
    $('#modal-overlay').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // ── FITUR PENCARIAN CERDAS (Warga & Nama Tempat) ──
    let searchTimeout = null;

    function initSearch() {
        const $input = $('#search-input');
        const $results = $('#search-results');
        const $clear = $('#search-clear');
        const $spinner = $('#search-spinner');

        $input.on('input', function() {
            const val = $(this).val().trim();

            if (val.length === 0) {
                clearSearch();
                return;
            }

            $clear.addClass('vis');
            
            // Saring pencarian lokal (Keluarga)
            const valLower = val.toLowerCase();
            const filteredWarga = markersList.filter(item => {
                return item.nama.toLowerCase().includes(valLower) || 
                       item.alamat.toLowerCase().includes(valLower) ||
                       item.rt.toLowerCase().includes(valLower) ||
                       item.rw.toLowerCase().includes(valLower);
            });

            // Tampilkan pencarian lokal terlebih dahulu
            renderSearchResults(filteredWarga, []);

            // Jika input >= 3 karakter, cari tempat secara geografis (Photon API)
            if (val.length >= 3) {
                $spinner.addClass('vis');
                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(() => {
                    fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(val)}`)
                        .then(res => res.json())
                        .then(data => {
                            $spinner.removeClass('vis');
                            const places = data.features || [];
                            renderSearchResults(filteredWarga, places);
                        })
                        .catch(err => {
                            $spinner.removeClass('vis');
                            console.error("Gagal memuat pencarian tempat:", err);
                        });
                }, 400); // Debounce 400ms
            } else {
                $spinner.removeClass('vis');
            }
        });
    }

    function renderSearchResults(wargaList, placeList) {
        const $results = $('#search-results');
        
        if (wargaList.length === 0 && placeList.length === 0) {
            $results.html('<div class="sr-empty">Pencarian tidak ditemukan</div>').show();
            return;
        }

        let html = '';

        // 1. Seksi Hasil Warga
        if (wargaList.length > 0) {
            html += '<div class="sr-sec"><i class="fas fa-users"></i> Keluarga / Warga</div>';
            wargaList.slice(0, 5).forEach(item => {
                html += `
                    <div class="sr-item" onclick="focusOnMarker(${item.id})">
                        <div>
                            <div class="sr-name">${item.nama}</div>
                            <div class="sr-sub">${item.alamat} (${item.rt}/${item.rw})</div>
                        </div>
                    </div>
                `;
            });
        }

        // 2. Seksi Hasil Tempat Geografis
        if (placeList.length > 0) {
            html += '<div class="sr-sec"><i class="fas fa-map-marker-alt"></i> Lokasi / Tempat</div>';
            placeList.slice(0, 5).forEach(item => {
                const name = item.properties.name || "Tanpa Nama";
                const city = item.properties.city || item.properties.state || "";
                const country = item.properties.country || "";
                const subText = [city, country].filter(Boolean).join(', ');
                const lat = item.geometry.coordinates[1];
                const lon = item.geometry.coordinates[0];

                html += `
                    <div class="sr-item" onclick="focusOnLocation(${lat}, ${lon}, '${name.replace(/'/g, "\\'")}')">
                        <div>
                            <div class="sr-name">${name}</div>
                            <div class="sr-sub">${subText}</div>
                        </div>
                    </div>
                `;
            });
        }

        $results.html(html).show();
    }

    function clearSearch() {
        $('#search-input').val('');
        $('#search-results').hide().html('');
        $('#search-clear').removeClass('vis');
        $('#search-spinner').removeClass('vis');
    }

    function focusOnMarker(rumahId) {
        const item = markersList.find(m => m.id === rumahId);
        if (!item) return;

        clearSearch();
        map.setView([item.lat, item.lng], 18);
        setTimeout(() => {
            item.markerRef.openPopup();
        }, 300);
    }

    function focusOnLocation(lat, lon, placeName) {
        clearSearch();
        map.setView([lat, lon], 16);
        
        // Buat pop-up penanda sementara
        L.popup()
            .setLatLng([lat, lon])
            .setContent(`
                <div class="custom-popup-content">
                    <div class="popup-title"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Lokasi Dicari</div>
                    <div class="popup-row"><span class="popup-value">${placeName}</span></div>
                </div>
            `)
            .openOn(map);
    }
</script>
</body>
</html>
