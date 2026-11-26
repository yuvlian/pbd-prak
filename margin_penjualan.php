<?php

require 'config.php';

wajib_akses('margin_penjualan');

$err = '';

if (is_post()) {
    try {
        $act = post_str('act');
        $id  = post_int('id');

        if ($act === 'delete') {
            run_query('delete from margin_penjualan where idmargin_penjualan = ?', 'i', $id);
            redirect('margin_penjualan.php');
        } elseif ($act === 'toggle') {
            $m = row(
                'select status, user_iduser
                 from margin_penjualan
                 where idmargin_penjualan = ?',
                'i',
                $id
            );

            if (!$m) {
                $err = 'margin tidak ditemukan.';
            } elseif ((int) $m['status'] === 1) {
                run_query(
                    'update margin_penjualan set status = 0 where idmargin_penjualan = ?',
                    'i',
                    $id
                );
                redirect('margin_penjualan.php');
            } else {
                run_query(
                    'call sp_aktifkan_margin(?, ?)',
                    'ii',
                    $id,
                    (int) $m['user_iduser']
                );
                redirect('margin_penjualan.php');
            }
        } else {
            $persen = (float) ($_POST['persen'] ?? 0);
            $iduser = post_int('iduser');

            if ($persen < 0) {
                $err = 'persen tidak boleh negatif.';
            } elseif (!row('select iduser from user where iduser = ?', 'i', $iduser)) {
                $err = 'user tidak ditemukan.';
            } else {
                run_query(
                    'insert into margin_penjualan
                        (created_at, persen, status, user_iduser) values
                        (now(), ?, 0, ?)',
                    'di',
                    $persen,
                    $iduser
                );
                redirect('margin_penjualan.php');
            }
        }
    } catch (mysqli_sql_exception $ex) {
        $err = $ex->getMessage();
    }
}

$users = rows('select * from v_user order by iduser');
$rows  = rows('select * from v_margin_penjualan order by idmargin_penjualan');

page_start('margin_penjualan', $err);
?>
<form method="post">
    <input type="hidden" name="act" value="tambah">

    <p>persen
        <input type="number" step="0.1" name="persen" value="10">
    </p>
    <p>iduser
        <select name="iduser">
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['iduser']) ?>">
                    <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p><button>tambah</button></p>
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
        <?php $aktif = ($r['status_teks'] ?? '') === 'Aktif'; ?>
        <tr>
            <td><?= e($r['idmargin_penjualan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['persen']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['updated_at']) ?></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="act" value="toggle">
                    <input type="hidden" name="id" value="<?= e($r['idmargin_penjualan']) ?>">
                    <button><?= $aktif ? 'nonaktifkan' : 'aktifkan' ?></button>
                </form>
                <form method="post" class="inline" onsubmit="return confirm('hapus?')">
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="id" value="<?= e($r['idmargin_penjualan']) ?>">
                    <button>hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_end(); ?>
