<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        if (post_str('act') === 'delete') {
            run_query('delete from vendor where idvendor = ?', 'i', $cur);
        } elseif ($cur > 0) {
            run_query(
                'update vendor set nama_vendor = ?, badan_hukum = ?, status = ? where idvendor = ?',
                'sssi',
                post_str('nama_vendor'),
                post_str('badan_hukum'),
                post_str('status'),
                $cur
            );
        } else {
            run_query(
                'insert into vendor (idvendor, nama_vendor, badan_hukum, status) values (?, ?, ?, ?)',
                'isss',
                post_int('idvendor'),
                post_str('nama_vendor'),
                post_str('badan_hukum'),
                post_str('status')
            );
        }

        redirect('vendor.php');
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row(
        'select * from v_vendor where idvendor = ?',
        'i',
        (int) $_GET['edit']
    );
}

$rows = rows('select * from v_vendor order by idvendor');

page_start('vendor', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idvendor'] ?? 0) ?>">

    <p>idvendor
        <input name="idvendor" value="<?= e($edit['idvendor'] ?? '') ?>" <?= $edit ? 'disabled' : '' ?>>
    </p>
    <p>nama_vendor
        <input name="nama_vendor" value="<?= e($edit['nama_vendor'] ?? '') ?>">
    </p>
    <p>badan_hukum
        <select name="badan_hukum">
            <option value="P" <?= ($edit['badan_hukum'] ?? '') === 'P' ? 'selected' : '' ?>>PT</option>
            <option value="C" <?= ($edit['badan_hukum'] ?? '') === 'C' ? 'selected' : '' ?>>CV</option>
            <option value="U" <?= ($edit['badan_hukum'] ?? '') === 'U' ? 'selected' : '' ?>>UD</option>
        </select>
    </p>
    <p>status
        <select name="status">
            <option value="A" <?= ($edit['status'] ?? 'A') === 'A' ? 'selected' : '' ?>>Aktif</option>
            <option value="N" <?= ($edit['status'] ?? '') === 'N' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="vendor.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>idvendor</th>
        <th>nama_vendor</th>
        <th>badan_hukum</th>
        <th>badan_hukum_teks</th>
        <th>status</th>
        <th>status_teks</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idvendor']) ?></td>
            <td><?= e($r['nama_vendor']) ?></td>
            <td><?= e($r['badan_hukum']) ?></td>
            <td><?= e($r['badan_hukum_teks']) ?></td>
            <td><?= e($r['status']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td>
                <a href="vendor.php?edit=<?= e($r['idvendor']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['idvendor']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
