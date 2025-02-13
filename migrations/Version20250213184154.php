<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250213184154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'AspireSync 2.0 irreversible migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE sync_assets');
        $this->addSql('DROP TABLE revisions');
        $this->addSql('DROP TABLE cache');
    }

    public function down(Schema $schema): void
    {
        // 2.0 migration is irreversible
    }
}
