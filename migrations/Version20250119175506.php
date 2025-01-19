<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250119175506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add checked column to sync table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table sync add checked int not null default 0');
        $this->addSql('alter table sync alter column checked drop default');
        $this->addSql('create index idx_checked on sync (checked)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sync DROP checked');
    }
}
