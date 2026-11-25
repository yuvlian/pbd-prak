<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        $persen = (float) ($_POST['persen'] ?? 0);
        $status = post_int('status');
        $iduser = post_int('iduser');

        if (post_str('act') === 'delete') {
            run_query('delete from margin_penjualan where idmargin_penjualan = ?', 'i', $cur);
        } elseif ($cur > 0) {
            run_query(
                'update margin_penjualan
                    set persen = ?, status = ?, user_iduser = ?
                    where idmargin_penjualan = ?',
                'diii',
                $persen,
                $status,
                $iduser,
                $cur
            );

            if ($status === 1) {
                run_query('call sp_aktifkan_margin(?, ?)', 'ii', $cur, $iduser);
            }
        } else {
            run_query(
                'insert into margin_penjualan (created_at, persen, status, user_iduser) values
                    (now(), ?, ?, ?)',
                'dii',
                $persen,
                $status,
                $iduser
            );

            if ($status === 1) {
                run_query('call sp_aktifkan_margin(?, ?)', 'ii', insert_id(), $iduser);
            }
        }

        redirect('margin_penjualan.php');
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row(
        'select * from v_margin_penjualan where idmargin_penjualan = ?',
        'i',
        (int) $_GET['edit']
    );
}

$users = rows('select * from v_user order by iduser');
$rows  = rows('select * from v_margin_penjualan order by idmargin_penjualan');

page_start('margin_penjualan', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idmargin_penjualan'] ?? 0) ?>">

    <?php if ($edit): ?>
        <p>idmargin_penjualan
            <input value="<?= e($edit['idmargin_penjualan']) ?>" disabled>
        </p>
        <p>created_at
            <input value="<?= e($edit['created_at']) ?>" disabled>
        </p>
    <?php endif; ?>
    <p>persen
        <input type="number" step="0.1" name="persen" value="<?= e($edit['persen'] ?? 10) ?>">
    </p>
    <p>status
        <select name="status">
            <option value="1" <?= ($edit['status_teks'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= ($edit['status_teks'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </p>
    <p>iduser
        <select name="iduser">
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['iduser']) ?>" <?= ($edit['iduser'] ?? 0) == $u['iduser'] ? 'selected' : '' ?>>
                    <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="margin_penjualan.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>id</th>
        <th>created_at</th>
        <th>persen</th>
        <th>status_teks</th>
        <th>username</th>
        <th>updated_at</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idmargin_penjualan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['persen']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['updated_at']) ?></td>
            <td>
                <a href="margin_penjualan.php?edit=<?= e($r['idmargin_penjualan']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['idmargin_penjualan']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
