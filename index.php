<?php

require 'config.php';

wajib_login();

$u = current_user();

$menu = [
    'role'             => 'v_role',
    'satuan'           => 'v_satuan',
    'vendor'           => 'v_vendor',
    'user'             => 'v_user',
    'margin_penjualan' => 'v_margin_penjualan',
    'barang'           => 'v_barang',
    'pengadaan'        => 'v_pengadaan',
    'penerimaan'       => 'v_penerimaan',
    'penjualan'        => 'v_penjualan',
    'kartu_stok'       => 'v_kartu_stok',
];

page_start('owobat');

echo '<p>hello ' . e($u['nama_role'] . ' ' . $u['username']) . '</p>';
echo '<form method="post" action="logout.php" class="inline"><button>logout</button></form>';

$ada = false;

foreach ($menu as $page => $view) {
    if (!boleh_akses($page)) {
        continue;
    }

    $ada = true;

    $jumlah = row('select count(*) as n from ' . $view)['n'];

    echo '<p>';
    echo '<a href="' . e($page) . '.php">' . e($page) . '</a> (' . (int) $jumlah . ')';
    echo '</p>';
}

if (!$ada) {
    echo '<p>role ini belum punya halaman lain.</p>';
}

page_end();
