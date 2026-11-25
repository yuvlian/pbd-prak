drop procedure if exists sp_simpan_user;

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
        insert into user (username, password, role_idrole)
        values (p_username, sha2(p_password, 256), p_idrole);
    elseif p_password = '' then
        update user
           set username = p_username, role_idrole = p_idrole
         where iduser = p_iduser;
    else
        update user
           set username = p_username,
               password = sha2(p_password, 256),
               role_idrole = p_idrole
         where iduser = p_iduser;
    end if;
end//

delimiter ;
