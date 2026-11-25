<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        if (post_str('act') === 'delete') {
            run_query('delete from satuan where idsatuan = ?', 'i', $cur);
        } elseif ($cur > 0) {
            run_query(
                'update satuan set nama_satuan = ?, status = ? where idsatuan = ?',
                'sii',
                post_str('nama_satuan'),
                post_int('status'),
                $cur
            );
        } else {
            run_query(
                'insert into satuan (idsatuan, nama_satuan, status) values (?, ?, ?)',
                'isi',
                post_int('idsatuan'),
                post_str('nama_satuan'),
                post_int('status')
            );
        }

        redirect('satuan.php');
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row('select * from v_satuan where idsatuan = ?', 'i', (int) $_GET['edit']);
}

$rows = rows('select * from v_satuan order by idsatuan');

page_start('satuan', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idsatuan'] ?? 0) ?>">

    <p>idsatuan
        <input name="idsatuan" value="<?= e($edit['idsatuan'] ?? '') ?>" <?= $edit ? 'disabled' : '' ?>>
    </p>
    <p>nama_satuan
        <input name="nama_satuan" value="<?= e($edit['nama_satuan'] ?? '') ?>">
    </p>
    <p>status
        <input type="number" name="status" value="<?= e($edit['status'] ?? 1) ?>">
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="satuan.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>idsatuan</th>
        <th>nama_satuan</th>
        <th>status</th>
        <th>status_teks</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idsatuan']) ?></td>
            <td><?= e($r['nama_satuan']) ?></td>
            <td><?= e($r['status']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td>
                <a href="satuan.php?edit=<?= e($r['idsatuan']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['idsatuan']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
