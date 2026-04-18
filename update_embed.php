<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

if (isset($_POST['embed_code'])) {
    $embed = $_POST['embed_code'];

    $stmt = $conn->prepare("UPDATE tableau_embed SET embed_code=? WHERE id=1");
    $stmt->bind_param("s", $embed);
    $stmt->execute();

    header("Location: edit_embed.php?success=1");
    exit();
}
