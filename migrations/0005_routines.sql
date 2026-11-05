drop procedure if exists send_message;

delimiter //

create procedure send_message(
    in p_conversation_id int unsigned,
    in p_sender_id       int unsigned,
    in p_content         varchar(4000),
    in p_type_id         int unsigned
)
begin
    if p_sender_id is not null
       and not is_member(p_conversation_id, p_sender_id)
    then
        signal sqlstate '45000'
            set message_text = 'sender is not a member of this conversation';
    end if;

    insert into messages (conversation_id, sender_id, type_id, content)
    values (p_conversation_id, p_sender_id, p_type_id, p_content);

    select last_insert_id() as message_id;
end//

delimiter ;

delimiter //

drop function if exists is_member//
create function is_member(
    p_conversation_id int unsigned,
    p_user_id         int unsigned
) returns boolean
reads sql data
begin
    return exists (
        select 1 from conversation_members
        where conversation_id = p_conversation_id
          and user_id = p_user_id
    );
end//

delimiter ;

delimiter //

drop trigger if exists prevent_cm_owner_delete//
create trigger prevent_cm_owner_delete
before delete on conversation_members
for each row
begin
    if @bypass_owner_trigger is null
       and old.group_role_id = (select group_role_id from group_roles where role_name = 'owner')
    then
        signal sqlstate '45000'
            set message_text = 'cannot remove the group owner';
    end if;
end//

delimiter ;
