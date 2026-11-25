create or replace view v_sisa_pengadaan as
    select p.idpengadaan, p.created_at, v.nama_vendor,
           dp.barang_idbarang, b.nama, dp.jumlah,
           fn_sisa_pesanan(p.idpengadaan, dp.barang_idbarang) as sisa
      from pengadaan p
      join vendor v on v.idvendor = p.vendor_idvendor
      join detail_pengadaan dp on dp.pengadaan_idpengadaan = p.idpengadaan
      join barang b on b.idbarang = dp.barang_idbarang;

drop procedure if exists sp_terima_pengadaan;

delimiter //

create procedure sp_terima_pengadaan(
    in p_idpenerimaan bigint,
    in p_idpengadaan bigint,
    in p_barang_idbarang int,
    in p_jumlah int
)
begin
    declare v_harga int;

    if p_jumlah > 0
       and p_jumlah <= fn_sisa_pesanan(p_idpengadaan, p_barang_idbarang) then
        select min(harga_satuan) into v_harga
          from detail_pengadaan
         where pengadaan_idpengadaan = p_idpengadaan
           and barang_idbarang = p_barang_idbarang;

        if v_harga is not null then
            insert into detail_penerimaan
                (penerimaan_idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
            values (p_idpenerimaan, p_barang_idbarang, p_jumlah, v_harga, v_harga * p_jumlah);

            update pengadaan p
               set p.status = if(exists (
                       select 1
                         from detail_pengadaan dp
                        where dp.pengadaan_idpengadaan = p_idpengadaan
                          and fn_sisa_pesanan(p_idpengadaan, dp.barang_idbarang) > 0
                   ), 'P', 'S')
             where p.idpengadaan = p_idpengadaan;
        end if;
    end if;
end//

delimiter ;
