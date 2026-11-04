create or replace view v_users as
select
    u.user_id,
    u.username,
    u.email,
    u.password_hash,
    u.role_id,
    u.status_id,
    u.created_at,
    r.role_name,
    s.status_name
from users u
join roles r on r.role_id = u.role_id
join user_statuses s on s.status_id = u.status_id;

create or replace view v_groups as
select 
    g.group_id,
    g.group_name,
    g.created_by,
    g.created_at,
    u.username as creator_name
from `groups` g
join users u on u.user_id = g.created_by;

create or replace view v_conversations as
select
    c.conversation_id,
    c.group_id,
    c.created_at,
    g.group_name,
    g.created_by as group_creator_id
from conversations c
join `groups` g on g.group_id = c.group_id;

create or replace view v_conversation_members as
select
    cm.conversation_id,
    cm.user_id,
    cm.group_role_id,
    cm.joined_at,
    u.username,
    s.status_name,
    gr.role_name
from conversation_members cm
join users u on u.user_id = cm.user_id
join user_statuses s on s.status_id = u.status_id
join group_roles gr on gr.group_role_id = cm.group_role_id;

create or replace view v_messages as
select
    m.message_id,
    m.conversation_id,
    m.sender_id,
    m.type_id,
    m.content,
    m.sent_at,
    u.username as sender_name,
    t.type_name
from messages m
left join users u on u.user_id = m.sender_id
join message_types t on t.type_id = m.type_id;
