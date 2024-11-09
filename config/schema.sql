create table sync_plugins
(
    id              character(36) primary key              not null,
    slug            varchar(255) unique                    not null,
    name            varchar(255)                           not null,
    status          varchar(255) default 'open'            not null,
    current_version varchar(255),
    updated         timestamp(0) default current_timestamp not null,
    pulled_at       timestamp(0) default current_timestamp not null,
    metadata        text
);

create table sync_plugin_files
(
    id        character(36) primary key              not null,
    plugin_id character(36)                          not null
        references sync_plugins (id) on delete cascade,
    file_url  varchar(255),
    type      varchar(255)                           not null,
    version   varchar(255)                           not null,
    metadata  text,
    created   timestamp(0) default current_timestamp not null,
    processed timestamp(0),
    hash      varchar(255),
    unique (plugin_id, version, type) on conflict replace
);
create index plugin_files_hash_index on sync_plugin_files (hash);

create table sync_themes
(
    id              character(36) primary key              not null,
    slug            varchar(255)                           not null,
    name            varchar(255)                           not null,
    status          varchar(255) default 'open'            not null,
    current_version varchar(255),
    updated         timestamp(0) default current_timestamp not null,
    pulled_at       timestamp(0) default current_timestamp not null,
    metadata        text
);

create table sync_theme_files
(
    id        character(36) primary key              not null,
    theme_id  character(36)                          not null
        references sync_themes (id) on delete cascade,
    file_url  varchar(255),
    type      varchar(255)                           not null,
    version   varchar(255)                           not null,
    metadata  text,
    created   timestamp(0) default current_timestamp not null,
    processed timestamp(0),
    hash      varchar(255),
    unique (theme_id, version, type) on conflict replace
);
create index theme_files_hash_index on sync_theme_files (hash);

create table sync_not_found_items
(
    id         character(36) primary key              not null,
    item_type  varchar(255)                           not null,
    item_slug  varchar(255)                           not null,
    created_at timestamp(0) default current_timestamp not null,
    updated_at timestamp(0) default current_timestamp not null
);

create table sync_revisions
(
    action   varchar(255)                           not null,
    revision varchar(255)                           not null,
    added_at timestamp(0) default current_timestamp not null
);
create index revisions_action_index on sync_revisions (action);

create table sync_sites
(
    id   character(36) primary key not null,
    host varchar(255) unique       not null
);

create table sync_stats
(
    id         character(36) primary key              not null,
    command    varchar(255)                           not null,
    stats      text                                   not null,
    created_at timestamp(0) default current_timestamp not null
);
