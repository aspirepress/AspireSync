create table sync_plugins
(
    id              uuid                                   not null
        constraint plugins_pkey
            primary key,
    name            varchar(255)                           not null,
    slug            varchar(255)                           not null
        constraint plugins_slug_unique
            unique,
    current_version varchar(255),
    updated         timestamp(0) default CURRENT_TIMESTAMP not null,
    status          varchar(255) default 'open' not null,
    pulled_at       timestamp(0) default CURRENT_TIMESTAMP not null,
    metadata        jsonb
);

-- auto-generated definition
create table sync_plugin_files
(
    id        uuid                                   not null
        constraint plugin_files_pkey
            primary key,
    plugin_id uuid                                   not null
        constraint plugin_files_plugin_id_foreign
            references sync_plugins
            on update cascade on delete cascade,
    file_url  varchar(255),
    type      varchar(255)                           not null,
    version   varchar(255)                           not null,
    metadata  jsonb,
    created   timestamp(0) default CURRENT_TIMESTAMP not null,
    processed timestamp(0),
    hash      varchar(255),
    constraint plugin_files_plugin_id_version_type_unique
        unique (plugin_id, version, type)
);

create index plugin_files_hash_index
    on sync_plugin_files (hash);

create table sync_themes
(
    id              uuid                                   not null
        constraint themes_pkey
            primary key,
    name            varchar(255)                           not null,
    slug            varchar(255)                           not null,
    current_version varchar(255),
    updated         timestamp(0) default CURRENT_TIMESTAMP not null,
    pulled_at       timestamp(0) default CURRENT_TIMESTAMP not null,
    metadata        jsonb
);

-- auto-generated definition
create table sync_theme_files
(
    id        uuid                                   not null
        constraint theme_files_pkey
            primary key,
    theme_id  uuid                                   not null
        constraint theme_files_theme_id_foreign
            references sync_themes
            on update cascade on delete cascade,
    file_url  varchar(255),
    type      varchar(255)                           not null,
    version   varchar(255)                           not null,
    metadata  jsonb,
    created   timestamp(0) default CURRENT_TIMESTAMP not null,
    processed timestamp(0),
    hash      varchar(255),
    constraint theme_files_theme_id_version_type_unique
        unique (theme_id, version, type)
);

create index theme_files_hash_index
    on sync_theme_files (hash);

create table sync_not_found_items
(
    id         uuid                                   not null
        constraint not_found_items_pkey
            primary key,
    item_type  varchar(255)                           not null,
    item_slug  varchar(255)                           not null,
    created_at timestamp(0) default CURRENT_TIMESTAMP not null,
    updated_at timestamp(0) default CURRENT_TIMESTAMP not null
);

create table sync_revisions
(
    action   varchar(255)                           not null,
    revision varchar(255)                           not null,
    added_at timestamp(0) default CURRENT_TIMESTAMP not null
);

create index revisions_action_index
    on sync_revisions (action);

create table sync_sites
(
    id   uuid         not null
        constraint sites_pkey
            primary key,
    host varchar(255) not null
        constraint sites_host_unique
            unique
);

create table sync_stats
(
    id         uuid                                   not null
        constraint stats_pkey
            primary key,
    command    varchar(255)                           not null,
    stats      jsonb                                  not null,
    created_at timestamp(0) default CURRENT_TIMESTAMP not null
);

