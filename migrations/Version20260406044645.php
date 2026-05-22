<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406044645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_type ADD CONSTRAINT FK_136758812469DE2 FOREIGN KEY (category_id) REFERENCES clothes_category (id)');
        $this->addSql('CREATE INDEX IDX_136758812469DE2 ON product_type (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type DROP FOREIGN KEY FK_136758812469DE2');
        $this->addSql('DROP INDEX IDX_136758812469DE2 ON product_type');
        $this->addSql('ALTER TABLE product_type DROP category_id');
    }
}
