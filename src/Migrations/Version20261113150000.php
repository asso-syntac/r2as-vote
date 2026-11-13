<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align schema with mapping after DBAL 4 upgrade';
    }

    public function up(Schema $schema): void
    {
        // createdAt is always set by Events::__construct(); the default only served the backfill.
        $this->addSql('ALTER TABLE events ALTER created_at DROP DEFAULT');
        // DBAL 4 no longer uses DC2Type comments.
        $this->addSql('COMMENT ON COLUMN events.created_at IS NULL');
        // Name expected by Symfony's PdoSessionHandler schema listener.
        $this->addSql('ALTER INDEX sessions_lifetime_idx RENAME TO sess_lifetime_idx');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX sess_lifetime_idx RENAME TO sessions_lifetime_idx');
        $this->addSql("COMMENT ON COLUMN events.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE events ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
    }
}
