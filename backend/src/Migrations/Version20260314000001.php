<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create scientific papers tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE my_scientific_paper (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(500) NOT NULL,
                abstract LONGTEXT DEFAULT NULL,
                status VARCHAR(50) NOT NULL,
                deleted TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql('
            CREATE TABLE my_scientific_paper_checklist_item (
                id INT AUTO_INCREMENT NOT NULL,
                paper_id INT NOT NULL,
                title VARCHAR(500) NOT NULL,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                INDEX IDX_CHECKLIST_PAPER (paper_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_CHECKLIST_PAPER FOREIGN KEY (paper_id) REFERENCES my_scientific_paper (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql('
            CREATE TABLE my_scientific_paper_version (
                id INT AUTO_INCREMENT NOT NULL,
                paper_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_VERSION_PAPER (paper_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_VERSION_PAPER FOREIGN KEY (paper_id) REFERENCES my_scientific_paper (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS my_scientific_paper_checklist_item');
        $this->addSql('DROP TABLE IF EXISTS my_scientific_paper_version');
        $this->addSql('DROP TABLE IF EXISTS my_scientific_paper');
    }
}
