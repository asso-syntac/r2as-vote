<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522205020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__proposal AS SELECT id, event_id_id, type, name FROM proposal');
        $this->addSql('DROP TABLE proposal');
        $this->addSql(<<<'SQL'
            CREATE TABLE proposal (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL,
              type INTEGER NOT NULL,
              name VARCHAR(512) NOT NULL,
              CONSTRAINT FK_BFE594723E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO proposal (id, event_id_id, type, name)
            SELECT
              id,
              event_id_id,
              type,
              name
            FROM
              __temp__proposal
        SQL);
        $this->addSql('DROP TABLE __temp__proposal');
        $this->addSql('CREATE INDEX IDX_BFE594723E5F2F7B ON proposal (event_id_id)');
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__response_type1 AS
            SELECT
              id,
              event_id_id,
              user_id_id,
              proposal_id_id,
              positive,
              negative,
              abstention
            FROM
              response_type1
        SQL);
        $this->addSql('DROP TABLE response_type1');
        $this->addSql(<<<'SQL'
            CREATE TABLE response_type1 (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL,
              user_id_id INTEGER NOT NULL,
              proposal_id_id INTEGER NOT NULL,
              positive INTEGER DEFAULT NULL,
              negative INTEGER DEFAULT NULL,
              abstention INTEGER DEFAULT NULL,
              CONSTRAINT FK_D19472DC3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
              CONSTRAINT FK_D19472DC9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
              CONSTRAINT FK_D19472DCB8B357F6 FOREIGN KEY (proposal_id_id) REFERENCES proposal (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO response_type1 (
              id, event_id_id, user_id_id, proposal_id_id,
              positive, negative, abstention
            )
            SELECT
              id,
              event_id_id,
              user_id_id,
              proposal_id_id,
              positive,
              negative,
              abstention
            FROM
              __temp__response_type1
        SQL);
        $this->addSql('DROP TABLE __temp__response_type1');
        $this->addSql('CREATE INDEX IDX_D19472DCB8B357F6 ON response_type1 (proposal_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DC9D86650F ON response_type1 (user_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DC3E5F2F7B ON response_type1 (event_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__proposal AS SELECT id, event_id_id, type, name FROM proposal');
        $this->addSql('DROP TABLE proposal');
        $this->addSql(<<<'SQL'
            CREATE TABLE proposal (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL,
              type INTEGER NOT NULL,
              name VARCHAR(512) NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO proposal (id, event_id_id, type, name)
            SELECT
              id,
              event_id_id,
              type,
              name
            FROM
              __temp__proposal
        SQL);
        $this->addSql('DROP TABLE __temp__proposal');
        $this->addSql('CREATE INDEX IDX_BFE594723E5F2F7B ON proposal (event_id_id)');
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__response_type1 AS
            SELECT
              id,
              event_id_id,
              user_id_id,
              proposal_id_id,
              positive,
              negative,
              abstention
            FROM
              response_type1
        SQL);
        $this->addSql('DROP TABLE response_type1');
        $this->addSql(<<<'SQL'
            CREATE TABLE response_type1 (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL, user_id_id INTEGER NOT NULL,
              proposal_id_id INTEGER NOT NULL, positive INTEGER DEFAULT NULL,
              negative INTEGER DEFAULT NULL, abstention INTEGER DEFAULT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO response_type1 (
              id, event_id_id, user_id_id, proposal_id_id,
              positive, negative, abstention
            )
            SELECT
              id,
              event_id_id,
              user_id_id,
              proposal_id_id,
              positive,
              negative,
              abstention
            FROM
              __temp__response_type1
        SQL);
        $this->addSql('DROP TABLE __temp__response_type1');
        $this->addSql('CREATE INDEX IDX_D19472DC3E5F2F7B ON response_type1 (event_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DC9D86650F ON response_type1 (user_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DCB8B357F6 ON response_type1 (proposal_id_id)');
    }
}
