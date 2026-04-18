<?php
// proses_input_penduduk.php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $nama_lengkap = $_POST['nama_lengkap'];
        $nik = $_POST['nik'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $alamat = $_POST['alamat'];
        
        // Validasi NIK apakah sudah ada
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM data_penduduk WHERE nik = ?");
        $stmt_check->execute([$nik]);
        
        if ($stmt_check->fetchColumn() > 0) {
            header("Location: input_data.php?error=nik_exists");
            exit();
        }
        
        // Insert data ke database
        $sql = "INSERT INTO data_penduduk (nama_lengkap, nik, jenis_kelamin, alamat, tanggal_input) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_lengkap, $nik, $jenis_kelamin, $alamat]);
        
        header("Location: input_data.php?success=1");
        exit();
        
    } catch(PDOException $e) {
        header("Location: input_data.php?error=1");
        exit();
    }
} else {
    header("Location: input_data.php");
    exit();
}
?>