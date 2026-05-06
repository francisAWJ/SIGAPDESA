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
    <link href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Mozilla Text', sans-serif;
            background-color: #f8fafc;
        }

        #content {
            max-width: 100%;
        }

        details#filterForm summary {
            padding: 1.5rem 1.75rem;
        }

        #filterForm select,
        #filterForm input {
            border-radius: 1rem;
            border: 1px solid #cbd5e1;
            padding: 1rem 1.25rem;
            background: #fff;
            color: #0f172a;
        }

        #filterForm select option {
            padding: 0.75rem 1rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info {
            padding: 1rem 1.25rem;
        }

        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0.75rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: none;
            background: transparent;
            padding: 0.6rem 1rem;
            margin: 0 0.15rem;
            color: #334155;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #dc2626;
            color: #fff !important;
            border-radius: 0.75rem;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 1rem;
            border: 1px solid #cbd5e1;
            padding: 0.85rem 1rem;
            background: #fff;
            color: #0f172a;
        }
    </style>
</head>

<body>

<div id="content" class="mx-auto min-h-screen px-4 py-6 sm:px-6 lg:px-8">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <!-- <h1 class="text-3xl font-semibold text-gray-800">Daftar Warga</h1> -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="input_data.php" class="inline-flex items-center gap-3 rounded-3xl bg-gradient-to-r from-rose-600 via-red-600 to-rose-500 px-4 py-2 text-base font-semibold text-white shadow-2xl shadow-rose-300 transition hover:from-rose-700 hover:via-red-700 hover:to-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-200">
                <i class="fas fa-plus"></i>
                Tambah Data Warga
            </a>
            <a href="input_data.php" class="inline-flex items-center gap-3 rounded-3xl bg-gradient-to-r from-rose-600 via-red-600 to-rose-500 px-4 py-2 text-base font-semibold text-white shadow-2xl shadow-rose-300 transition hover:from-rose-700 hover:via-red-700 hover:to-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-200">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Input Data
            </a>
        </div>
    </div>

    <details id="filterForm" class="mb-6 overflow-hidden rounded-3xl border border-red-100 bg-white shadow-sm px-2 md:px-8">
        <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-base font-semibold text-red-900 transition hover:bg-red-50">
            <span>Filter Data</span>
            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-200">
                Tampilkan / Sembunyikan
            </span>
        </summary>
        <div class="border-t border-red-100 bg-red-50 px-6 md:px-8 py-5">
            <div class="grid gap-4 lg:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Provinsi</label>
                    <select id="filterProvinsi" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="">Semua</option>
                        <?php
                        $q = $conn->query("SELECT * FROM provinsi ORDER BY nama_provinsi");
                        while ($p = $q->fetch_assoc()) {
                            echo "<option value='{$p['nama_provinsi']}' data-id='{$p['id_provinsi']}'>{$p['nama_provinsi']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kabupaten</label>
                    <select id="filterKabupaten" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="">Semua</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kecamatan</label>
                    <select id="filterKecamatan" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="">Semua</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Desa</label>
                    <select id="filterDesa" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="">Semua</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">RT</label>
                    <input type="text" id="filterRT" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Contoh: 02">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">RW</label>
                    <input type="text" id="filterRW" class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Contoh: 05">
                </div>

                <div class="lg:col-span-4 flex flex-col gap-3 pt-2 sm:flex-row sm:items-center">
                    <button id="applyFilter" class="inline-flex items-center justify-center rounded-3xl bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-rose-300 transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-200">
                        <i class="fas fa-filter mr-2"></i>
                        Terapkan Filter
                    </button>
                    <button id="resetFilter" class="inline-flex items-center justify-center rounded-3xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <i class="fas fa-undo mr-2"></i>
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </details>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
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

            <table class="min-w-full divide-y divide-slate-200 text-sm" id="wargaTable">
                <thead class="bg-rose-600 text-white">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">#</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">NIK</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Nama</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">TTL</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">JK</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Alamat</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">RT</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">RW</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Desa</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Kecamatan</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Kabupaten</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Provinsi</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Gol. Darah</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Agama</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Pekerjaan</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3"><?= $no++ ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nik'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nama_lengkap'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['tempat_lahir'] ?> / <?= $row['tanggal_lahir'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['alamat_lengkap'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nomor_rt'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nomor_rw'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nama_desa'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nama_kecamatan'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nama_kabupaten'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['nama_provinsi'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['golongan_darah'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['agama'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['status_perkawinan'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= $row['pekerjaan'] ?></td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="edit_warga.php?id=<?= $row['id_warga'] ?>" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-800 transition hover:bg-rose-200 hover:text-rose-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="hapus_warga.php?id=<?= $row['id_warga'] ?>" class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700" onclick="return confirm('Hapus data ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
let table;

$(document).ready(function () {

    table = $('#wargaTable').DataTable({
        "autoWidth": false,
        "scrollX": true
    });

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

    $('#applyFilter').click(function () {
        table.column(11).search($('#filterProvinsi').val());
        table.column(10).search($('#filterKabupaten').val());
        table.column(9).search($('#filterKecamatan').val());
        table.column(8).search($('#filterDesa').val());
        table.column(6).search($('#filterRT').val());
        table.column(7).search($('#filterRW').val());
        table.draw();
    });

    $('#resetFilter').click(function () {
        $('#filterForm select, #filterForm input').val('');
        table.search('').columns().search('').draw();
    });

});
</script>

</body>
</html>
