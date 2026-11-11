<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        // input datetime-local ("2026-11-11T14:30") -> format mysql
        $created = str_replace('T', ' ', post_str('created_at'));
        $persen  = (float) ($_POST['persen'] ?? 0);
        $status  = post_int('status');
        $iduser  = post_int('iduser');

        if (post_str('act') === 'delete') {
            run_query('delete from margin_penjualan where idmargin_penjualan = ?', 'i', $cur);
        } elseif ($cur > 0) {
            run_query(
                'update margin_penjualan
                    set created_at = ?, persen = ?, status = ?, iduser = ?
                  where idmargin_penjualan = ?',
                'sdiii',
                $created,
                $persen,
                $status,
                $iduser,
                $cur
            );
        } else {
            run_query(
                'insert into margin_penjualan (idmargin_penjualan, created_at, persen, status, iduser)
                 values (?, ?, ?, ?, ?)',
                'isdii',
                post_int('idmargin_penjualan'),
                $created,
                $persen,
                $status,
                $iduser
            );
        }

        redirect('margin_penjualan.php');
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row(
        'select idmargin_penjualan, created_at, persen, status, iduser
           from v_margin_penjualan
          where idmargin_penjualan = ?',
        'i',
        (int) $_GET['edit']
    );
}

$users = rows('select iduser, username from v_user order by iduser');
$rows  = rows(
    'select idmargin_penjualan, created_at, persen, status, status_teks, iduser, username, updated_at
       from v_margin_penjualan
      order by idmargin_penjualan'
);

$created = $edit
    ? str_replace(' ', 'T', substr($edit['created_at'], 0, 16))
    : date('Y-m-d\TH:i');

page_start('margin_penjualan', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idmargin_penjualan'] ?? 0) ?>">

    <p>idmargin_penjualan
        <input name="idmargin_penjualan" value="<?= e($edit['idmargin_penjualan'] ?? '') ?>" <?= $edit ? 'disabled' : '' ?>>
    </p>
    <p>created_at
        <input type="datetime-local" name="created_at" value="<?= e($created) ?>">
    </p>
    <p>persen
        <input type="number" step="0.1" name="persen" value="<?= e($edit['persen'] ?? 10) ?>">
    </p>
    <p>status
        <input type="number" name="status" value="<?= e($edit['status'] ?? 0) ?>">
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
        <th>status</th>
        <th>status_teks</th>
        <th>iduser</th>
        <th>username</th>
        <th>updated_at</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idmargin_penjualan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['persen']) ?></td>
            <td><?= e($r['status']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['iduser']) ?></td>
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
