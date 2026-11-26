<?php

require 'config.php';

wajib_akses('pengadaan');

$err  = '';
$act  = is_post() ? post_str('act') : '';
$qty  = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : [];
$mode = 'form';

$idvendor = post_int('vendor_idvendor');
$iduser   = post_int('iduser');

$pesanan     = [];
$subtotal    = 0;
$vendor_teks = '';
$user_teks   = '';

$vendors = rows(
    "select *
     from v_vendor
     where status_teks = 'Aktif'
     order by nama_vendor"
);
$stok    = rows(
    "select *
     from v_stok_barang
     where status_teks = 'Aktif'
     order by nama"
);
$users   = rows('select * from v_user order by iduser');

if (is_post()) {
    try {
        $v = row(
            "select *
             from v_vendor
             where idvendor = ?
             and status_teks = 'Aktif'",
            'i',
            $idvendor
        );
        $u = row('select * from v_user where iduser = ?', 'i', $iduser);

        foreach ($stok as $s) {
            $idbarang = (int) $s['idbarang'];
            $jumlah   = (int) ($qty[$idbarang] ?? 0);

            if ($jumlah < 0) {
                $err = 'Jumlah tidak boleh negatif.';
                break;
            }

            if ($jumlah > 0) {
                $harga = (int) $s['harga'];
                $pesanan[] = [
                    'idbarang' => $idbarang,
                    'nama'     => $s['nama'],
                    'stok'     => (int) $s['stok'],
                    'harga'    => $harga,
                    'jumlah'   => $jumlah,
                    'subtotal' => $harga * $jumlah,
                ];
                $subtotal += $harga * $jumlah;
            }
        }

        if ($err !== '') {
            // negative num
        } elseif (!$v) {
            $err = 'Vendor tidak valid atau sudah tidak aktif.';
        } elseif (!$u) {
            $err = 'User tidak ditemukan.';
        } elseif (!$pesanan) {
            $err = 'Pilih minimal satu barang.';
        } elseif ($act === 'preview') {
            $mode        = 'confirm';
            $vendor_teks = $v['nama_vendor'];
            $user_teks   = $u['username'];
        } else {
            begin();
            run_query(
                'insert into pengadaan
                    (created_at, user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai) values
                    (now(), ?, ?, ?, ?, 0, ?)',
                'isiii',
                $iduser,
                'P',
                $idvendor,
                $subtotal,
                $subtotal
            );
            $idpengadaan = insert_id();

            foreach ($pesanan as $it) {
                run_query(
                    'insert into detail_pengadaan
                        (harga_satuan, jumlah, sub_total, barang_idbarang, pengadaan_idpengadaan) values
                        (?, ?, ?, ?, ?)',
                    'iiiii',
                    $it['harga'],
                    $it['jumlah'],
                    $it['subtotal'],
                    $it['idbarang'],
                    $idpengadaan
                );
            }
            commit();

            redirect('pengadaan.php');
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
        'select * from v_pengadaan where idpengadaan = ?',
        'i',
        (int) $_GET['id']
    );

    if ($head) {
        $items = rows(
            'select *
             from v_detail_pengadaan
             where pengadaan_idpengadaan = ' . (int) $head['idpengadaan']
        );
    }
}

$rows = rows('select * from v_pengadaan order by idpengadaan desc');

page_start($mode === 'confirm' ? 'konfirmasi pengadaan' : 'pengadaan', $err);
?>
<?php if ($mode === 'confirm'): ?>
    <p>vendor: <?= e($vendor_teks) ?><br>user: <?= e($user_teks) ?></p>
    <p>kamu akan pesan:</p>
    <table>
        <tr>
            <th>barang</th>
            <th>stok</th>
            <th>harga</th>
            <th>jumlah</th>
            <th>sub_total</th>
        </tr>
        <?php foreach ($pesanan as $p): ?>
            <tr>
                <td><?= e($p['nama']) ?></td>
                <td><?= e($p['stok']) ?></td>
                <td><?= e($p['harga']) ?></td>
                <td><?= e($p['jumlah']) ?></td>
                <td><?= e($p['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>total: <?= e($subtotal) ?></p>
    <form method="post">
        <input type="hidden" name="act" value="commit">
        <input type="hidden" name="vendor_idvendor" value="<?= e($idvendor) ?>">
        <input type="hidden" name="iduser" value="<?= e($iduser) ?>">
        <?php foreach ($pesanan as $p): ?>
            <input type="hidden" name="qty[<?= e($p['idbarang']) ?>]" value="<?= e($p['jumlah']) ?>">
        <?php endforeach; ?>
        <p><button>konfirmasi</button> <a href="pengadaan.php">batal</a></p>
    </form>
<?php else: ?>
<form method="post">
    <input type="hidden" name="act" value="preview">
    <p>vendor
        <select name="vendor_idvendor">
            <?php foreach ($vendors as $v): ?>
                <option value="<?= e($v['idvendor']) ?>" <?= (int) $v['idvendor'] === $idvendor ? 'selected' : '' ?>>
                    <?= e($v['idvendor'] . ' - ' . $v['nama_vendor']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>user
        <select name="iduser">
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['iduser']) ?>" <?= (int) $u['iduser'] === $iduser ? 'selected' : '' ?>>
                    <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <table>
        <tr>
            <th>idbarang</th>
            <th>nama</th>
            <th>harga</th>
            <th>stok</th>
            <th>jumlah</th>
        </tr>
        <?php foreach ($stok as $b): ?>
            <tr>
                <td><?= e($b['idbarang']) ?></td>
                <td><?= e($b['nama']) ?></td>
                <td><?= e($b['harga']) ?></td>
                <td><?= e($b['stok']) ?></td>
                <td>
                    <input type="number" min="0" name="qty[<?= e($b['idbarang']) ?>]"
                           value="<?= e($qty[$b['idbarang']] ?? 0) ?>">
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>
        <button>lanjut</button>
    </p>
</form>
<?php endif; ?>

<br>
<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>status_teks</th>
        <th>vendor</th>
        <th>user</th>
        <th>subtotal</th>
        <th>ppn</th>
        <th>total</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idpengadaan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['nama_vendor']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['subtotal_nilai']) ?></td>
            <td><?= e($r['ppn']) ?></td>
            <td><?= e($r['total_nilai']) ?></td>
            <td>
                <a href="pengadaan.php?id=<?= e($r['idpengadaan']) ?>">detail</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($head): ?>
    <h2>pengadaan <?= e($head['idpengadaan']) ?></h2>
    <table>
        <tr><th>tanggal</th><td><?= e($head['created_at']) ?></td></tr>
        <tr><th>status_teks</th><td><?= e($head['status_teks']) ?></td></tr>
        <tr><th>vendor</th><td><?= e($head['nama_vendor']) ?></td></tr>
        <tr><th>user</th><td><?= e($head['username']) ?></td></tr>
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
            <th>sub_total</th>
        </tr>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['barang_idbarang']) ?></td>
                <td><?= e($it['nama']) ?></td>
                <td><?= e($it['harga_satuan']) ?></td>
                <td><?= e($it['jumlah']) ?></td>
                <td><?= e($it['sub_total']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php page_end(); ?>
