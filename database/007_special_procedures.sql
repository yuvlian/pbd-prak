drop procedure if exists sp_simpan_user;
drop procedure if exists sp_terima_pengadaan;
drop procedure if exists sp_aktifkan_margin;

delimiter //

create procedure sp_simpan_user(
    in p_iduser    int,
    in p_username  varchar(45),
    in p_password  varchar(100),
    in p_idrole    int,
    in p_is_update tinyint
)
begin
    if p_is_update = 0 then
        insert into user
            (
                username,
                password,
                role_idrole
            )
        values
            (
                p_username,
                sha2(p_password, 256),
                p_idrole
            );

    elseif p_password = '' then
        update user
            set username = p_username, role_idrole = p_idrole
            where iduser = p_iduser;

    else
        update user
            set
                username = p_username,
                password = sha2(p_password, 256),
                role_idrole = p_idrole
            where iduser = p_iduser;
    end if;
end//

create procedure sp_terima_pengadaan(
    in p_idpenerimaan bigint,
    in p_idpengadaan bigint,
    in p_barang_idbarang int,
    in p_jumlah int
)
begin
    declare v_harga int;
    declare v_baris int;

    if p_jumlah > 0 and p_jumlah <= fn_sisa_pesanan(p_idpengadaan, p_barang_idbarang) then
        select min(harga_satuan) 
            into v_harga
            from detail_pengadaan
            where pengadaan_idpengadaan = p_idpengadaan
            and barang_idbarang = p_barang_idbarang;

        if v_harga is not null then
            insert into detail_penerimaan
                (
                    penerimaan_idpenerimaan,
                    barang_idbarang,
                    jumlah_terima,
                    harga_satuan_terima,
                    sub_total_terima
                )
            values
                (
                    p_idpenerimaan,
                    p_barang_idbarang,
                    p_jumlah,
                    v_harga,
                    v_harga * p_jumlah
                );

            select count(*)
                into v_baris
                from detail_pengadaan dp
                where dp.pengadaan_idpengadaan = p_idpengadaan
                and fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang) > 0;

            update pengadaan p
                set p.status = if(v_baris > 0, 'P', 'S')
                where p.idpengadaan = p_idpengadaan;
        end if;
    end if;
end//

create procedure sp_aktifkan_margin(
    in p_idmargin_penjualan int,
    in p_iduser int
)
begin
    declare v_lock int;
    declare v_exists int default 0;

    start transaction;

    select idmargin_penjualan
        into v_lock
        from margin_penjualan
        where user_iduser = p_iduser
        order by idmargin_penjualan
        limit 1
        for update;

    select count(*)
        into v_exists
        from margin_penjualan
        where idmargin_penjualan = p_idmargin_penjualan;

    if v_exists > 0 then
        update margin_penjualan
            set status = 0
            where user_iduser = p_iduser
            and status = 1
            and idmargin_penjualan <> p_idmargin_penjualan;

        update margin_penjualan
            set
                status = 1,
                user_iduser = p_iduser
            where idmargin_penjualan = p_idmargin_penjualan;
    end if;

    commit;
end//

delimiter ;
