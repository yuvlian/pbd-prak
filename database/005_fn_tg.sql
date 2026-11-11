-- function

drop function if exists fn_stok_barang;

delimiter //

create function fn_stok_barang(p_idbarang int)
returns int
reads sql data
begin
    declare total int;

    select coalesce(sum(masuk - keluar), 0) into total
      from kartu_stok
     where idbarang = p_idbarang;

    return total;
end//

delimiter ;

-- trigger

drop trigger if exists trg_margin_updated_at;

create trigger trg_margin_updated_at
before update on margin_penjualan
for each row
set new.updated_at = now();
