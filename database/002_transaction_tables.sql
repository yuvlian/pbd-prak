create table pengadaan (
    idpengadaan bigint not null,
    timestamp timestamp,
    user_iduser int,
    status char(1),
    vendor_idvendor int,
    subtotal_nilai int,
    ppn int,
    total_nilai int,
    primary key (idpengadaan),
    constraint fk_pengadaan_user
        foreign key (user_iduser) references user (iduser),
    constraint fk_pengadaan_vendor
        foreign key (vendor_idvendor) references vendor (idvendor)
);

create table detail_pengadaan (
    iddetail_pengadaan bigint not null,
    harga_satuan int,
    jumlah int,
    sub_total int,
    idbarang int,
    idpengadaan bigint,
    primary key (iddetail_pengadaan),
    constraint fk_detpengadaan_barang
        foreign key (idbarang) references barang (idbarang),
    constraint fk_detpengadaan_pengadaan
        foreign key (idpengadaan) references pengadaan (idpengadaan)
);

create table penerimaan (
    idpenerimaan bigint not null,
    created_at timestamp,
    status char(1),
    idpengadaan bigint,
    iduser int,
    primary key (idpenerimaan),
    constraint fk_penerimaan_pengadaan
        foreign key (idpengadaan) references pengadaan (idpengadaan),
    constraint fk_penerimaan_user
        foreign key (iduser) references user (iduser)
);

create table detail_penerimaan (
    iddetail_penerimaan bigint not null,
    idpenerimaan bigint,
    barang_idbarang int,
    jumlah_terima int,
    harga_satuan_terima int,
    sub_total_terima int,
    primary key (iddetail_penerimaan),
    constraint fk_detpenerimaan_penerimaan
        foreign key (idpenerimaan) references penerimaan (idpenerimaan),
    constraint fk_detpenerimaan_barang
        foreign key (barang_idbarang) references barang (idbarang)
);

create table penjualan (
    idpenjualan int not null,
    created_at timestamp,
    subtotal_nilai int,
    ppn int,
    total_nilai int,
    iduser int,
    idmargin_penjualan int,
    primary key (idpenjualan),
    constraint fk_penjualan_user
        foreign key (iduser) references user (iduser),
    constraint fk_penjualan_margin
        foreign key (idmargin_penjualan) references margin_penjualan (idmargin_penjualan)
);

create table detail_penjualan (
    iddetail_penjualan bigint not null,
    harga_satuan int,
    jumlah int,
    subtotal int,
    penjualan_idpenjualan int,
    idbarang int,
    primary key (iddetail_penjualan),
    constraint fk_detpenjualan_penjualan
        foreign key (penjualan_idpenjualan) references penjualan (idpenjualan),
    constraint fk_detpenjualan_barang
        foreign key (idbarang) references barang (idbarang)
);

create table retur (
    idretur bigint not null,
    created_at timestamp,
    idpenerimaan bigint,
    iduser int,
    primary key (idretur),
    constraint fk_retur_penerimaan
        foreign key (idpenerimaan) references penerimaan (idpenerimaan),
    constraint fk_retur_user
        foreign key (iduser) references user (iduser)
);

create table detail_retur (
    iddetail_retur int not null,
    jumlah int,
    alasan varchar(200),
    idretur bigint,
    iddetail_penerimaan bigint,
    primary key (iddetail_retur),
    constraint fk_detretur_retur
        foreign key (idretur) references retur (idretur),
    constraint fk_detretur_detpenerimaan
        foreign key (iddetail_penerimaan) references detail_penerimaan (iddetail_penerimaan)
);

create table kartu_stok (
    idkartu_stok bigint not null,
    jenis_transaksi char(1),
    masuk int,
    keluar int,
    stock int,
    created_at timestamp,
    idtransaksi int,
    idbarang int,
    primary key (idkartu_stok),
    constraint fk_kartustok_barang
        foreign key (idbarang) references barang (idbarang)
);
