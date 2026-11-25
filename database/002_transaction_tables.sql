create table pengadaan (
    idpengadaan bigint not null auto_increment,
    created_at timestamp null default null,
    user_iduser int not null,
    status char(1) default null,
    vendor_idvendor int not null,
    subtotal_nilai int default null,
    ppn int default null,
    total_nilai int default null,
    primary key (idpengadaan),
    key fk_pengadaan_user1 (user_iduser),
    key fk_pengadaan_vendor1 (vendor_idvendor),
    constraint fk_pengadaan_user1 foreign key (user_iduser) references user (iduser),
    constraint fk_pengadaan_vendor1 foreign key (vendor_idvendor) references vendor (idvendor)
);

create table detail_pengadaan (
    iddetail_pengadaan bigint not null auto_increment,
    harga_satuan int default null,
    jumlah int default null,
    sub_total int default null,
    barang_idbarang int not null,
    pengadaan_idpengadaan bigint not null,
    primary key (iddetail_pengadaan),
    key fk_detail_pengadaan_barang1 (barang_idbarang),
    key fk_detail_pengadaan_pengadaan1 (pengadaan_idpengadaan),
    constraint fk_detail_pengadaan_barang1 foreign key (barang_idbarang) references barang (idbarang),
    constraint fk_detail_pengadaan_pengadaan1 foreign key (pengadaan_idpengadaan) references pengadaan (idpengadaan)
);

create table penerimaan (
    idpenerimaan bigint not null auto_increment,
    created_at timestamp null default null,
    status char(1) default null,
    pengadaan_idpengadaan bigint not null,
    user_iduser int not null,
    primary key (idpenerimaan),
    key fk_penerimaan_pengadaan1 (pengadaan_idpengadaan),
    key fk_penerimaan_user1 (user_iduser),
    constraint fk_penerimaan_pengadaan1 foreign key (pengadaan_idpengadaan) references pengadaan (idpengadaan),
    constraint fk_penerimaan_user1 foreign key (user_iduser) references user (iduser)
);

create table detail_penerimaan (
    iddetail_penerimaan bigint not null auto_increment,
    barang_idbarang int not null,
    penerimaan_idpenerimaan bigint not null,
    jumlah_terima int default null,
    harga_satuan_terima int default null,
    sub_total_terima int default null,
    primary key (iddetail_penerimaan),
    key fk_detail_penerimaan_barang1 (barang_idbarang),
    key fk_detail_penerimaan_penerimaan1 (penerimaan_idpenerimaan),
    constraint fk_detail_penerimaan_barang1 foreign key (barang_idbarang) references barang (idbarang),
    constraint fk_detail_penerimaan_penerimaan1 foreign key (penerimaan_idpenerimaan) references penerimaan (idpenerimaan)
);

create table penjualan (
    idpenjualan int not null auto_increment,
    created_at timestamp null default null,
    subtotal_nilai int default null,
    ppn int default null,
    total_nilai int default null,
    user_iduser int not null,
    margin_penjualan_idmargin_penjualan int not null,
    status char(1) not null default 'P',
    primary key (idpenjualan),
    key fk_penjualan_margin_penjualan1 (margin_penjualan_idmargin_penjualan),
    key fk_penjualan_user1 (user_iduser),
    constraint fk_penjualan_margin_penjualan1 foreign key (margin_penjualan_idmargin_penjualan) references margin_penjualan (idmargin_penjualan),
    constraint fk_penjualan_user1 foreign key (user_iduser) references user (iduser)
);

create table detail_penjualan (
    iddetail_penjualan bigint not null auto_increment,
    harga_satuan int default null,
    jumlah int default null,
    subtotal int default null,
    penjualan_idpenjualan int not null,
    barang_idbarang int not null,
    primary key (iddetail_penjualan),
    key fk_detail_penjualan_barang1 (barang_idbarang),
    key fk_detail_penjualan_penjualan1 (penjualan_idpenjualan),
    constraint fk_detail_penjualan_barang1 foreign key (barang_idbarang) references barang (idbarang),
    constraint fk_detail_penjualan_penjualan1 foreign key (penjualan_idpenjualan) references penjualan (idpenjualan)
);

create table retur (
    idretur bigint not null auto_increment,
    created_at timestamp null default null,
    penerimaan_idpenerimaan bigint not null,
    user_iduser int not null,
    status char(1) not null default 'P',
    primary key (idretur),
    key fk_retur_penerimaan1 (penerimaan_idpenerimaan),
    key fk_retur_user1 (user_iduser),
    constraint fk_retur_penerimaan1 foreign key (penerimaan_idpenerimaan) references penerimaan (idpenerimaan),
    constraint fk_retur_user1 foreign key (user_iduser) references user (iduser)
);

create table detail_retur (
    iddetail_retur int not null auto_increment,
    jumlah int default null,
    alasan varchar(200) default null,
    detail_penerimaan_iddetail_penerimaan bigint not null,
    retur_idretur bigint not null,
    primary key (iddetail_retur),
    key fk_detail_retur_detail_penerimaan1 (detail_penerimaan_iddetail_penerimaan),
    key fk_detail_retur_retur1 (retur_idretur),
    constraint fk_detail_retur_detail_penerimaan1 foreign key (detail_penerimaan_iddetail_penerimaan) references detail_penerimaan (iddetail_penerimaan),
    constraint fk_detail_retur_retur1 foreign key (retur_idretur) references retur (idretur)
);

create table kartu_stok (
    idkartu_stok bigint not null auto_increment,
    jenis_transaksi char(1) default null,
    masuk int default null,
    keluar int default null,
    stock int default null,
    created_at timestamp null default null,
    idtransaksi int default null,
    barang_idbarang int not null,
    primary key (idkartu_stok),
    key fk_kartu_stok_barang1 (barang_idbarang),
    constraint fk_kartu_stok_barang1 foreign key (barang_idbarang) references barang (idbarang)
);
