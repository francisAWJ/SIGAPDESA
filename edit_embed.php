<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Ambil embed code saat ini (ID = 1)
$query = $conn->query("SELECT embed_code FROM tableau_embed WHERE id = 1");
$row = $query->fetch_assoc();
$oldEmbed = $row['embed_code'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Embed Code</title>

    <!-- SB Admin 2 -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fc;
        }

        .card-custom {
            border-left: 5px solid #d80027; /* Merah GMLS */
            border-radius: 10px;
        }

        .btn-gmls {
            background-color: #d80027;
            border-color: #b40020;
            color: white;
        }

        .btn-gmls:hover {
            background-color: #b40020;
            color: white;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background-color: #545b62;
            color: white;
        }
    </style>
</head>

<body class="p-4">

<div class="container">

    <div class="mb-4">
        <h1 class="h3 text-gray-800">
            <i class="fas fa-code"></i> Edit Tableau Embed Code
        </h1>
        <p class="text-muted">Perbarui kode embed dashboard Tableau tanpa harus masuk ke kode PHP.</p>
    </div>

    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success shadow-sm">
            <i class="fas fa-check-circle"></i> Embed code berhasil diperbarui!
        </div>
    <?php } ?>

    <div class="card shadow card-custom">
        <div class="card-body">

            <form method="POST" action="update_embed.php">
                <div class="form-group">
                    <label class="font-weight-bold text-danger">Embed Code Tableau:</label>
                    <textarea name="embed_code" class="form-control" rows="12" required><?= htmlspecialchars($oldEmbed) ?></textarea>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="titik_evakuasi.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                    <button class="btn btn-gmls">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

</body>
</html>
