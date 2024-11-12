create table sync
(
    id       character(36) primary key              not null,
    type     varchar(255)                           not null,
    slug     varchar(255)                           not null,
    name     varchar(255)                           not null,
    status   varchar(255) default 'open'            not null,
    version  varchar(255),
    origin   varchar(255)                           not null,
    updated  timestamp(0) default current_timestamp not null,
    pulled   timestamp(0) default current_timestamp not null,
    metadata text,
    unique (slug, type, origin)
);
create index idx_sync_slug on sync (slug);
create index idx_sync_type on sync (type);
create index idx_sync_origin on sync (origin);
create index idx_sync_updated on sync (updated);
create index idx_sync_pulled on sync (pulled);

create table sync_assets
(
    id        character(36) primary key              not null,
    sync_id   character(36)                          not null references sync (id) on delete cascade,
    version   varchar(255)                           not null,
    url       varchar(255),
    created   timestamp(0) default current_timestamp not null,
    processed timestamp(0),
    metadata  text,
    unique (sync_id, version)
);

create table revisions
(
    action   varchar(255)                           not null,
    revision varchar(255)                           not null,
    added_at timestamp(0) default current_timestamp not null
);
create index idx_revisions_action on revisions (action);

create table cache
(
    key     varchar(4096) primary key not null,
    expires timestamp(0)              not null,
    value   text
)
