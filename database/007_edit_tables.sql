alter table detail_retur drop foreign key fk_detretur_detpenerimaan;
alter table detail_retur drop foreign key fk_detretur_retur;
alter table retur drop foreign key fk_retur_penerimaan;
alter table retur drop foreign key fk_retur_user;
alter table detail_penerimaan drop foreign key fk_detpenerimaan_barang;
alter table detail_penerimaan drop foreign key fk_detpenerimaan_penerimaan;
alter table detail_pengadaan drop foreign key fk_detpengadaan_barang;
alter table detail_pengadaan drop foreign key fk_detpengadaan_pengadaan;
alter table detail_penjualan drop foreign key fk_detpenjualan_barang;
alter table detail_penjualan drop foreign key fk_detpenjualan_penjualan;
alter table kartu_stok drop foreign key fk_kartustok_barang;
alter table margin_penjualan drop foreign key fk_margin_user;
alter table penerimaan drop foreign key fk_penerimaan_pengadaan;
alter table penerimaan drop foreign key fk_penerimaan_user;
alter table pengadaan drop foreign key fk_pengadaan_user;
alter table pengadaan drop foreign key fk_pengadaan_vendor;
alter table penjualan drop foreign key fk_penjualan_margin;
alter table penjualan drop foreign key fk_penjualan_user;
alter table user drop foreign key fk_user_role;
alter table barang drop foreign key fk_barang_satuan;

alter table role
    modify idrole int not null auto_increment,
    modify nama_role varchar(100) not null;

alter table satuan
    modify idsatuan int not null auto_increment,
    modify nama_satuan varchar(45) not null,
    modify status tinyint not null;

alter table vendor
    modify idvendor int not null auto_increment,
    modify nama_vendor varchar(100) not null,
    modify badan_hukum char(1) not null,
    modify status char(1) not null;

alter table user
    modify iduser int not null auto_increment,
    modify username varchar(45) not null,
    add unique key user_username_unique (username),
    modify password varchar(100) not null,
    change idrole role_idrole int not null;

alter table margin_penjualan
    modify idmargin_penjualan int not null auto_increment,
    change iduser user_iduser int not null;

alter table barang
    modify idbarang int not null auto_increment,
    modify jenis char(1) not null,
    modify nama varchar(45) not null,
    change idsatuan satuan_idsatuan int not null,
    modify status tinyint not null,
    modify harga int not null;

alter table pengadaan
    modify idpengadaan bigint not null auto_increment,
    change `timestamp` created_at timestamp null default null,
    modify user_iduser int not null,
    modify vendor_idvendor int not null;

alter table detail_pengadaan
    modify iddetail_pengadaan bigint not null auto_increment,
    change idbarang barang_idbarang int not null,
    change idpengadaan pengadaan_idpengadaan bigint not null;

alter table penerimaan
    modify idpenerimaan bigint not null auto_increment,
    change idpengadaan pengadaan_idpengadaan bigint not null,
    change iduser user_iduser int not null;

alter table detail_penerimaan
    modify iddetail_penerimaan bigint not null auto_increment,
    modify barang_idbarang int not null after iddetail_penerimaan;

alter table detail_penerimaan
    change idpenerimaan penerimaan_idpenerimaan bigint not null after barang_idbarang;

alter table penjualan
    modify idpenjualan int not null auto_increment,
    change iduser user_iduser int not null,
    change idmargin_penjualan margin_penjualan_idmargin_penjualan int not null;

alter table penjualan
    add column status char(1) not null default 'P' after margin_penjualan_idmargin_penjualan;

-- biar yg udah ada = selesai
update penjualan set status = 'S';

alter table detail_penjualan
    modify iddetail_penjualan bigint not null auto_increment,
    change idbarang barang_idbarang int not null,
    modify penjualan_idpenjualan int not null;

alter table retur
    modify idretur bigint not null auto_increment,
    change idpenerimaan penerimaan_idpenerimaan bigint not null,
    change iduser user_iduser int not null;

alter table retur
    add column status char(1) not null default 'P' after user_iduser;

alter table detail_retur
    modify iddetail_retur int not null auto_increment;

alter table detail_retur
    change iddetail_penerimaan detail_penerimaan_iddetail_penerimaan bigint not null after alasan;

alter table detail_retur
    change idretur retur_idretur bigint not null after detail_penerimaan_iddetail_penerimaan;

alter table kartu_stok
    modify idkartu_stok bigint not null auto_increment,
    change idbarang barang_idbarang int not null;

alter table barang
    add constraint fk_barang_satuan
        foreign key (satuan_idsatuan) references satuan (idsatuan);

alter table detail_penerimaan
    add constraint fk_detail_penerimaan_barang1
        foreign key (barang_idbarang) references barang (idbarang);

alter table detail_penerimaan
    add constraint fk_detail_penerimaan_penerimaan1
        foreign key (penerimaan_idpenerimaan) references penerimaan (idpenerimaan);

alter table detail_pengadaan
    add constraint fk_detail_pengadaan_barang1
        foreign key (barang_idbarang) references barang (idbarang);

alter table detail_pengadaan
    add constraint fk_detail_pengadaan_pengadaan1
        foreign key (pengadaan_idpengadaan) references pengadaan (idpengadaan);

alter table detail_penjualan
    add constraint fk_detail_penjualan_barang1
        foreign key (barang_idbarang) references barang (idbarang);

alter table detail_penjualan
    add constraint fk_detail_penjualan_penjualan1
        foreign key (penjualan_idpenjualan) references penjualan (idpenjualan);

alter table detail_retur
    add constraint fk_detail_retur_detail_penerimaan1
        foreign key (detail_penerimaan_iddetail_penerimaan) references detail_penerimaan (iddetail_penerimaan);

alter table detail_retur
    add constraint fk_detail_retur_retur1
        foreign key (retur_idretur) references retur (idretur);

alter table kartu_stok
    add constraint fk_kartu_stok_barang1
        foreign key (barang_idbarang) references barang (idbarang);

alter table margin_penjualan
    add constraint fk_margin_penjualan_user1
        foreign key (user_iduser) references user (iduser);

alter table penerimaan
    add constraint fk_penerimaan_pengadaan1
        foreign key (pengadaan_idpengadaan) references pengadaan (idpengadaan);

alter table penerimaan
    add constraint fk_penerimaan_user1
        foreign key (user_iduser) references user (iduser);

alter table pengadaan
    add constraint fk_pengadaan_user1
        foreign key (user_iduser) references user (iduser);

alter table pengadaan
    add constraint fk_pengadaan_vendor1
        foreign key (vendor_idvendor) references vendor (idvendor);

alter table penjualan
    add constraint fk_penjualan_margin_penjualan1
        foreign key (margin_penjualan_idmargin_penjualan) references margin_penjualan (idmargin_penjualan);

alter table penjualan
    add constraint fk_penjualan_user1
        foreign key (user_iduser) references user (iduser);

alter table retur
    add constraint fk_retur_penerimaan1
        foreign key (penerimaan_idpenerimaan) references penerimaan (idpenerimaan);

alter table retur
    add constraint fk_retur_user1
        foreign key (user_iduser) references user (iduser);

alter table user
    add constraint fk_user_role1
        foreign key (role_idrole) references role (idrole);

create or replace view v_user as
    select u.iduser, u.username, u.role_idrole as idrole, r.nama_role
    from user u
    left join role r on r.idrole = u.role_idrole;

create or replace view v_margin_penjualan as
    select m.idmargin_penjualan, m.created_at, m.persen, m.status,
           if(m.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           m.user_iduser as iduser, u.username, m.updated_at
    from margin_penjualan m
    left join user u on u.iduser = m.user_iduser;

create or replace view v_barang as
    select b.idbarang, b.jenis,
           case b.jenis
               when 'O' then 'Obat'
               when 'S' then 'Suplemen'
               when 'A' then 'Alat Kesehatan'
               else b.jenis
           end as jenis_teks,
           b.nama, b.satuan_idsatuan as idsatuan, s.nama_satuan, b.status,
           if(b.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           b.harga
    from barang b
    left join satuan s on s.idsatuan = b.satuan_idsatuan;
