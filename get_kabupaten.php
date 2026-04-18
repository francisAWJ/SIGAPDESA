<?php
include 'config.php';

$provinsi_id = $_GET['provinsi_id'];

$query = $conn->query("SELECT id_kabupaten, nama_kabupaten 
                       FROM kabupaten 
                       WHERE id_provinsi = '$provinsi_id'
                       ORDER BY nama_kabupaten ASC");

$data = [];
while ($row = $query->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
