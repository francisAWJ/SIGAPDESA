<?php
session_start();
include 'config.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['embed'])) {
    $embed = mysqli_real_escape_string($conn, $_POST['embed']);
    mysqli_query($conn, "UPDATE tableau_embed SET embed_code='$embed' WHERE id=1");
    $message = "Embed Tableau berhasil diperbarui!";
}

$result = mysqli_query($conn, "SELECT embed_code FROM tableau_embed WHERE id=1");
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Tableau Embed</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h3>Edit Tableau Embed Code</h3>
<?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>

<form method="POST">
    <textarea name="embed" class="form-control" rows="10"><?= htmlspecialchars($data['embed_code']); ?></textarea>
    <br>
    <button type="submit" class="btn btn-danger">Simpan Embed</button>
</form>

</body>
</html>
