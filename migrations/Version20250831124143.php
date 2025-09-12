<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831124143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE association (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE attempt (id SERIAL NOT NULL, competitor_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_18EC026678A5D405 ON attempt (competitor_id)');
        $this->addSql('CREATE INDEX IDX_18EC026612469DE2 ON attempt (category_id)');
        $this->addSql('CREATE TABLE attempt_score (id SERIAL NOT NULL, attempt_id INT NOT NULL, score NUMERIC(4, 1) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_925068A2B191BE6B ON attempt_score (attempt_id)');
        $this->addSql('CREATE TABLE category (id SERIAL NOT NULL, tournament_id INT NOT NULL, category_group_id INT NOT NULL, name VARCHAR(255) NOT NULL, initial_fee NUMERIC(5, 2) NOT NULL, additional_fee NUMERIC(5, 2) NOT NULL, attempt_limit INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64C19C133D1A3E7 ON category (tournament_id)');
        $this->addSql('CREATE INDEX IDX_64C19C1294CCED ON category (category_group_id)');
        $this->addSql('CREATE TABLE category_group (id SERIAL NOT NULL, description VARCHAR(255) NOT NULL, scores_per_attempt INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE competitor (id SERIAL NOT NULL, association_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E0D53BAAEFB9C8A5 ON competitor (association_id)');
        $this->addSql('CREATE TABLE tournament (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, image_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE attempt ADD CONSTRAINT FK_18EC026678A5D405 FOREIGN KEY (competitor_id) REFERENCES competitor (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attempt ADD CONSTRAINT FK_18EC026612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attempt_score ADD CONSTRAINT FK_925068A2B191BE6B FOREIGN KEY (attempt_id) REFERENCES attempt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1294CCED FOREIGN KEY (category_group_id) REFERENCES category_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competitor ADD CONSTRAINT FK_E0D53BAAEFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE attempt DROP CONSTRAINT FK_18EC026678A5D405');
        $this->addSql('ALTER TABLE attempt DROP CONSTRAINT FK_18EC026612469DE2');
        $this->addSql('ALTER TABLE attempt_score DROP CONSTRAINT FK_925068A2B191BE6B');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C133D1A3E7');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1294CCED');
        $this->addSql('ALTER TABLE competitor DROP CONSTRAINT FK_E0D53BAAEFB9C8A5');
        $this->addSql('DROP TABLE association');
        $this->addSql('DROP TABLE attempt');
        $this->addSql('DROP TABLE attempt_score');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_group');
        $this->addSql('DROP TABLE competitor');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
