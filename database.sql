-- ============================================================
--  Base de données : logements_db
-- ============================================================

-- 1. Suppression des tables (ordre inversé des dépendances)
DROP TABLE IF EXISTS `favori`;
DROP TABLE IF EXISTS `correspondance`;
DROP TABLE IF EXISTS `notification`;
DROP TABLE IF EXISTS `photo`;
DROP TABLE IF EXISTS `reservation`;
DROP TABLE IF EXISTS `demande`;
DROP TABLE IF EXISTS `logement`;
DROP TABLE IF EXISTS `hote`;
DROP TABLE IF EXISTS `utilisateur`;

-- 2. Table utilisateur
CREATE TABLE `utilisateur` (
  `id_client`        int          NOT NULL AUTO_INCREMENT,
  `nom`              varchar(100) NOT NULL,
  `prenom`           varchar(100) NOT NULL,
  `email`            varchar(255) NOT NULL,
  `telephone`        varchar(20)  DEFAULT NULL,
  `adresse`          text,
  `ville`            varchar(100) DEFAULT NULL,
  `pays`             varchar(100) DEFAULT 'Tunisie',
  `type_client`      enum('client','proprietaire','admin') DEFAULT 'client',
  `mot_de_passe`     varchar(255) NOT NULL,
  `date_inscription` datetime     DEFAULT CURRENT_TIMESTAMP,
  `statut`           enum('actif','inactif','suspendu') DEFAULT 'actif',
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table hote (dépend de utilisateur)
CREATE TABLE `hote` (
  `id_hote`     int         NOT NULL AUTO_INCREMENT,
  `id_client`   int         NOT NULL,
  `statut_hote` enum('actif','suspendu','en_attente') DEFAULT 'actif',
  `date_debut`  datetime    DEFAULT CURRENT_TIMESTAMP,
  `commission`  decimal(5,2) DEFAULT '8.00',
  PRIMARY KEY (`id_hote`),
  KEY `id_client` (`id_client`),
  CONSTRAINT `hote_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `utilisateur` (`id_client`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table logement (dépend de hote)
CREATE TABLE `logement` (
  `id_logement`       int          NOT NULL AUTO_INCREMENT,
  `id_hote`           int          NOT NULL,
  `titre`             varchar(255) NOT NULL,
  `description`       text,
  `type`              enum('villa','appartement','studio','maison','loft','autre') NOT NULL,
  `region_ville`      varchar(100) NOT NULL,
  `prix_nuit`         decimal(10,2) NOT NULL,
  `nb_lits`           int          DEFAULT '1',
  `nb_chambres`       int          DEFAULT '1',
  `nb_sdb`            int          DEFAULT '1',
  `surface`           int          DEFAULT NULL,
  `statut`            enum('actif','inactif','en_revision','supprime') DEFAULT 'en_revision',
  `date_creation`     datetime     DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `adresse_exacte`    text,
  `latitude`          decimal(10,8) DEFAULT NULL,
  `longitude`         decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id_logement`),
  KEY `id_hote` (`id_hote`),
  CONSTRAINT `logement_ibfk_1` FOREIGN KEY (`id_hote`) REFERENCES `hote` (`id_hote`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table photo
--    CORRECTIONS apportées :
--      • Ajout de `id_hote`  → utilisé par admin/photos.php (JOIN) et publier.php (INSERT)
--      • Ajout de `statut`   → utilisé par functions.php : WHERE statut = 'active'
-- Supprimer l'ancienne table
-- 1. Supprimer l'ancienne table photo
DROP TABLE IF EXISTS photo;

-- 2. Recréer la table avec la bonne structure
CREATE TABLE photo (
    id_photo INT PRIMARY KEY AUTO_INCREMENT,
    id_logement INT NOT NULL,
    id_hote INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    description TEXT DEFAULT NULL,
    statut ENUM('active','en_revision','rejetee') DEFAULT 'en_revision',
    est_principale TINYINT DEFAULT 0,
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `id_logement` (`id_logement`),
    KEY `id_hote` (`id_hote`),
    CONSTRAINT `photo_ibfk_1` FOREIGN KEY (`id_logement`) REFERENCES logement(id_logement) ON DELETE CASCADE,
    CONSTRAINT `photo_ibfk_2` FOREIGN KEY (`id_hote`) REFERENCES hote(id_hote) ON DELETE CASCADE
);

-- 6. Table reservation
CREATE TABLE `reservation` (
  `id_reservation` int          NOT NULL AUTO_INCREMENT,
  `id_client`     int          NOT NULL,
  `id_logement`   int          NOT NULL,
  `date_deb`      date         NOT NULL,
  `date_fin`      date         NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `statut`        enum('en_attente','confirmee','annulee','terminee','refusee') DEFAULT 'en_attente',
  `date_creation` datetime     DEFAULT CURRENT_TIMESTAMP,
  `nb_voyageurs`  int          DEFAULT '1',
  `commentaire`   text,
  PRIMARY KEY (`id_reservation`),
  KEY `id_client` (`id_client`),
  KEY `id_logement` (`id_logement`),
  CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`id_client`)   REFERENCES `utilisateur`   (`id_client`)   ON DELETE CASCADE,
  CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`id_logement`) REFERENCES `logement` (`id_logement`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table demande
--    CORRECTIONS apportées :
--      • Ajout de `id_logement` → utilisé par mes-demandes.php et l'ALTER qui existait à la fin
--      • Ajout de `montant`     → même raison
--      • Ajout de `message`     → même raison
CREATE TABLE `demande` (
  `id_demande`      int          NOT NULL AUTO_INCREMENT,
  `id_client`       int          NOT NULL,
  `id_logement`     int          DEFAULT NULL,                   -- logement concerné (optionnel)
  `date_deb`        date         NOT NULL,
  `date_fin`        date         NOT NULL,
  `nb_nuits`        int          DEFAULT NULL,
  `budget`          decimal(10,2) DEFAULT NULL,
  `montant`         decimal(10,2) DEFAULT NULL,                  -- montant proposé / accepté
  `region_souhaitee` varchar(255) DEFAULT NULL,
  `statut`          enum('en_attente','traitee','refusee','en_cours') DEFAULT 'en_attente',
  `description`     text,
  `message`         text,                                        -- message libre du client
  `date_creation`   datetime     DEFAULT CURRENT_TIMESTAMP,
  `nb_voyageurs`    int          DEFAULT '1',
  PRIMARY KEY (`id_demande`),
  KEY `id_client` (`id_client`),
  KEY `id_logement` (`id_logement`),
  CONSTRAINT `demande_ibfk_1` FOREIGN KEY (`id_client`)   REFERENCES `utilisateur`   (`id_client`)   ON DELETE CASCADE,
  CONSTRAINT `demande_ibfk_2` FOREIGN KEY (`id_logement`) REFERENCES `logement` (`id_logement`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table favori
CREATE TABLE `favori` (
  `id_client`   int      NOT NULL,
  `id_logement` int      NOT NULL,
  `date_ajout`  datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_client`,`id_logement`),
  KEY `id_logement` (`id_logement`),
  CONSTRAINT `favori_ibfk_1` FOREIGN KEY (`id_client`)   REFERENCES `utilisateur`   (`id_client`)   ON DELETE CASCADE,
  CONSTRAINT `favori_ibfk_2` FOREIGN KEY (`id_logement`) REFERENCES `logement` (`id_logement`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table notification
CREATE TABLE `notification` (
  `id_notification` int          NOT NULL AUTO_INCREMENT,
  `id_client`       int          NOT NULL,
  `titre`           varchar(255) NOT NULL,
  `message`         text         NOT NULL,
  `type`            enum('reservation','demande','systeme','info') DEFAULT 'info',
  `date_creation`   datetime     DEFAULT CURRENT_TIMESTAMP,
  `lu`              tinyint(1)   DEFAULT '0',
  PRIMARY KEY (`id_notification`),
  KEY `id_client` (`id_client`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `utilisateur` (`id_client`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table correspondance
CREATE TABLE `correspondance` (
  `id_correspondance` int          NOT NULL AUTO_INCREMENT,
  `id_expediteur`     int          NOT NULL,
  `id_destinataire`   int          NOT NULL,
  `message`           text         NOT NULL,
  `date_envoi`        datetime     DEFAULT CURRENT_TIMESTAMP,
  `lu`                tinyint(1)   DEFAULT '0',
  `sujet`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_correspondance`),
  KEY `id_expediteur`   (`id_expediteur`),
  KEY `id_destinataire` (`id_destinataire`),
  CONSTRAINT `correspondance_ibfk_1` FOREIGN KEY (`id_expediteur`)   REFERENCES `utilisateur` (`id_client`) ON DELETE CASCADE,
  CONSTRAINT `correspondance_ibfk_2` FOREIGN KEY (`id_destinataire`) REFERENCES `utilisateur` (`id_client`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Données initiales
-- ============================================================

-- Administrateur (mot de passe : admin123)
INSERT INTO `utilisateur` (`nom`, `prenom`, `email`, `telephone`, `ville`, `type_client`, `mot_de_passe`, `statut`) VALUES
('Admin', 'Super', 'admin@logements.tn', '+216 00 000 000', 'Tunis', 'admin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'actif');

-- Propriétaire exemple (mot de passe : admin123)
INSERT INTO `utilisateur` (`nom`, `prenom`, `email`, `telephone`, `ville`, `type_client`, `mot_de_passe`, `statut`) VALUES
('Jlassi', 'Mohamed', 'jlassi@email.com', '+216 55 100 200', 'Hammamet', 'proprietaire',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'actif');

INSERT INTO `hote` (`id_client`, `statut_hote`) VALUES (2, 'actif');

-- Logements exemples
INSERT INTO `logement` (`id_hote`, `titre`, `description`, `type`, `region_ville`, `prix_nuit`, `nb_lits`, `nb_chambres`, `nb_sdb`, `surface`, `statut`) VALUES
(1, 'Villa avec piscine privée',
 'Magnifique villa de prestige avec piscine privée chauffée, située à 5 minutes de la plage.',
 'villa', 'Hammamet, Nabeul', 320.00, 6, 3, 2, 180, 'actif'),
(1, 'Appartement vue mer',
 'Superbe appartement avec vue imprenable sur la mer Méditerranée.',
 'appartement', 'La Marsa, Tunis', 180.00, 2, 1, 1, 75, 'actif'),
(1, 'Riad traditionnel',
 'Riad authentique au cœur de la médina de Tunis, rénové avec goût.',
 'maison', 'Médina, Tunis', 250.00, 4, 2, 2, 120, 'actif');

-- Clients exemples (mot de passe : admin123)
INSERT INTO `utilisateur` (`nom`, `prenom`, `email`, `telephone`, `ville`, `type_client`, `mot_de_passe`, `statut`) VALUES
('Zarai',    'Oumayma', 'oumayma@email.com', '+216 22 111 222', 'Tunis', 'client',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'actif'),
('Trabelsi', 'Karim',   'karim@email.com',   '+216 55 333 444', 'Sfax',  'client',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'actif');

