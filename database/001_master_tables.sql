create table role (
    idrole int not null,
    nama_role varchar(100),
    primary key (idrole)
);

create table satuan (
    idsatuan int not null,
    nama_satuan varchar(45),
    status tinyint,
    primary key (idsatuan)
);

create table vendor (
    idvendor int not null,
    nama_vendor varchar(100),
    badan_hukum char(1),
    status char(1),
    primary key (idvendor)
);

create table user (
    iduser int not null,
    username varchar(45),
    password varchar(100),
    idrole int,
    primary key (iduser),
    constraint fk_user_role
        foreign key (idrole) references role (idrole)
);

create table margin_penjualan (
    idmargin_penjualan int not null,
    created_at timestamp,
    persen double,
    status tinyint,
    iduser int,
    updated_at timestamp,
    primary key (idmargin_penjualan),
    constraint fk_margin_user
        foreign key (iduser) references user (iduser)
);

create table barang (
    idbarang int not null,
    jenis char(1),
    nama varchar(45),
    idsatuan int,
    status tinyint,
    harga int,
    primary key (idbarang),
    constraint fk_barang_satuan
        foreign key (idsatuan) references satuan (idsatuan)
);
