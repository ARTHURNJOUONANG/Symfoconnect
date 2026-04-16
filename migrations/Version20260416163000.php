<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles, follows, likes and notifications for day 2.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` ADD roles JSON NOT NULL");
        $this->addSql("UPDATE `user` SET roles = '[\"ROLE_USER\"]'");

        $this->addSql('CREATE TABLE user_follows (follower_id INT NOT NULL, followed_id INT NOT NULL, INDEX IDX_8D93D649AC24F853 (follower_id), INDEX IDX_8D93D649D956F010 (followed_id), PRIMARY KEY(follower_id, followed_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_likes (post_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_35CE90ED4B89032C (post_id), INDEX IDX_35CE90EDA76ED395 (user_id), PRIMARY KEY(post_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, type VARCHAR(50) NOT NULL, content VARCHAR(255) NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAA76ED395 (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_follows ADD CONSTRAINT FK_8D93D649AC24F853 FOREIGN KEY (follower_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_follows ADD CONSTRAINT FK_8D93D649D956F010 FOREIGN KEY (followed_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_35CE90ED4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_35CE90EDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (recipient_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_follows DROP FOREIGN KEY FK_8D93D649AC24F853');
        $this->addSql('ALTER TABLE user_follows DROP FOREIGN KEY FK_8D93D649D956F010');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_35CE90ED4B89032C');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_35CE90EDA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE user_follows');
        $this->addSql('DROP TABLE post_likes');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE `user` DROP roles');
    }
}
