<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325180631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_adjustment (id INT AUTO_INCREMENT NOT NULL, previous_quantity INT NOT NULL, new_quantity INT NOT NULL, adjustment_type VARCHAR(20) NOT NULL, reason VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, adjusted_at DATETIME NOT NULL, stock_id INT NOT NULL, adjusted_by_id INT NOT NULL, INDEX IDX_27B08FBADCD6110 (stock_id), INDEX IDX_27B08FBA85EDDAD8 (adjusted_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stock_adjustment ADD CONSTRAINT FK_27B08FBADCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE stock_adjustment ADD CONSTRAINT FK_27B08FBA85EDDAD8 FOREIGN KEY (adjusted_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock_adjustment DROP FOREIGN KEY FK_27B08FBADCD6110');
        $this->addSql('ALTER TABLE stock_adjustment DROP FOREIGN KEY FK_27B08FBA85EDDAD8');
        $this->addSql('DROP TABLE stock_adjustment');
    }
}
