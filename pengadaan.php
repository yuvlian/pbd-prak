<?php

require 'config.php';

$err = '';

$vendors = rows("select * from v_vendor where status = 'A' order by nama_vendor");
$stok    = rows('select * from v_stok_barang where status = 1 order by nama');
$users   = rows('select * from v_user order by iduser');

if (is_post()) {
    try {
        $idbarang = post_int('idbarang');
        $idvendor = post_int('vendor_idvendor');
        $iduser   = post_int('iduser');
        $jumlah   = post_int('jumlah');

        $b = row('select * from v_barang where idbarang = ? and status = 1', 'i', $idbarang);
        $v = row("select * from v_vendor where idvendor = ? and status = 'A'", 'i', $idvendor);
        $u = row('select * from v_user where iduser = ?', 'i', $iduser);

        if (!$b) {
            $err = 'Barang tidak ditemukan atau sudah tidak aktif.';
        } elseif (!$v) {
            $err = 'Vendor tidak valid atau sudah tidak aktif.';
        } elseif (!$u) {
            $err = 'User tidak ditemukan.';
        } elseif ($jumlah < 1) {
            $err = 'Jumlah harus lebih dari nol.';
        } else {
            $harga    = (int) $b['harga'];
            $subtotal = $harga * $jumlah;
            $ppn      = 0;
            $total    = $subtotal;

            begin();
            run_query(
                'insert into pengadaan (created_at, user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai)
                 values (now(), ?, ?, ?, ?, ?, ?)',
                'isiiii',
                $iduser,
                'P',
                $idvendor,
                $subtotal,
                $ppn,
                $total
            );
            $idpengadaan = insert_id();

            run_query(
                'insert into detail_pengadaan (harga_satuan, jumlah, sub_total, barang_idbarang, pengadaan_idpengadaan)
                 values (?, ?, ?, ?, ?)',
                'iiiii',
                $harga,
                $jumlah,
                $subtotal,
                $idbarang,
                $idpengadaan
            );
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
            'select * from v_detail_pengadaan
              where pengadaan_idpengadaan = ' . (int) $head['idpengadaan']
        );
    }
}

$rows = rows('select * from v_pengadaan order by idpengadaan desc');

page_start('pengadaan', $err);
?>
<form method="post">
    <p>vendor
        <select name="vendor_idvendor">
            <?php foreach ($vendors as $v): ?>
                <option value="<?= e($v['idvendor']) ?>">
                    <?= e($v['idvendor'] . ' - ' . $v['nama_vendor']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>barang
        <select name="idbarang">
            <?php foreach ($stok as $b): ?>
                <option value="<?= e($b['idbarang']) ?>">
                    <?= e($b['idbarang'] . ' - ' . $b['nama'] . ' (' . $b['harga'] . ')') ?>
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
                <option value="<?= e($u['iduser']) ?>">
                    <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <button>tambah</button>
    </p>
</form>

<?php if ($head): ?>
    <h2>pengadaan <?= e($head['idpengadaan']) ?></h2>
    <table>
        <tr><th>tanggal</th><td><?= e($head['created_at']) ?></td></tr>
        <tr><th>status</th><td><?= e($head['status']) ?></td></tr>
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
<br>
<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>status</th>
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
            <td><?= e($r['status']) ?></td>
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
<?php page_end(); ?>
