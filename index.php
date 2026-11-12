<?php

require 'config.php';

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

foreach ($menu as $page => $view) {
    $jumlah = row('select count(*) as n from ' . $view)['n'];

    echo '<p>';
    echo '<a href="' . e($page) . '.php">' . e($page) . '</a> (' . (int) $jumlah . ')';
    echo '</p>';
}

page_end();
