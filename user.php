<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        $username = post_str('username');
        $password = post_str('password');

        if (post_str('act') === 'delete') {
            run_query('delete from user where iduser = ?', 'i', $cur);
            redirect('user.php');
        } elseif ($username === '') {
            $err = 'username wajib diisi.';
        } elseif ($cur === 0 && $password === '') {
            $err = 'password wajib diisi untuk user baru.';
        } else {
            run_query(
                'call sp_simpan_user(?, ?, ?, ?, ?)',
                'issii',
                $cur,
                $username,
                $password,
                post_int('idrole'),
                $cur > 0 ? 1 : 0
            );

            redirect('user.php');
        }
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row('select * from v_user where iduser = ?', 'i', (int) $_GET['edit']);
}

$roles = rows('select * from v_role order by idrole');
$rows  = rows('select * from v_user order by iduser');

page_start('user', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="cur" value="<?= e($edit['iduser'] ?? 0) ?>">

    <?php if ($edit): ?>
        <p>iduser
            <input value="<?= e($edit['iduser']) ?>" disabled>
        </p>
    <?php endif; ?>
    <p>username
        <input name="username" value="<?= e($edit['username'] ?? '') ?>" required>
    </p>
    <p>password
        <input type="password" name="password" <?= $edit ? 'placeholder="kosong = tidak diubah"' : 'required' ?>>
    </p>
    <p>idrole
        <select name="idrole">
            <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['idrole']) ?>" <?= ($edit['idrole'] ?? 0) == $r['idrole'] ? 'selected' : '' ?>>
                    <?= e($r['idrole'] . ' - ' . $r['nama_role']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <button><?= $edit ? 'simpan' : 'tambah' ?></button>
        <?php if ($edit): ?>
            <a href="user.php">batal</a>
        <?php endif; ?>
    </p>
</form>

<table>
    <tr>
        <th>iduser</th>
        <th>username</th>
        <th>idrole</th>
        <th>nama_role</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['iduser']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['idrole']) ?></td>
            <td><?= e($r['nama_role']) ?></td>
            <td>
                <a href="user.php?edit=<?= e($r['iduser']) ?>">edit</a>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="cur" value="<?= e($r['iduser']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
