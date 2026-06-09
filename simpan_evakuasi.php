<?php
include 'config.php';
session_start();

// Proteksi akses admin
header('Content-Type: application/json');
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai admin.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokasi = clean_input($_POST['nama_lokasi'] ?? '');
    $latitude = clean_input($_POST['latitude'] ?? '');
    $longitude = clean_input($_POST['longitude'] ?? '');
    $kapasitas = !empty($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : null;
    $keterangan = clean_input($_POST['keterangan'] ?? '');

    if (empty($nama_lokasi) || empty($latitude) || empty($longitude)) {
        echo json_encode(['success' => false, 'message' => 'Nama lokasi, latitude, dan longitude wajib diisi!']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO titik_evakuasi_kustom (nama_lokasi, latitude, longitude, kapasitas, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $nama_lokasi, $latitude, $longitude, $kapasitas, $keterangan);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Titik evakuasi berhasil disimpan!', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
