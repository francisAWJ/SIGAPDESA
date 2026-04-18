<?php
include 'config.php';

$kabupaten_id = $_GET['kabupaten_id'];

$query = $conn->query("SELECT id_kecamatan, nama_kecamatan 
                       FROM kecamatan 
                       WHERE id_kabupaten = '$kabupaten_id'
                       ORDER BY nama_kecamatan ASC");

$data = [];
while ($row = $query->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
