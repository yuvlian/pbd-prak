drop trigger if exists trg_margin_updated_at;
drop trigger if exists trg_detail_penerimaan_stok;

delimiter //

create trigger trg_margin_updated_at
before update on margin_penjualan
for each row
    set new.updated_at = now()//

create trigger trg_detail_penerimaan_stok
after insert on detail_penerimaan
for each row
    insert into kartu_stok
        (
            jenis_transaksi,
            masuk,
            keluar,
            stock,
            created_at,
            idtransaksi,
            barang_idbarang
        )
    values
        (
            'P',
            new.jumlah_terima,
            0,
            fn_stok_barang(new.barang_idbarang) + new.jumlah_terima,
            now(),
            new.penerimaan_idpenerimaan,
            new.barang_idbarang
        )//

delimiter ;
