<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        $jenis    = post_str('jenis');
        $nama     = post_str('nama');
        $idsatuan = post_int('idsatuan');
        $status   = post_int('status');
        $harga    = post_int('harga');

        if (post_str('act') === 'delete') {
            run_query('delete from barang where idbarang = ?', 'i', $cur);
            redirect('barang.php');
        } elseif ($nama === '') {
            $err = 'nama wajib diisi.';
        } else {
            if ($cur > 0) {
                run_query(
                    'update barang set jenis = ?, nama = ?, satuan_idsatuan = ?, status = ?, harga = ?
                      where idbarang = ?',
                    'ssiiii',
                    $jenis,
                    $nama,
                    $idsatuan,
                    $status,
                    $harga,
                    $cur
                );
            } else {
                run_query(
                    'insert into barang (jenis, nama, satuan_idsatuan, status, harga)
                     values (?, ?, ?, ?, ?)',
                    'ssiii',
                    $jenis,
                    $nama,
                    $idsatuan,
                    $status,
                    $harga
                );
            }

            redirect('barang.php');
        }
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row(
        'select * from v_barang where idbarang = ?',
        'i',
        (int) $_GET['edit']
    );
}

$satuan = rows('select * from v_satuan order by idsatuan');
$rows   = rows('select * from v_stok_barang order by idbarang');

page_start('barang', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idbarang'] ?? 0) ?>">

    <?php if ($edit): ?>
        <p>idbarang
            <input value="<?= e($edit['idbarang']) ?>" disabled>
        </p>
    <?php endif; ?>
    <p>jenis
        <select name="jenis">
            <option value="O" <?= ($edit['jenis_teks'] ?? 'Obat') === 'Obat' ? 'selected' : '' ?>>Obat</option>
            <option value="S" <?= ($edit['jenis_teks'] ?? '') === 'Suplemen' ? 'selected' : '' ?>>Suplemen</option>
            <option value="A" <?= ($edit['jenis_teks'] ?? '') === 'Alat Kesehatan' ? 'selected' : '' ?>>Alat Kesehatan</option>
        </select>
    </p>
    <p>nama
        <input name="nama" value="<?= e($edit['nama'] ?? '') ?>" required>
    </p>
    <p>idsatuan
        <select name="idsatuan">
            <?php foreach ($satuan as $s): ?>
                <option value="<?= e($s['idsatuan']) ?>" <?= ($edit['idsatuan'] ?? 0) == $s['idsatuan'] ? 'selected' : '' ?>>
                    <?= e($s['idsatuan'] . ' - ' . $s['nama_satuan']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>status
        <select name="status">
            <option value="1" <?= ($edit['status_teks'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= ($edit['status_teks'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </p>
    <p>harga
        <input type="number" name="harga" value="<?= e($edit['harga'] ?? 0) ?>">
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="barang.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>idbarang</th>
        <th>jenis_teks</th>
        <th>nama</th>
        <th>nama_satuan</th>
        <th>status_teks</th>
        <th>harga</th>
        <th>stok</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idbarang']) ?></td>
            <td><?= e($r['jenis_teks']) ?></td>
            <td><?= e($r['nama']) ?></td>
            <td><?= e($r['nama_satuan']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['harga']) ?></td>
            <td><?= e($r['stok']) ?></td>
            <td>
                <a href="barang.php?edit=<?= e($r['idbarang']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['idbarang']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
