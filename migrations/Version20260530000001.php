<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create device_token table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE device_token (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_device_token_user (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE device_token
            ADD CONSTRAINT FK_device_token_user
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE device_token DROP FOREIGN KEY FK_device_token_user');
        $this->addSql('DROP TABLE device_token');
    }
}
