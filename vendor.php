<?php

require 'config.php';

wajib_akses('vendor');

$err = '';
$cur = post_int('cur');

if (is_post()) {
    try {
        if (post_str('act') === 'delete') {
            run_query('delete from vendor where idvendor = ?', 'i', $cur);
            redirect('vendor.php');
        } else {
            $nama = post_str('nama_vendor');

            if ($nama === '') {
                $err = 'nama_vendor wajib diisi.';
            } else {
                if ($cur > 0) {
                    run_query(
                        'update vendor
                            set nama_vendor = ?, badan_hukum = ?, status = ?
                            where idvendor = ?',
                        'sssi',
                        $nama,
                        post_str('badan_hukum'),
                        post_str('status'),
                        $cur
                    );
                } else {
                    run_query(
                        'insert into vendor (nama_vendor, badan_hukum, status) values
                            (?, ?, ?)',
                        'sss',
                        $nama,
                        post_str('badan_hukum'),
                        post_str('status')
                    );
                }

                redirect('vendor.php');
            }
        }
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

    <?php if ($edit): ?>
        <p>idvendor
            <input value="<?= e($edit['idvendor']) ?>" disabled>
        </p>
    <?php endif; ?>
    <p>nama_vendor
        <input name="nama_vendor" value="<?= e($edit['nama_vendor'] ?? '') ?>" required>
    </p>
    <p>badan_hukum
        <select name="badan_hukum">
            <option value="P" <?= ($edit['badan_hukum_teks'] ?? '') === 'PT' ? 'selected' : '' ?>>PT</option>
            <option value="C" <?= ($edit['badan_hukum_teks'] ?? '') === 'CV' ? 'selected' : '' ?>>CV</option>
            <option value="U" <?= ($edit['badan_hukum_teks'] ?? '') === 'UD' ? 'selected' : '' ?>>UD</option>
        </select>
    </p>
    <p>status
        <select name="status">
            <option value="A" <?= ($edit['status_teks'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="N" <?= ($edit['status_teks'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
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
        <th>badan_hukum_teks</th>
        <th>status_teks</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idvendor']) ?></td>
            <td><?= e($r['nama_vendor']) ?></td>
            <td><?= e($r['badan_hukum_teks']) ?></td>
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
