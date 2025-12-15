<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212215243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clients (id INT IDENTITY NOT NULL, first_name NVARCHAR(100) NOT NULL, last_name NVARCHAR(100) NOT NULL, email_encrypted NVARCHAR(255) NOT NULL, phone_encrypted NVARCHAR(255), national_id_encrypted NVARCHAR(255), address_encrypted NVARCHAR(255), document_encrypted NVARCHAR(255) NOT NULL, type_document_encrypted NVARCHAR(255) NOT NULL, status NVARCHAR(20) NOT NULL, created_at DATETIME2(6) NOT NULL, updated_at DATETIME2(6), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE orders (id INT IDENTITY NOT NULL, status NVARCHAR(20) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, order_date DATETIME2(6) NOT NULL, completed_at DATETIME2(6), canceled_at DATETIME2(6), description NVARCHAR(255), client_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E52FFDEE19EB6921 ON orders (client_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA db_accessadmin');
        $this->addSql('CREATE SCHEMA db_backupoperator');
        $this->addSql('CREATE SCHEMA db_datareader');
        $this->addSql('CREATE SCHEMA db_datawriter');
        $this->addSql('CREATE SCHEMA db_ddladmin');
        $this->addSql('CREATE SCHEMA db_denydatareader');
        $this->addSql('CREATE SCHEMA db_denydatawriter');
        $this->addSql('CREATE SCHEMA db_owner');
        $this->addSql('CREATE SCHEMA db_securityadmin');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE19EB6921');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE orders');
    }
}
