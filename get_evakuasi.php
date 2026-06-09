<?php
include 'config.php';
session_start();

header('Content-Type: application/json');
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

$data = [];
try {
    $result = $conn->query("SELECT id, nama_lokasi, latitude, longitude, COALESCE(kapasitas, 0) AS kapasitas, COALESCE(keterangan, '') AS keterangan FROM titik_evakuasi_kustom ORDER BY id DESC");
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'nama' => $row['nama_lokasi'],
            'lat' => (float)$row['latitude'],
            'lng' => (float)$row['longitude'],
            'kapasitas' => (int)$row['kapasitas'],
            'keterangan' => $row['keterangan']
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
