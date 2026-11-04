create table conversations (
  conversation_id   int unsigned primary key auto_increment,
  group_id          int unsigned not null unique,
  created_at        datetime not null default current_timestamp,

  foreign key (group_id) references `groups`(group_id)
);

create table conversation_members (
  conversation_id int unsigned not null,
  user_id         int unsigned not null,
  group_role_id   int unsigned not null,
  joined_at       datetime not null default current_timestamp,

  primary key (conversation_id, user_id),
  foreign key (conversation_id) references conversations(conversation_id),
  foreign key (user_id)         references users(user_id),
  foreign key (group_role_id)   references group_roles(group_role_id)
);

create index idx_cm_user on conversation_members (user_id);

create table messages (
  message_id      int unsigned primary key auto_increment,
  conversation_id int unsigned not null,
  sender_id       int unsigned null, -- null = notice
  type_id         int unsigned not null,
  content         varchar(4000) not null,
  sent_at         datetime not null default current_timestamp,

  foreign key (conversation_id) references conversations(conversation_id),
  foreign key (sender_id)       references users(user_id),
  foreign key (type_id)         references message_types(type_id)
);

create index idx_messages_conv on messages (conversation_id, message_id);
