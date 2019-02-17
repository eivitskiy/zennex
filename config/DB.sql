create table attachments
(
  id         int auto_increment
    primary key,
  file       longblob    null,
  author_id  int         null,
  created_at datetime    not null,
  type       varchar(16) not null,
  link       text        null
);

create table messages
(
  id          int auto_increment
    primary key,
  content     text            null,
  created_at  datetime        not null,
  deleted_at  datetime        null,
  author_id   int             not null,
  likes       int default '0' not null,
  attachments text            null,
  constraint table_name_id_uindex
  unique (id)
);

create table users
(
  id         int auto_increment
    primary key,
  username   varchar(24) not null,
  token      varchar(32) null,
  created_at datetime    not null,
  constraint users_id_uindex
  unique (id),
  constraint users_username_uindex
  unique (username),
  constraint users_token_uindex
  unique (token)
);