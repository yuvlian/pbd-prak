<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        if (post_str('act') === 'delete') {
            run_query('delete from role where idrole = ?', 'i', $cur);
        } elseif ($cur > 0) {
            run_query('update role set nama_role = ? where idrole = ?', 'si', post_str('nama_role'), $cur);
        } else {
            run_query('insert into role (idrole, nama_role) values (?, ?)', 'is', post_int('idrole'), post_str('nama_role'));
        }

        redirect('role.php');
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row('select * from v_role where idrole = ?', 'i', (int) $_GET['edit']);
}

$rows = rows('select * from v_role order by idrole');

page_start('role', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['idrole'] ?? 0) ?>">

    <p>idrole
        <input name="idrole" value="<?= e($edit['idrole'] ?? '') ?>" <?= $edit ? 'disabled' : '' ?>>
    </p>
    <p>nama_role
        <input name="nama_role" value="<?= e($edit['nama_role'] ?? '') ?>">
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="role.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>idrole</th>
        <th>nama_role</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idrole']) ?></td>
            <td><?= e($r['nama_role']) ?></td>
            <td>
                <a href="role.php?edit=<?= e($r['idrole']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['idrole']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
