<?php

require 'config.php';

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        if (post_str('act') === 'delete') {
            run_query('delete from satuan where idsatuan = ?', 'i', $cur);
            redirect('satuan.php');
        } else {
            $nama = post_str('nama_satuan');

            if ($nama === '') {
                $err = 'nama_satuan wajib diisi.';
            } else {
                if ($cur > 0) {
                    run_query(
                        'update satuan set nama_satuan = ?, status = ? where idsatuan = ?',
                        'sii',
                        $nama,
                        post_int('status'),
                        $cur
                    );
                } else {
                    run_query(
                        'insert into satuan (nama_satuan, status) values (?, ?)',
                        'si',
                        $nama,
                        post_int('status')
                    );
                }

                redirect('satuan.php');
            }
        }
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

    <?php if ($edit): ?>
        <p>idsatuan
            <input value="<?= e($edit['idsatuan']) ?>" disabled>
        </p>
    <?php endif; ?>
    <p>nama_satuan
        <input name="nama_satuan" value="<?= e($edit['nama_satuan'] ?? '') ?>" required>
    </p>
    <p>status
        <select name="status">
            <option value="1" <?= ($edit['status_teks'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= ($edit['status_teks'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
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
        <th>status_teks</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idsatuan']) ?></td>
            <td><?= e($r['nama_satuan']) ?></td>
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
