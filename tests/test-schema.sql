-- Postgres dump of test db schema

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

CREATE TABLE public.sync_not_found_items (
    id uuid NOT NULL,
    item_type character varying(255) NOT NULL,
    item_slug character varying(255) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE public.sync_plugin_files (
    id uuid NOT NULL,
    plugin_id uuid NOT NULL,
    file_url character varying(255),
    type character varying(255) NOT NULL,
    version character varying(255) NOT NULL,
    metadata jsonb,
    created timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    processed timestamp(0) without time zone,
    hash character varying(255)
);

CREATE TABLE public.sync_plugins (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    current_version character varying(255),
    updated timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    pulled_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    metadata jsonb
);

CREATE TABLE public.sync_revisions (
    action character varying(255) NOT NULL,
    revision character varying(255) NOT NULL,
    added_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE public.sync_sites (
    id uuid NOT NULL,
    host character varying(255) NOT NULL
);

CREATE TABLE public.sync_stats (
    id uuid NOT NULL,
    command character varying(255) NOT NULL,
    stats jsonb NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE public.sync_theme_files (
    id uuid NOT NULL,
    theme_id uuid NOT NULL,
    file_url character varying(255),
    type character varying(255) NOT NULL,
    version character varying(255) NOT NULL,
    metadata jsonb,
    created timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    processed timestamp(0) without time zone,
    hash character varying(255)
);

CREATE TABLE public.sync_themes (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    current_version character varying(255),
    updated timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    pulled_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    metadata jsonb
);

ALTER TABLE ONLY public.sync_not_found_items ADD CONSTRAINT not_found_items_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_plugin_files ADD CONSTRAINT plugin_files_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_plugin_files ADD CONSTRAINT plugin_files_plugin_id_version_type_unique UNIQUE (plugin_id, version, type);
ALTER TABLE ONLY public.sync_plugins ADD CONSTRAINT plugins_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_plugins ADD CONSTRAINT plugins_slug_unique UNIQUE (slug);
ALTER TABLE ONLY public.sync_sites ADD CONSTRAINT sites_host_unique UNIQUE (host);
ALTER TABLE ONLY public.sync_sites ADD CONSTRAINT sites_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_stats ADD CONSTRAINT stats_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_theme_files ADD CONSTRAINT theme_files_pkey PRIMARY KEY (id);
ALTER TABLE ONLY public.sync_theme_files ADD CONSTRAINT theme_files_theme_id_version_type_unique UNIQUE (theme_id, version, type);
ALTER TABLE ONLY public.sync_themes ADD CONSTRAINT themes_pkey PRIMARY KEY (id);
CREATE INDEX plugin_files_hash_index ON public.sync_plugin_files USING btree (hash);
CREATE INDEX revisions_action_index ON public.sync_revisions USING btree (action);
CREATE INDEX theme_files_hash_index ON public.sync_theme_files USING btree (hash);
ALTER TABLE ONLY public.sync_plugin_files ADD CONSTRAINT plugin_files_plugin_id_foreign FOREIGN KEY (plugin_id) REFERENCES public.sync_plugins(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY public.sync_theme_files ADD CONSTRAINT theme_files_theme_id_foreign FOREIGN KEY (theme_id) REFERENCES public.sync_themes(id) ON UPDATE CASCADE ON DELETE CASCADE;
