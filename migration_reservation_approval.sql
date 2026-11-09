-- Migration: Ajouter le système d'approbation des réservations
-- Exécuter ce fichier dans phpMyAdmin pour mettre à jour la base de données existante

-- 1. Ajouter la colonne id_reservation si elle n'existe pas
ALTER TABLE `reservation` 
ADD COLUMN `id_reservation` INT NOT NULL AUTO_INCREMENT FIRST,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id_reservation`),
ADD KEY `id_client` (`id_client`);

-- 2. Modifier l'enum statut pour ajouter 'refusee'
ALTER TABLE `reservation` 
MODIFY COLUMN `statut` ENUM('en_attente','confirmee','annulee','terminee','refusee') DEFAULT 'en_attente';

-- 3. Mettre toutes les réservations existantes sans statut en 'en_attente'
UPDATE `reservation` SET `statut` = 'en_attente' WHERE `statut` IS NULL;
