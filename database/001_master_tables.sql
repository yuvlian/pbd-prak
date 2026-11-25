create table role (
    idrole int not null auto_increment,
    nama_role varchar(100) not null,
    primary key (idrole)
);

create table satuan (
    idsatuan int not null auto_increment,
    nama_satuan varchar(45) not null,
    status tinyint not null,
    primary key (idsatuan)
);

create table vendor (
    idvendor int not null auto_increment,
    nama_vendor varchar(100) not null,
    badan_hukum char(1) not null,
    status char(1) not null,
    primary key (idvendor)
);

create table user (
    iduser int not null auto_increment,
    username varchar(45) not null,
    password varchar(100) not null,
    role_idrole int not null,
    primary key (iduser),
    unique key user_username_unique (username),
    key fk_user_role1 (role_idrole),
    constraint fk_user_role1 foreign key (role_idrole) references role (idrole)
);

create table margin_penjualan (
    idmargin_penjualan int not null auto_increment,
    created_at timestamp null default null,
    persen double default null,
    status tinyint default null,
    user_iduser int not null,
    updated_at timestamp null default null,
    primary key (idmargin_penjualan),
    key fk_margin_penjualan_user1 (user_iduser),
    constraint fk_margin_penjualan_user1 foreign key (user_iduser) references user (iduser)
);

create table barang (
    idbarang int not null auto_increment,
    jenis char(1) not null,
    nama varchar(45) not null,
    satuan_idsatuan int not null,
    status tinyint not null,
    harga int not null,
    primary key (idbarang),
    key fk_barang_satuan (satuan_idsatuan),
    constraint fk_barang_satuan foreign key (satuan_idsatuan) references satuan (idsatuan)
);
