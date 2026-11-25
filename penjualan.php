<?php

require 'config.php';

$err = '';

$users = rows('select * from v_user order by iduser');
$daftar = rows("select * from v_stok_barang where status_teks = 'Aktif' order by nama");

$margin_user = [];
foreach (rows('select user_iduser, idmargin_penjualan, persen from margin_penjualan where status = 1') as $m) {
    $margin_user[(int) $m['user_iduser']] = $m;
}

if (is_post()) {
    try {
        $idbarang = post_int('idbarang');
        $iduser   = post_int('iduser');
        $jumlah   = post_int('jumlah');

        $margin = row(
            'select idmargin_penjualan, persen from margin_penjualan
              where status = 1 and user_iduser = ? limit 1',
            'i',
            $iduser
        );

        $b = row("select * from v_barang where idbarang = ? and status_teks = 'Aktif'", 'i', $idbarang);
        $stok = $b
            ? (int) row('select * from v_stok_barang where idbarang = ?', 'i', $idbarang)['stok']
            : 0;
        $u = row('select * from v_user where iduser = ?', 'i', $iduser);

        if (!$u) {
            $err = 'User tidak ditemukan.';
        } elseif (!$margin) {
            $err = 'User ini belum punya margin penjualan aktif.';
        } elseif (!$b) {
            $err = 'Barang tidak ditemukan atau sudah tidak aktif.';
        } elseif ($jumlah < 1) {
            $err = 'Jumlah harus lebih dari nol.';
        } elseif ($jumlah > $stok) {
            $err = 'Stok tidak mencukupi untuk transaksi ini.';
        } else {
            $harga      = (int) $b['harga'];
            $harga_jual = (int) round($harga + $harga * (float) $margin['persen'] / 100);
            $subtotal   = $harga_jual * $jumlah;
            $ppn        = (int) round($subtotal * PPN_PERSEN / 100);
            $total      = $subtotal + $ppn;

            begin();
            run_query(
                'insert into penjualan (created_at, subtotal_nilai, ppn, total_nilai, user_iduser, margin_penjualan_idmargin_penjualan, status)
                 values (now(), ?, ?, ?, ?, ?, \'S\')',
                'iiiii',
                $subtotal,
                $ppn,
                $total,
                $iduser,
                (int) $margin['idmargin_penjualan']
            );
            $idpenjualan = insert_id();

            run_query(
                'insert into detail_penjualan (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, barang_idbarang)
                 values (?, ?, ?, ?, ?)',
                'iiiii',
                $harga_jual,
                $jumlah,
                $subtotal,
                $idpenjualan,
                $idbarang
            );

            run_query(
                'insert into kartu_stok (jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, barang_idbarang)
                 values (?, 0, ?, fn_stok_barang(?) - ?, now(), ?, ?)',
                'siiiii',
                'J',
                $jumlah,
                $idbarang,
                $jumlah,
                $idpenjualan,
                $idbarang
            );
            commit();

            redirect('penjualan.php');
        }
    } catch (mysqli_sql_exception $ex) {
        rollback();
        $err = $ex->getMessage();
    }
}

$head  = null;
$items = [];
if (isset($_GET['id'])) {
    $head = row(
        'select * from v_penjualan where idpenjualan = ?',
        'i',
        (int) $_GET['id']
    );

    if ($head) {
        $items = rows(
            'select * from v_detail_penjualan
              where penjualan_idpenjualan = ' . (int) $head['idpenjualan']
        );
    }
}

$rows = rows('select * from v_penjualan order by idpenjualan desc');

page_start('penjualan', $err);
?>
<?php if ($daftar): ?>
    <table>
        <tr>
            <th>idbarang</th>
            <th>nama</th>
            <th>satuan</th>
            <th>harga</th>
            <th>stok</th>
        </tr>
        <?php foreach ($daftar as $d): ?>
            <tr>
                <td><?= e($d['idbarang']) ?></td>
                <td><?= e($d['nama']) ?></td>
                <td><?= e($d['nama_satuan']) ?></td>
                <td><?= e($d['harga']) ?></td>
                <td><?= e($d['stok']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form method="post">
        <p>barang
            <select name="idbarang">
                <?php foreach ($daftar as $d): ?>
                    <option value="<?= e($d['idbarang']) ?>">
                        <?= e($d['idbarang'] . ' - ' . $d['nama'] . ' (stok ' . $d['stok'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>jumlah
            <input type="number" name="jumlah" value="1">
        </p>
        <p>user
            <select name="iduser">
                <?php foreach ($users as $u): ?>
                    <?php $mu = $margin_user[(int) $u['iduser']] ?? null; ?>
                    <option value="<?= e($u['iduser']) ?>">
                        <?= e($u['iduser'] . ' - ' . $u['username']) ?><?= $mu ? ' (margin ' . e($mu['persen']) . '%)' : ' (tanpa margin)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <button>jual</button>
        </p>
    </form>
<?php endif; ?>

<br>
<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>status_teks</th>
        <th>user</th>
        <th>margin</th>
        <th>subtotal</th>
        <th>ppn</th>
        <th>total</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idpenjualan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['margin_persen']) ?></td>
            <td><?= e($r['subtotal_nilai']) ?></td>
            <td><?= e($r['ppn']) ?></td>
            <td><?= e($r['total_nilai']) ?></td>
            <td>
                <a href="penjualan.php?id=<?= e($r['idpenjualan']) ?>">detail</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($head): ?>
    <h2>penjualan <?= e($head['idpenjualan']) ?></h2>
    <table>
        <tr><th>tanggal</th><td><?= e($head['created_at']) ?></td></tr>
        <tr><th>status_teks</th><td><?= e($head['status_teks']) ?></td></tr>
        <tr><th>user</th><td><?= e($head['username']) ?></td></tr>
        <tr><th>margin</th><td><?= e($head['margin_persen']) ?>%</td></tr>
        <tr><th>subtotal</th><td><?= e($head['subtotal_nilai']) ?></td></tr>
        <tr><th>ppn</th><td><?= e($head['ppn']) ?></td></tr>
        <tr><th>total</th><td><?= e($head['total_nilai']) ?></td></tr>
    </table>
    <br>
    <table>
        <tr>
            <th>barang</th>
            <th>nama</th>
            <th>harga_satuan</th>
            <th>jumlah</th>
            <th>subtotal</th>
        </tr>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['barang_idbarang']) ?></td>
                <td><?= e($it['nama']) ?></td>
                <td><?= e($it['harga_satuan']) ?></td>
                <td><?= e($it['jumlah']) ?></td>
                <td><?= e($it['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php page_end(); ?>
