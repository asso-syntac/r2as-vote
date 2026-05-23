<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523182950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE proposal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE response_type1_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
              id INT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description VARCHAR(1024) DEFAULT NULL,
              uuid VARCHAR(255) NOT NULL,
              mail VARCHAR(255) NOT NULL,
              state BOOLEAN NOT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387574AD17F50A6 ON events (uuid)');
        $this->addSql(<<<'SQL'
            CREATE TABLE proposal (
              id INT NOT NULL,
              event_id_id INT NOT NULL,
              type INT NOT NULL,
              name VARCHAR(512) NOT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_BFE594723E5F2F7B ON proposal (event_id_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE response_type1 (
              id INT NOT NULL,
              event_id_id INT NOT NULL,
              user_id_id INT NOT NULL,
              proposal_id_id INT NOT NULL,
              positive INT DEFAULT NULL,
              negative INT DEFAULT NULL,
              abstention INT DEFAULT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_D19472DC3E5F2F7B ON response_type1 (event_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DC9D86650F ON response_type1 (user_id_id)');
        $this->addSql('CREATE INDEX IDX_D19472DCB8B357F6 ON response_type1 (proposal_id_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id INT NOT NULL,
              event_id_id INT NOT NULL,
              mail VARCHAR(255) NOT NULL,
              factor INT NOT NULL,
              uuid VARCHAR(255) NOT NULL,
              name VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9D17F50A6 ON users (uuid)');
        $this->addSql('CREATE INDEX IDX_1483A5E93E5F2F7B ON users (event_id_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              proposal
            ADD
              CONSTRAINT FK_BFE594723E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              response_type1
            ADD
              CONSTRAINT FK_D19472DC3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              response_type1
            ADD
              CONSTRAINT FK_D19472DC9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              response_type1
            ADD
              CONSTRAINT FK_D19472DCB8B357F6 FOREIGN KEY (proposal_id_id) REFERENCES proposal (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            ADD
              CONSTRAINT FK_1483A5E93E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE events_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE proposal_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE response_type1_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE proposal DROP CONSTRAINT FK_BFE594723E5F2F7B');
        $this->addSql('ALTER TABLE response_type1 DROP CONSTRAINT FK_D19472DC3E5F2F7B');
        $this->addSql('ALTER TABLE response_type1 DROP CONSTRAINT FK_D19472DC9D86650F');
        $this->addSql('ALTER TABLE response_type1 DROP CONSTRAINT FK_D19472DCB8B357F6');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E93E5F2F7B');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE proposal');
        $this->addSql('DROP TABLE response_type1');
        $this->addSql('DROP TABLE users');
    }
}
