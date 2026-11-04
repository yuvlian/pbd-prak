insert into user_statuses (status_id, status_name)
values (1,'offline'), (2,'online')
on duplicate key update status_name = values(status_name);

insert into roles (role_id, role_name)
values (1,'member'), (2,'admin')
on duplicate key update role_name = values(role_name);

insert into message_types (type_id, type_name)
values (1,'text'), (2,'system')
on duplicate key update type_name = values(type_name);

insert into group_roles (group_role_id, role_name)
values (1,'owner'), (2,'member')
on duplicate key update role_name = values(group_roles.role_name);
