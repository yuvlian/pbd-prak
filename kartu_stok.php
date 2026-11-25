<?php

require 'config.php';

$rows = rows('select * from v_kartu_stok order by idkartu_stok desc');

page_start('kartu_stok');
?>
<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>barang</th>
        <th>jenis</th>
        <th>masuk</th>
        <th>keluar</th>
        <th>stock</th>
        <th>idtransaksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idkartu_stok']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['nama']) ?></td>
            <td><?= e($r['jenis_teks']) ?></td>
            <td><?= e($r['masuk']) ?></td>
            <td><?= e($r['keluar']) ?></td>
            <td><?= e($r['stock']) ?></td>
            <td><?= e($r['idtransaksi']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
