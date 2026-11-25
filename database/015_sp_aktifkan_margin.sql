drop procedure if exists sp_aktifkan_margin;

delimiter //

create procedure sp_aktifkan_margin(
    in p_idmargin_penjualan int,
    in p_iduser int
)
begin
    declare v_lock int;
    declare v_exists int default 0;

    start transaction;

    select idmargin_penjualan into v_lock
      from margin_penjualan
     order by idmargin_penjualan
     limit 1
       for update;

    select count(*) into v_exists
      from margin_penjualan
     where idmargin_penjualan = p_idmargin_penjualan;

    if v_exists > 0 then
        update margin_penjualan
           set status = 0
         where status = 1
           and idmargin_penjualan <> p_idmargin_penjualan;

        update margin_penjualan
           set status = 1,
               user_iduser = p_iduser
         where idmargin_penjualan = p_idmargin_penjualan;
    end if;

    commit;
end//

delimiter ;
