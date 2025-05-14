<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514131351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE categorie_event (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, desc_categorie_event VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE client_evenement (client_id INT(11) NOT NULL, evenement_id INT(11) NOT NULL, INDEX IDX_E006B81119EB6921 (client_id), INDEX IDX_E006B811FD02F13 (evenement_id), PRIMARY KEY(client_id, evenement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), INDEX IDX_6EEAA67DF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire (commentaire_id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, parent_commentaire_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, votes INT NOT NULL, creation_at DATETIME NOT NULL, INDEX IDX_67F068BC1E27F6BF (question_id), INDEX IDX_67F068BCA441A59B (parent_commentaire_id), INDEX IDX_67F068BCFB88E14F (utilisateur_id), PRIMARY KEY(commentaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire_reactions (commentaire_id INT NOT NULL, user_id INT NOT NULL, emoji VARCHAR(255) NOT NULL, INDEX IDX_B7E4F084BA9CD190 (commentaire_id), INDEX IDX_B7E4F084A76ED395 (user_id), UNIQUE INDEX commentaire_user_unique (commentaire_id, user_id), PRIMARY KEY(commentaire_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire_votes (commentaire_id INT NOT NULL, user_id INT NOT NULL, vote_type VARCHAR(255) NOT NULL, INDEX IDX_D407B2D3BA9CD190 (commentaire_id), INDEX IDX_D407B2D3A76ED395 (user_id), PRIMARY KEY(commentaire_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, game VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, file VARCHAR(255) NOT NULL, date DATETIME NOT NULL, userId INT DEFAULT NULL, INDEX IDX_2694D7A564B64DCC (userId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, nom_event VARCHAR(255) NOT NULL, max_places_event INT NOT NULL, date_event DATETIME NOT NULL, lieu_event VARCHAR(255) NOT NULL, photo_event VARCHAR(255) NOT NULL, INDEX IDX_B26681EBCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE games (game_id INT AUTO_INCREMENT NOT NULL, game_name VARCHAR(255) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, game_type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_FF232B31B536122A (game_name), PRIMARY KEY(game_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lexik_trans_unit (id INT AUTO_INCREMENT NOT NULL, key_name VARCHAR(191) NOT NULL, domain VARCHAR(191) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX key_domain_idx (key_name, domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lexik_trans_unit_translations (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, trans_unit_id INT DEFAULT NULL, locale VARCHAR(191) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, modified_manually TINYINT(1) NOT NULL, INDEX IDX_B0AA394493CB796C (file_id), INDEX IDX_B0AA3944C3C583C9 (trans_unit_id), UNIQUE INDEX trans_unit_locale_idx (trans_unit_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lexik_translation_file (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(191) NOT NULL, locale VARCHAR(191) NOT NULL, extention VARCHAR(191) NOT NULL, path VARCHAR(191) NOT NULL, hash VARCHAR(191) NOT NULL, UNIQUE INDEX hash_idx (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at VARCHAR(255) NOT NULL, available_at VARCHAR(255) NOT NULL, delivered_at VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, is_read TINYINT(1) NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom_produit VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, score INT NOT NULL, platform VARCHAR(50) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, region VARCHAR(50) DEFAULT NULL, activation_region VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE question_reactions (reaction_id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, user_id INT DEFAULT NULL, emoji VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_31A63B281E27F6BF (question_id), INDEX IDX_31A63B28A76ED395 (user_id), UNIQUE INDEX question_user_emoji_unique (question_id, user_id, emoji), PRIMARY KEY(reaction_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE question_votes (question_id INT NOT NULL, user_id INT NOT NULL, vote_type VARCHAR(255) NOT NULL, INDEX IDX_61606BCB1E27F6BF (question_id), INDEX IDX_61606BCBA76ED395 (user_id), PRIMARY KEY(question_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE questions (question_id INT AUTO_INCREMENT NOT NULL, game_id INT DEFAULT NULL, utilisateur_id INT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, votes INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, media_path VARCHAR(255) DEFAULT NULL, media_type VARCHAR(255) NOT NULL, INDEX IDX_8ADC54D5E48FD905 (game_id), INDEX IDX_8ADC54D5FB88E14F (utilisateur_id), PRIMARY KEY(question_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reports (reportId INT AUTO_INCREMENT NOT NULL, reason ENUM('MINEUR_IMPLIQUE','HARCELEMENT','SUICIDE_AUTOMUTILATION','CONTENU_VIOLENT','VENTE_ARTICLES_RESTREINTS','CONTENU_ADULTE','ARNAQUE_FAUSSE_INFORMATION','CONTENU_NON_DESIRE'), evidence VARCHAR(255) DEFAULT NULL, status ENUM('PENDING','REVIEWED','RESOLVED'), created_at DATETIME NOT NULL, reporterId INT DEFAULT NULL, reportedUserId INT DEFAULT NULL, INDEX IDX_F11FA745AC3D5463 (reporterId), INDEX IDX_F11FA74544D96C64 (reportedUserId), PRIMARY KEY(reportId)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, session_id_id INT NOT NULL, client_id INT NOT NULL, date_reservation DATETIME NOT NULL, INDEX IDX_42C84955A4392681 (session_id_id), INDEX IDX_42C8495519EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_794381C6FB88E14F (utilisateur_id), INDEX IDX_794381C6F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE session_game (id INT AUTO_INCREMENT NOT NULL, coach_id INT NOT NULL, prix DOUBLE PRECISION NOT NULL, date_creation DATETIME NOT NULL, duree_session VARCHAR(50) NOT NULL, game VARCHAR(255) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, INDEX IDX_E55A31A63C105691 (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, games_id INT DEFAULT NULL, quantity INT NOT NULL, prix_produit INT NOT NULL, image VARCHAR(255) NOT NULL, INDEX IDX_4B365660F347EFB (produit_id), INDEX IDX_4B36566097FFC673 (games_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, nickname VARCHAR(255) NOT NULL, numero INT NOT NULL, mot_passe VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, googleId VARCHAR(255) DEFAULT NULL, privilege VARCHAR(50) DEFAULT 'regular' NOT NULL, ban TINYINT(1) DEFAULT 0 NOT NULL, banTime DATETIME DEFAULT NULL, countRep INT DEFAULT 0 NOT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), UNIQUE INDEX UNIQ_1D1C63B3A188FE64 (nickname), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE client_evenement ADD CONSTRAINT FK_E006B81119EB6921 FOREIGN KEY (client_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE client_evenement ADD CONSTRAINT FK_E006B811FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC1E27F6BF FOREIGN KEY (question_id) REFERENCES questions (question_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA441A59B FOREIGN KEY (parent_commentaire_id) REFERENCES commentaire (commentaire_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_reactions ADD CONSTRAINT FK_B7E4F084BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (commentaire_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_reactions ADD CONSTRAINT FK_B7E4F084A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_votes ADD CONSTRAINT FK_D407B2D3BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (commentaire_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_votes ADD CONSTRAINT FK_D407B2D3A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande ADD CONSTRAINT FK_2694D7A564B64DCC FOREIGN KEY (userId) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement ADD CONSTRAINT FK_B26681EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA394493CB796C FOREIGN KEY (file_id) REFERENCES lexik_translation_file (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA3944C3C583C9 FOREIGN KEY (trans_unit_id) REFERENCES lexik_trans_unit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_reactions ADD CONSTRAINT FK_31A63B281E27F6BF FOREIGN KEY (question_id) REFERENCES questions (question_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_reactions ADD CONSTRAINT FK_31A63B28A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_votes ADD CONSTRAINT FK_61606BCB1E27F6BF FOREIGN KEY (question_id) REFERENCES questions (question_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_votes ADD CONSTRAINT FK_61606BCBA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE questions ADD CONSTRAINT FK_8ADC54D5E48FD905 FOREIGN KEY (game_id) REFERENCES games (game_id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE questions ADD CONSTRAINT FK_8ADC54D5FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reports ADD CONSTRAINT FK_F11FA745AC3D5463 FOREIGN KEY (reporterId) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reports ADD CONSTRAINT FK_F11FA74544D96C64 FOREIGN KEY (reportedUserId) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A4392681 FOREIGN KEY (session_id_id) REFERENCES session_game (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519EB6921 FOREIGN KEY (client_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C6F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session_game ADD CONSTRAINT FK_E55A31A63C105691 FOREIGN KEY (coach_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD CONSTRAINT FK_4B365660F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD CONSTRAINT FK_4B36566097FFC673 FOREIGN KEY (games_id) REFERENCES games (game_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE client_evenement DROP FOREIGN KEY FK_E006B81119EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE client_evenement DROP FOREIGN KEY FK_E006B811FD02F13
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA441A59B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_reactions DROP FOREIGN KEY FK_B7E4F084BA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_reactions DROP FOREIGN KEY FK_B7E4F084A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_votes DROP FOREIGN KEY FK_D407B2D3BA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_votes DROP FOREIGN KEY FK_D407B2D3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A564B64DCC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EBCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA394493CB796C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA3944C3C583C9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_reactions DROP FOREIGN KEY FK_31A63B281E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_reactions DROP FOREIGN KEY FK_31A63B28A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_votes DROP FOREIGN KEY FK_61606BCB1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_votes DROP FOREIGN KEY FK_61606BCBA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE questions DROP FOREIGN KEY FK_8ADC54D5E48FD905
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE questions DROP FOREIGN KEY FK_8ADC54D5FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reports DROP FOREIGN KEY FK_F11FA745AC3D5463
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reports DROP FOREIGN KEY FK_F11FA74544D96C64
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A4392681
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C6FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C6F347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session_game DROP FOREIGN KEY FK_E55A31A63C105691
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP FOREIGN KEY FK_4B365660F347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP FOREIGN KEY FK_4B36566097FFC673
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE categorie_event
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE client_evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commande
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire_reactions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire_votes
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE demande
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE games
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lexik_trans_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lexik_trans_unit_translations
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lexik_translation_file
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE produit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question_reactions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question_votes
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE questions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reports
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE review
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE session_game
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE stock
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE utilisateur
        SQL);
    }
}
