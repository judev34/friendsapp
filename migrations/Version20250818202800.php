<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818202800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial tables for EventApp: user, event, registration - MariaDB optimized';
    }

    public function up(Schema $schema): void
    {
        // Create user table
        $this->addSql('CREATE TABLE user (
            id INT AUTO_INCREMENT NOT NULL, 
            email VARCHAR(180) NOT NULL, 
            roles JSON NOT NULL, 
            password VARCHAR(255) NOT NULL, 
            first_name VARCHAR(100) NOT NULL, 
            last_name VARCHAR(100) NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME DEFAULT NULL, 
            is_verified TINYINT(1) NOT NULL DEFAULT 0, 
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Create event table
        $this->addSql('CREATE TABLE event (
            id INT AUTO_INCREMENT NOT NULL, 
            organizer_id INT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            description LONGTEXT NOT NULL, 
            start_date DATETIME NOT NULL, 
            end_date DATETIME NOT NULL, 
            location VARCHAR(255) NOT NULL, 
            max_participants INT DEFAULT NULL, 
            price DECIMAL(8, 2) DEFAULT NULL, 
            image_url VARCHAR(255) DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME DEFAULT NULL, 
            is_published TINYINT(1) NOT NULL DEFAULT 0, 
            tags JSON DEFAULT NULL, 
            INDEX IDX_3BAE0AA7876C4DDA (organizer_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Create registration table
        $this->addSql('CREATE TABLE registration (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            event_id INT NOT NULL, 
            status VARCHAR(255) NOT NULL, 
            registered_at DATETIME NOT NULL, 
            confirmed_at DATETIME DEFAULT NULL, 
            cancelled_at DATETIME DEFAULT NULL, 
            ticket_code VARCHAR(255) DEFAULT NULL, 
            notes LONGTEXT DEFAULT NULL, 
            paid_amount DECIMAL(8, 2) DEFAULT NULL, 
            paid_at DATETIME DEFAULT NULL, 
            INDEX IDX_62A8A7A7A76ED395 (user_id), 
            INDEX IDX_62A8A7A771F7E88B (event_id), 
            UNIQUE INDEX unique_user_event (user_id, event_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A771F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints first
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7876C4DDA');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A7A76ED395');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A771F7E88B');
        
        // Drop tables
        $this->addSql('DROP TABLE registration');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE user');
    }
}
