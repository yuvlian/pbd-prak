<?php

require 'config.php';

$err  = '';
$act  = is_post() ? post_str('act') : '';
$qty  = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : [];
$mode = 'form';

$iduser = post_int('iduser');

$users = rows('select * from v_user order by iduser');
$daftar = rows("select * from v_stok_barang where status_teks = 'Aktif' order by nama");

$margin_user = [];
foreach (rows('select user_iduser, idmargin_penjualan, persen from margin_penjualan where status = 1') as $m) {
    $margin_user[(int) $m['user_iduser']] = $m;
}

$baris_jual    = [];
$subtotal      = 0;
$ppn           = 0;
$total         = 0;
$margin_persen = 0.0;
$user_teks     = '';

if (is_post()) {
    try {
        $u = row('select * from v_user where iduser = ?', 'i', $iduser);
        $margin = row(
            'select idmargin_penjualan, persen from margin_penjualan
              where status = 1 and user_iduser = ? limit 1',
            'i',
            $iduser
        );

        foreach ($daftar as $d) {
            $idbarang = (int) $d['idbarang'];
            $jumlah   = (int) ($qty[$idbarang] ?? 0);

            if ($jumlah < 0) {
                $err = 'Jumlah tidak boleh negatif.';
                break;
            }

            if ($jumlah > (int) $d['stok']) {
                $err = $d['nama'] . ': stok tidak mencukupi (tersedia ' . $d['stok'] . ').';
                break;
            }

            if ($jumlah > 0) {
                $harga      = (int) $d['harga'];
                $harga_jual = (int) round($harga + $harga * (float) ($margin['persen'] ?? 0) / 100);
                $sub        = $harga_jual * $jumlah;

                $baris_jual[] = [
                    'idbarang'   => $idbarang,
                    'nama'       => $d['nama'],
                    'harga'      => $harga,
                    'harga_jual' => $harga_jual,
                    'jumlah'     => $jumlah,
                    'subtotal'   => $sub,
                ];
                $subtotal += $sub;
            }
        }

        if ($err !== '') {
            // negatif / stok
        } elseif (!$u) {
            $err = 'User tidak ditemukan.';
        } elseif (!$margin) {
            $err = 'User ini belum punya margin penjualan aktif.';
        } elseif (!$baris_jual) {
            $err = 'Pilih minimal satu barang.';
        } elseif ($act === 'preview') {
            $mode          = 'confirm';
            $margin_persen = (float) $margin['persen'];
            $user_teks     = $u['username'];
            $ppn           = (int) round($subtotal * PPN_PERSEN / 100);
            $total         = $subtotal + $ppn;
        } else {
            $ppn   = (int) round($subtotal * PPN_PERSEN / 100);
            $total = $subtotal + $ppn;

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

            foreach ($baris_jual as $p) {
                run_query(
                    'insert into detail_penjualan (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, barang_idbarang)
                     values (?, ?, ?, ?, ?)',
                    'iiiii',
                    $p['harga_jual'],
                    $p['jumlah'],
                    $p['subtotal'],
                    $idpenjualan,
                    $p['idbarang']
                );

                run_query(
                    'insert into kartu_stok (jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, barang_idbarang)
                     values (?, 0, ?, fn_stok_barang(?) - ?, now(), ?, ?)',
                    'siiiii',
                    'J',
                    $p['jumlah'],
                    $p['idbarang'],
                    $p['jumlah'],
                    $idpenjualan,
                    $p['idbarang']
                );
            }
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

page_start($mode === 'confirm' ? 'konfirmasi penjualan' : 'penjualan', $err);
?>
<?php if ($mode === 'confirm'): ?>
    <p>user: <?= e($user_teks) ?><br>margin: <?= e($margin_persen) ?>%</p>
    <p>kamu akan jual:</p>
    <table>
        <tr>
            <th>barang</th>
            <th>harga</th>
            <th>harga jual</th>
            <th>jumlah</th>
            <th>sub_total</th>
        </tr>
        <?php foreach ($baris_jual as $p): ?>
            <tr>
                <td><?= e($p['nama']) ?></td>
                <td><?= e($p['harga']) ?></td>
                <td><?= e($p['harga_jual']) ?></td>
                <td><?= e($p['jumlah']) ?></td>
                <td><?= e($p['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>subtotal: <?= e($subtotal) ?><br>ppn: <?= e($ppn) ?><br>total: <?= e($total) ?></p>
    <form method="post">
        <input type="hidden" name="act" value="commit">
        <input type="hidden" name="iduser" value="<?= e($iduser) ?>">
        <?php foreach ($baris_jual as $p): ?>
            <input type="hidden" name="qty[<?= e($p['idbarang']) ?>]" value="<?= e($p['jumlah']) ?>">
        <?php endforeach; ?>
        <p><button>konfirmasi</button> <a href="penjualan.php">batal</a></p>
    </form>
<?php elseif ($daftar): ?>
    <form method="post">
        <input type="hidden" name="act" value="preview">
        <p>user
            <select name="iduser">
                <?php foreach ($users as $u): ?>
                    <?php $mu = $margin_user[(int) $u['iduser']] ?? null; ?>
                    <option value="<?= e($u['iduser']) ?>" <?= (int) $u['iduser'] === $iduser ? 'selected' : '' ?>>
                        <?= e($u['iduser'] . ' - ' . $u['username']) ?><?= $mu ? ' (margin ' . e($mu['persen']) . '%)' : ' (tanpa margin)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <table>
            <tr>
                <th>idbarang</th>
                <th>nama</th>
                <th>satuan</th>
                <th>harga</th>
                <th>stok</th>
                <th>jumlah</th>
            </tr>
            <?php foreach ($daftar as $d): ?>
                <tr>
                    <td><?= e($d['idbarang']) ?></td>
                    <td><?= e($d['nama']) ?></td>
                    <td><?= e($d['nama_satuan']) ?></td>
                    <td><?= e($d['harga']) ?></td>
                    <td><?= e($d['stok']) ?></td>
                    <td>
                        <input type="number" min="0" max="<?= e($d['stok']) ?>"
                               name="qty[<?= e($d['idbarang']) ?>]"
                               value="<?= e($qty[$d['idbarang']] ?? 0) ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p><button>lanjut</button></p>
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
