<?php
include 'config.php';

$query = $conn->query("SELECT embed_code FROM tableau_embed WHERE id = 1");
$row = $query->fetch_assoc();

echo $row['embed_code'];
