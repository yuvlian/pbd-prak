-- function

drop function if exists fn_sisa_pesanan;

delimiter //

create function fn_sisa_pesanan(p_idpengadaan bigint, p_idbarang int)
returns int
reads sql data
begin
    declare v_sisa int;

    select coalesce(sum(dp.jumlah), 0) - coalesce((
               select sum(dpt.jumlah_terima)
                 from detail_penerimaan dpt
                 join penerimaan pt on pt.idpenerimaan = dpt.penerimaan_idpenerimaan
                where pt.pengadaan_idpengadaan = p_idpengadaan
                  and dpt.barang_idbarang = p_idbarang
           ), 0) into v_sisa
      from detail_pengadaan dp
     where dp.pengadaan_idpengadaan = p_idpengadaan
       and dp.barang_idbarang = p_idbarang;

    return v_sisa;
end//

delimiter ;

-- trigger

drop trigger if exists trg_detail_penerimaan_stok;

create trigger trg_detail_penerimaan_stok
after insert on detail_penerimaan
for each row
insert into kartu_stok
    (jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, barang_idbarang)
values
    ('P', new.jumlah_terima, 0, fn_stok_barang(new.barang_idbarang) + new.jumlah_terima,
     now(), new.penerimaan_idpenerimaan, new.barang_idbarang);

-- procedure

drop procedure if exists sp_terima_pengadaan;

delimiter //

create procedure sp_terima_pengadaan(
    in p_idpengadaan bigint,
    in p_iduser int
)
begin
    declare v_baris int;
    declare v_idpenerimaan bigint;

    select count(*) into v_baris
      from detail_pengadaan dp
     where dp.pengadaan_idpengadaan = p_idpengadaan
       and fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang) > 0;

    if v_baris > 0 then
        insert into penerimaan (created_at, status, pengadaan_idpengadaan, user_iduser)
        values (now(), 'S', p_idpengadaan, p_iduser);

        set v_idpenerimaan = last_insert_id();

        insert into detail_penerimaan
            (penerimaan_idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
        select v_idpenerimaan, dp.barang_idbarang,
               fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang),
               dp.harga_satuan,
               dp.harga_satuan * fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang)
          from detail_pengadaan dp
         where dp.pengadaan_idpengadaan = p_idpengadaan
           and fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang) > 0;

        update pengadaan
           set status = 'S'
         where idpengadaan = p_idpengadaan;
    end if;
end//

delimiter ;
