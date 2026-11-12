<?php

require 'config.php';

$err = '';

$users = rows('select iduser, username from v_user order by iduser');

if (is_post()) {
    try {
        $idpengadaan = post_int('idpengadaan');
        $iduser      = post_int('iduser');

        $ada = row(
            'select count(*) as n
               from v_sisa_pengadaan
              where idpengadaan = ?
                and sisa > 0',
            'i',
            $idpengadaan
        );
        $u = row('select iduser from v_user where iduser = ?', 'i', $iduser);

        if ((int) $ada['n'] === 0) {
            $err = 'Pengadaan sudah diterima seluruhnya.';
        } elseif (!$u) {
            $err = 'User tidak ditemukan.';
        } else {
            begin();
            run_query('call sp_terima_pengadaan(?, ?)', 'ii', $idpengadaan, $iduser);
            commit();

            redirect('penerimaan.php');
        }
    } catch (mysqli_sql_exception $ex) {
        rollback();
        $err = $ex->getMessage();
    }
}

$open = rows(
    'select idpengadaan, created_at, nama_vendor, nama, jumlah, sisa
       from v_sisa_pengadaan
      where sisa > 0
      order by idpengadaan'
);

$head  = null;
$items = [];
if (isset($_GET['id'])) {
    $head = row(
        'select idpenerimaan, created_at, status, status_teks, nama_vendor, username, total_nilai
           from v_penerimaan
          where idpenerimaan = ?',
        'i',
        (int) $_GET['id']
    );

    if ($head) {
        $items = rows(
            'select barang_idbarang, nama, jumlah_terima, harga_satuan_terima, sub_total_terima
               from v_detail_penerimaan
              where penerimaan_idpenerimaan = ' . (int) $head['idpenerimaan']
        );
    }
}

$rows = rows(
    'select idpenerimaan, created_at, status, status_teks, nama_vendor, username, total_nilai
       from v_penerimaan
      order by idpenerimaan desc'
);

page_start('penerimaan', $err);
?>
<?php if ($open): ?>
    <table>
        <tr>
            <th>idpengadaan</th>
            <th>tanggal</th>
            <th>vendor</th>
            <th>barang</th>
            <th>jumlah</th>
            <th>sisa</th>
            <th>terima</th>
        </tr>
        <?php foreach ($open as $o): ?>
            <tr>
                <td><?= e($o['idpengadaan']) ?></td>
                <td><?= e($o['created_at']) ?></td>
                <td><?= e($o['nama_vendor']) ?></td>
                <td><?= e($o['nama']) ?></td>
                <td><?= e($o['jumlah']) ?></td>
                <td><?= e($o['sisa']) ?></td>
                <td>
                    <form method="post" class="inline">
                        <input type="hidden" name="idpengadaan" value="<?= e($o['idpengadaan']) ?>">
                        <select name="iduser">
                            <?php foreach ($users as $u): ?>
                                <option value="<?= e($u['iduser']) ?>">
                                    <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button>terima</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>tidak ada pengadaan yang menunggu penerimaan</p>
<?php endif; ?>

<br><br>

<?php if ($head): ?>
    <h2>penerimaan <?= e($head['idpenerimaan']) ?></h2>
    <table>
        <tr><th>tanggal</th><td><?= e($head['created_at']) ?></td></tr>
        <tr><th>status</th><td><?= e($head['status']) ?></td></tr>
        <tr><th>status_teks</th><td><?= e($head['status_teks']) ?></td></tr>
        <tr><th>vendor</th><td><?= e($head['nama_vendor']) ?></td></tr>
        <tr><th>user</th><td><?= e($head['username']) ?></td></tr>
        <tr><th>total</th><td><?= e($head['total_nilai']) ?></td></tr>
    </table>
    <table>
        <tr>
            <th>barang</th>
            <th>nama</th>
            <th>jumlah_terima</th>
            <th>harga_satuan_terima</th>
            <th>sub_total_terima</th>
        </tr>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['barang_idbarang']) ?></td>
                <td><?= e($it['nama']) ?></td>
                <td><?= e($it['jumlah_terima']) ?></td>
                <td><?= e($it['harga_satuan_terima']) ?></td>
                <td><?= e($it['sub_total_terima']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>status</th>
        <th>status_teks</th>
        <th>vendor</th>
        <th>user</th>
        <th>total</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idpenerimaan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['status']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['nama_vendor']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['total_nilai']) ?></td>
            <td>
                <a href="penerimaan.php?id=<?= e($r['idpenerimaan']) ?>">detail</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
