<?php
include 'config.php';

$kecamatan_id = $_GET['kecamatan_id'];

$query = $conn->query("SELECT id_desa, nama_desa FROM desa WHERE id_kecamatan = '$kecamatan_id' ORDER BY nama_desa ASC");

$data = [];

while ($row = $query->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
