create table http_access_rules (
  id integer primary key,
  address varchar(255) not null,
  allow tinyint not null,
  position integer not null,
  owner_type varchar(32) not null,
  owner_id integer not null,
  http tinyint,
  https tinyint
);

create table tcpudp_access_rules (
  id integer primary key,
  address varchar(255) not null,
  allow tinyint not null,
  position integer not null,
  owner_type varchar(32) not null,
  owner_id integer not null,
  tcp tinyint,
  udp tinyint,
  port integer
);
