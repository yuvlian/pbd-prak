create table user_statuses (
  status_id   int unsigned primary key auto_increment,
  status_name varchar(32) not null unique
);

create table roles (
  role_id   int unsigned primary key auto_increment,
  role_name varchar(32) not null unique
);

create table message_types (
  type_id   int unsigned primary key auto_increment,
  type_name varchar(32) not null unique
);

create table group_roles (
  group_role_id int unsigned primary key auto_increment,
  role_name     varchar(32) not null unique
);

create table users (
  user_id       int unsigned primary key auto_increment,
  username      varchar(32) not null unique,
  email         varchar(255) not null unique,
  password_hash varchar(100) not null,
  role_id       int unsigned not null,
  status_id     int unsigned not null,
  created_at    datetime not null default current_timestamp,

  foreign key (role_id)   references roles(role_id),
  foreign key (status_id) references user_statuses(status_id)
);

create table `groups` (
  group_id   int unsigned primary key auto_increment,
  group_name varchar(64) not null,
  created_by int unsigned not null,
  created_at datetime not null default current_timestamp,

  foreign key (created_by) references users(user_id)
);
