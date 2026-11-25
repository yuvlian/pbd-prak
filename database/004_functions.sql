drop function if exists fn_stok_barang;
drop function if exists fn_sisa_pesanan;

delimiter //

create function fn_stok_barang(p_idbarang int)
returns int
reads sql data
begin
    declare total int;

    select stock into total from kartu_stok
        where barang_idbarang = p_idbarang
        order by idkartu_stok desc
        limit 1;

    return coalesce(total, 0);
end//

create function fn_sisa_pesanan(p_idpengadaan bigint, p_idbarang int)
returns int
reads sql data
begin
    declare v_pesan int;
    declare v_terima int;

    select coalesce(sum(dp.jumlah), 0)
        into v_pesan
        from detail_pengadaan dp
        where dp.pengadaan_idpengadaan = p_idpengadaan
        and dp.barang_idbarang = p_idbarang;

    select coalesce(sum(dpt.jumlah_terima), 0)
        into v_terima
        from detail_penerimaan dpt
        join penerimaan pt on pt.idpenerimaan = dpt.penerimaan_idpenerimaan
        where pt.pengadaan_idpengadaan = p_idpengadaan
        and dpt.barang_idbarang = p_idbarang;

    return v_pesan - v_terima;
end//

delimiter ;
