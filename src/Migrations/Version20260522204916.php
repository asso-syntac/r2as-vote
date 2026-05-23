<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522204916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__events AS
            SELECT
              id,
              name,
              description,
              uuid,
              mail,
              state
            FROM
              events
        SQL);
        $this->addSql('DROP TABLE events');
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description VARCHAR(1024) DEFAULT NULL,
              uuid VARCHAR(255) NOT NULL,
              mail VARCHAR(255) NOT NULL,
              state BOOLEAN NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO events (
              id, name, description, uuid, mail, state
            )
            SELECT
              id,
              name,
              description,
              uuid,
              mail,
              state
            FROM
              __temp__events
        SQL);
        $this->addSql('DROP TABLE __temp__events');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387574AD17F50A6 ON events (uuid)');
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__users AS
            SELECT
              id,
              event_id_id,
              mail,
              factor,
              uuid,
              name
            FROM
              users
        SQL);
        $this->addSql('DROP TABLE users');
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL,
              mail VARCHAR(255) NOT NULL,
              factor INTEGER NOT NULL,
              uuid VARCHAR(255) NOT NULL,
              name VARCHAR(255) NOT NULL,
              CONSTRAINT FK_1483A5E93E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) ON
              UPDATE
                NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO users (
              id, event_id_id, mail, factor, uuid,
              name
            )
            SELECT
              id,
              event_id_id,
              mail,
              factor,
              uuid,
              name
            FROM
              __temp__users
        SQL);
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE INDEX IDX_1483A5E93E5F2F7B ON users (event_id_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9D17F50A6 ON users (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__events AS
            SELECT
              id,
              name,
              description,
              uuid,
              mail,
              state
            FROM
              events
        SQL);
        $this->addSql('DROP TABLE events');
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description VARCHAR(1024) DEFAULT NULL,
              uuid VARCHAR(255) NOT NULL,
              mail VARCHAR(255) NOT NULL,
              state BOOLEAN NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO events (
              id, name, description, uuid, mail, state
            )
            SELECT
              id,
              name,
              description,
              uuid,
              mail,
              state
            FROM
              __temp__events
        SQL);
        $this->addSql('DROP TABLE __temp__events');
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__users AS
            SELECT
              id,
              event_id_id,
              mail,
              factor,
              uuid,
              name
            FROM
              users
        SQL);
        $this->addSql('DROP TABLE users');
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              event_id_id INTEGER NOT NULL,
              mail VARCHAR(255) NOT NULL,
              factor INTEGER NOT NULL,
              uuid VARCHAR(255) NOT NULL,
              name VARCHAR(255) NOT NULL,
              CONSTRAINT FK_1483A5E93E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO users (
              id, event_id_id, mail, factor, uuid,
              name
            )
            SELECT
              id,
              event_id_id,
              mail,
              factor,
              uuid,
              name
            FROM
              __temp__users
        SQL);
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE INDEX IDX_1483A5E93E5F2F7B ON users (event_id_id)');
    }
}
