<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Warga - SIGAP</title>

    <!-- Font Mozilla Text -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">

    <!-- Tailwind -->
    <link href="output.css" rel="stylesheet">

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        #content { max-width: 100%; padding: 20px; }
        .table-responsive { overflow-x: auto; }

        .table thead {
            background-color: #b71c1c;
            color: white;
        }

        .btn-red {
            background-color: #b71c1c;
            color: #fff;
        }

        .btn-red:hover {
            background-color: #7f0000;
            color: white;
        }

        .filter-box {
            padding: 15px;
            border-radius: 8px;
            background: #fff3f3;
            border: 1px solid #ffcdd2;
        }
    </style>
</head>

<body>

<div id="content">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0" style="color:#b71c1c;">Daftar Warga</h1>
        <a href="input_data.php" class="btn btn-red btn-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data Warga
        </a>
    </div>

    <!-- COLLAPSIBLE FILTER -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between">
            <h6 class="m-0 font-weight-bold text-danger">Filter Data</h6>
            <a href="#" data-toggle="collapse" data-target="#filterForm">Filter</a>
        </div>

        <div id="filterForm" class="collapse">
            <div class="card-body filter-box">

                <div class="row">
                    <div class="col-md-3">
                        <label>Provinsi</label>
                        <select id="filterProvinsi" class="form-control">
                            <option value="">Semua</option>
                            <?php
                            $q = $conn->query("SELECT * FROM provinsi ORDER BY nama_provinsi");
                            while ($p = $q->fetch_assoc()) {
                                echo "<option value='{$p['nama_provinsi']}' data-id='{$p['id_provinsi']}'>{$p['nama_provinsi']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Kabupaten</label>
                        <select id="filterKabupaten" class="form-control">
                            <option value="">Semua</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Kecamatan</label>
                        <select id="filterKecamatan" class="form-control">
                            <option value="">Semua</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Desa</label>
                        <select id="filterDesa" class="form-control">
                            <option value="">Semua</option>
                        </select>
                    </div>

                    <div class="col-md-3 mt-3">
                        <label>RT</label>
                        <input type="text" id="filterRT" class="form-control" placeholder="Contoh: 02">
                    </div>

                    <div class="col-md-3 mt-3">
                        <label>RW</label>
                        <input type="text" id="filterRW" class="form-control" placeholder="Contoh: 05">
                    </div>

                    <div class="col-md-12 mt-3">
                        <button id="applyFilter" class="btn btn-red btn-sm">Terapkan Filter</button>
                        <button id="resetFilter" class="btn btn-secondary btn-sm">Reset</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- END FILTER -->

    <!-- TABLE -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php
            $sql = "SELECT 
                        w.id_warga, w.nik, w.nama_lengkap, w.tempat_lahir, w.tanggal_lahir, w.jenis_kelamin,
                        w.golongan_darah, w.agama, w.status_perkawinan, w.pekerjaan, w.nomor_telepon,
                        r.alamat_lengkap,
                        d.nama_desa, c.nama_kecamatan, kb.nama_kabupaten, p.nama_provinsi,
                        rt.nomor_rt, rw.nomor_rw
                    FROM warga w
                    LEFT JOIN rumah r ON w.id_rumah = r.id_rumah
                    LEFT JOIN desa d ON w.id_desa = d.id_desa
                    LEFT JOIN kecamatan c ON w.id_kecamatan = c.id_kecamatan
                    LEFT JOIN kabupaten kb ON w.id_kabupaten = kb.id_kabupaten
                    LEFT JOIN provinsi p ON w.id_provinsi = p.id_provinsi
                    LEFT JOIN rt ON r.id_rt = rt.id_rt
                    LEFT JOIN rw ON r.id_rw = rw.id_rw
                    ORDER BY w.nama_lengkap ASC";
            $result = mysqli_query($conn, $sql);
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="wargaTable" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>TTL</th>
                            <th>JK</th>
                            <th>Alamat</th>

                            <th>RT</th>
                            <th>RW</th>

                            <th>Desa</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten</th>
                            <th>Provinsi</th>

                            <th>Gol. Darah</th>
                            <th>Agama</th>
                            <th>Status</th>
                            <th>Pekerjaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $row['nik'] ?></td>
                            <td><?= $row['nama_lengkap'] ?></td>
                            <td><?= $row['tempat_lahir'] ?> / <?= $row['tanggal_lahir'] ?></td>
                            <td><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>

                            <td><?= $row['alamat_lengkap'] ?></td>
                            <td><?= $row['nomor_rt'] ?></td>
                            <td><?= $row['nomor_rw'] ?></td>

                            <td><?= $row['nama_desa'] ?></td>
                            <td><?= $row['nama_kecamatan'] ?></td>
                            <td><?= $row['nama_kabupaten'] ?></td>
                            <td><?= $row['nama_provinsi'] ?></td>

                            <td><?= $row['golongan_darah'] ?></td>
                            <td><?= $row['agama'] ?></td>
                            <td><?= $row['status_perkawinan'] ?></td>
                            <td><?= $row['pekerjaan'] ?></td>

                            <td>
                                <a href="edit_warga.php?id=<?= $row['id_warga'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="hapus_warga.php?id=<?= $row['id_warga'] ?>" class="btn btn-red btn-sm" onclick="return confirm('Hapus data ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END TABLE -->

</div>

<!-- JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>

<script>
let table;

$(document).ready(function () {

    // INIT DATATABLE
    table = $('#wargaTable').DataTable({
        "autoWidth": false,
        "scrollX": true
    });

    // FILTER PROVINSI → KABUPATEN
    $('#filterProvinsi').change(function () {
        let provId = $(this).find(":selected").data("id");

        $('#filterKabupaten').html('<option value="">Semua</option>');
        $('#filterKecamatan').html('<option value="">Semua</option>');
        $('#filterDesa').html('<option value="">Semua</option>');

        if (provId) {
            $.get("get_kabupaten.php?provinsi_id=" + provId, function (data) {
                let kab = JSON.parse(data);
                kab.forEach(k => {
                    $('#filterKabupaten').append(`<option value="${k.nama_kabupaten}" data-id="${k.id_kabupaten}">${k.nama_kabupaten}</option>`);
                });
            });
        }
    });

    // FILTER KABUPATEN → KECAMATAN
    $('#filterKabupaten').change(function () {
        let id = $(this).find(":selected").data("id");

        $('#filterKecamatan').html('<option value="">Semua</option>');
        $('#filterDesa').html('<option value="">Semua</option>');

        if (id) {
            $.get("get_kecamatan.php?kabupaten_id=" + id, function (data) {
                let kec = JSON.parse(data);
                kec.forEach(k => {
                    $('#filterKecamatan').append(`<option value="${k.nama_kecamatan}" data-id="${k.id_kecamatan}">${k.nama_kecamatan}</option>`);
                });
            });
        }
    });

    // FILTER KECAMATAN → DESA
    $('#filterKecamatan').change(function () {
        let id = $(this).find(":selected").data("id");

        $('#filterDesa').html('<option value="">Semua</option>');

        if (id) {
            $.get("get_desa.php?kecamatan_id=" + id, function (data) {
                let desa = JSON.parse(data);
                desa.forEach(d => {
                    $('#filterDesa').append(`<option value="${d.nama_desa}">${d.nama_desa}</option>`);
                });
            });
        }
    });

    // APPLY FILTER
    $('#applyFilter').click(function () {
        table.column(11).search($('#filterProvinsi').val());
        table.column(10).search($('#filterKabupaten').val());
        table.column(9).search($('#filterKecamatan').val());
        table.column(8).search($('#filterDesa').val());
        table.column(6).search($('#filterRT').val());
        table.column(7).search($('#filterRW').val());
        table.draw();
    });

    // RESET FILTER
    $('#resetFilter').click(function () {
        $('#filterForm select, #filterForm input').val('');
        table.search('').columns().search('').draw();
    });

});
</script>

</body>
</html>
