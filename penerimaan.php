<?php

require 'config.php';

$err = '';

$users = rows('select * from v_user order by iduser');

function baris_terbuka(): array
{
    return rows(
        'select *
         from v_sisa_pengadaan
         where sisa > 0
         order by idpengadaan, nama'
    );
}

function validasi_terima(array $qty): array
{
    $terbuka = baris_terbuka();
    $items   = [];

    if (!$terbuka) {
        return [[], 'Tidak ada barang yang bisa diterima.'];
    }

    foreach ($terbuka as $s) {
        $idpengadaan = (int) $s['idpengadaan'];
        $idbarang    = (int) $s['barang_idbarang'];
        $jumlah      = isset($qty[$idpengadaan]) && is_array($qty[$idpengadaan])
            ? (int) ($qty[$idpengadaan][$idbarang] ?? 0)
            : 0;

        if ($jumlah < 0 || $jumlah > (int) $s['sisa']) {
            return [
                [],
                $s['nama']
                    . ' (pengadaan ' . $idpengadaan . '): jumlah harus antara 0 dan '
                    . $s['sisa'] . '.',
            ];
        }

        if ($jumlah > 0) {
            $items[] = [
                'idpengadaan'     => $idpengadaan,
                'barang_idbarang' => $idbarang,
                'nama'            => $s['nama'],
                'sisa'            => (int) $s['sisa'],
                'jumlah'          => $jumlah,
            ];
        }
    }

    if (!$items) {
        return [[], 'Pilih minimal satu barang.'];
    }

    return [$items, ''];
}

$act    = is_post() ? post_str('act') : '';
$iduser = post_int('iduser');
$qty    = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : [];
$items  = [];
$mode   = 'list';

if ($act === 'preview' || $act === 'commit') {
    [$items, $err] = validasi_terima($qty);

    $u = $iduser > 0
        ? row('select * from v_user where iduser = ?', 'i', $iduser)
        : null;

    if ($err === '' && !$u) {
        $err = 'User tidak ditemukan.';
    }

    if ($err === '' && $act === 'preview') {
        $mode = 'confirm';
    } elseif ($err === '') {
        $grup = [];
        foreach ($items as $it) {
            $grup[$it['idpengadaan']][] = $it;
        }

        try {
            begin();
            $masuk = 0;

            foreach ($grup as $pid => $daftar) {
                run_query(
                    'insert into penerimaan
                        (created_at, status, pengadaan_idpengadaan, user_iduser) values
                        (now(), ?, ?, ?)',
                    'sii',
                    'S',
                    $pid,
                    $iduser
                );
                $idpenerimaan = insert_id();

                foreach ($daftar as $it) {
                    run_query(
                        'call sp_terima_pengadaan(?, ?, ?, ?)',
                        'iiii',
                        $idpenerimaan,
                        $pid,
                        $it['barang_idbarang'],
                        $it['jumlah']
                    );
                }

                $masuk += (int) row(
                    'select count(*) as n
                     from detail_penerimaan
                     where penerimaan_idpenerimaan = ?',
                    'i',
                    $idpenerimaan
                )['n'];
            }

            if ($masuk !== count($items)) {
                rollback();
                $err = 'Sisa pesanan sudah berubah. Ulangi penerimaan.';
            } else {
                commit();
                redirect('penerimaan.php');
            }
        } catch (mysqli_sql_exception $ex) {
            rollback();
            $err = $ex->getMessage();
        }
    }
}

if ($iduser === 0 && $users) {
    $iduser = (int) $users[0]['iduser'];
}

$baris_terima = baris_terbuka();

$head         = null;
$detail_items = [];
if ($mode === 'list' && isset($_GET['id'])) {
    $head = row(
        'select * from v_penerimaan where idpenerimaan = ?',
        'i',
        (int) $_GET['id']
    );

    if ($head) {
        $detail_items = rows(
            'select *
             from v_detail_penerimaan
             where penerimaan_idpenerimaan = ' . (int) $head['idpenerimaan']
        );
    }
}

$rows = rows('select * from v_penerimaan order by idpenerimaan desc');

page_start($mode === 'confirm' ? 'konfirmasi penerimaan' : 'penerimaan', $err);
?>
<?php if ($mode === 'confirm'): ?>
    <p>kamu akan terima:</p>
    <table>
        <tr>
            <th>idpengadaan</th>
            <th>barang</th>
            <th>diterima</th>
            <th>sisa setelah</th>
        </tr>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['idpengadaan']) ?></td>
                <td><?= e($it['nama']) ?></td>
                <td><?= e($it['jumlah']) ?></td>
                <td><?= e($it['sisa'] - $it['jumlah']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <form method="post">
        <input type="hidden" name="act" value="commit">
        <input type="hidden" name="iduser" value="<?= e($iduser) ?>">
        <?php foreach ($items as $it): ?>
            <input type="hidden"
                   name="qty[<?= e($it['idpengadaan']) ?>][<?= e($it['barang_idbarang']) ?>]"
                   value="<?= e($it['jumlah']) ?>">
        <?php endforeach; ?>
        <p><button>konfirmasi</button> <a href="penerimaan.php">batal</a></p>
    </form>
<?php elseif ($baris_terima): ?>
    <form method="post">
        <input type="hidden" name="act" value="preview">
        <table>
            <tr>
                <th>idpengadaan</th>
                <th>tanggal</th>
                <th>vendor</th>
                <th>barang</th>
                <th>jumlah</th>
                <th>sisa</th>
                <th>terima</th>
            </tr>
            <?php foreach ($baris_terima as $b): ?>
                <?php
                $pid   = (int) $b['idpengadaan'];
                $bid   = (int) $b['barang_idbarang'];
                $nilai = isset($qty[$pid]) && is_array($qty[$pid]) ? (int) ($qty[$pid][$bid] ?? 0) : 0;
                ?>
                <tr>
                    <td><?= e($pid) ?></td>
                    <td><?= e($b['created_at']) ?></td>
                    <td><?= e($b['nama_vendor']) ?></td>
                    <td><?= e($b['nama']) ?></td>
                    <td><?= e($b['jumlah']) ?></td>
                    <td><?= e($b['sisa']) ?></td>
                    <td>
                        <input type="number" min="0" max="<?= e($b['sisa']) ?>"
                               name="qty[<?= e($pid) ?>][<?= e($bid) ?>]"
                               value="<?= e($nilai) ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p>user
            <select name="iduser">
                <?php foreach ($users as $u): ?>
                    <option value="<?= e($u['iduser']) ?>" <?= (int) $u['iduser'] === $iduser ? 'selected' : '' ?>>
                        <?= e($u['iduser'] . ' - ' . $u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><button>lanjut</button></p>
    </form>
<?php else: ?>
    <p>tidak ada pengadaan yang menunggu penerimaan</p>
<?php endif; ?>

<br>
<table>
    <tr>
        <th>id</th>
        <th>tanggal</th>
        <th>status_teks</th>
        <th>vendor</th>
        <th>user</th>
        <th>total</th>
        <th>aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['idpenerimaan']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['status_teks']) ?></td>
            <td><?= e($r['nama_vendor']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['total_nilai']) ?></td>
            <td>
                <a href="penerimaan.php?id=<?= e($r['idpenerimaan']) ?>">detail</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($head): ?>
    <h2>penerimaan <?= e($head['idpenerimaan']) ?></h2>
    <table>
        <tr><th>tanggal</th><td><?= e($head['created_at']) ?></td></tr>
        <tr><th>status_teks</th><td><?= e($head['status_teks']) ?></td></tr>
        <tr><th>vendor</th><td><?= e($head['nama_vendor']) ?></td></tr>
        <tr><th>user</th><td><?= e($head['username']) ?></td></tr>
        <tr><th>total</th><td><?= e($head['total_nilai']) ?></td></tr>
    </table>
    <br>
    <table>
        <tr>
            <th>barang</th>
            <th>nama</th>
            <th>jumlah_terima</th>
            <th>harga_satuan_terima</th>
            <th>sub_total_terima</th>
        </tr>
        <?php foreach ($detail_items as $it): ?>
            <tr>
                <td><?= e($it['barang_idbarang']) ?></td>
                <td><?= e($it['nama']) ?></td>
                <td><?= e($it['jumlah_terima']) ?></td>
                <td><?= e($it['harga_satuan_terima']) ?></td>
                <td><?= e($it['sub_total_terima']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php page_end(); ?>
