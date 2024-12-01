<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241201012435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'initial tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                CREATE TABLE sync (
                      id uuid primary key not null,
                      type varchar(32) not null,
                      slug varchar(255) not null,
                      name text not null,
                      status varchar(32) not null,
                      version varchar(32),
                      origin varchar(32) not null,
                      pulled bigint not null,
                      updated bigint not null,
                      metadata jsonb default null
                  )
                SQL,
        );
        $this->addSql('create index idx_slug on sync (slug)');
        $this->addSql('create index idx_type on sync (type)');
        $this->addSql('create index idx_origin on sync (origin)');
        $this->addSql('create index idx_updated on sync (updated)');
        $this->addSql('create index idx_pulled on sync (pulled)');
        $this->addSql('create unique index uniq_type_slug_origin on sync (type, slug, origin)');

        $this->addSql(
            <<<'SQL'
                create table sync_assets (
                          id uuid primary key not null,
                          sync_id uuid not null references sync (id) on delete cascade,
                          version varchar(32) not null,
                          url text default null,
                          created bigint not null,
                          processed bigint default null,
                          metadata jsonb default null
                        )
                SQL,
        );
        $this->addSql('create index idx_created on sync_assets (created)');
        $this->addSql('create index idx_processed on sync_assets (processed)');
        $this->addSql('create unique index uniq_syncid_version on sync_assets (sync_id, version)');

        $this->addSql(
            <<<'SQL'
                create table revisions (
                    action varchar(255) not null,
                    revision varchar(255) not null,
                    added bigint not null
                )
                SQL,
        );

        $this->addSql(
            <<<'SQL'
                create table cache (
                    key varchar(255) not null,
                    expires varchar(255) not null,
                    value text default null
                )
                SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table sync');
        $this->addSql('drop table sync_assets');
        $this->addSql('drop table revisions');
        $this->addSql('drop table cache');
    }
}
