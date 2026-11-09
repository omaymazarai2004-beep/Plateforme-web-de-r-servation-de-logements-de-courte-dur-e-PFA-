================================================================================
  APPLICATION "LOGEMENTS" — PFA1-2026-05
  Première année en Génie Informatique — ENSIT
  Année universitaire : 2025-2026
================================================================================
 
Auteurs :
  - ZARAI OUMAYMA
  - ALIBI RADHI
  - YASSINE IBN KHAYATIA
 
Encadrante : Madame Zoulel KOUKI
Présidente du jury : Madame Emna SOUISSI
 
--------------------------------------------------------------------------------
DESCRIPTION
--------------------------------------------------------------------------------
Application web "Logements" permettant la mise en relation entre propriétaires
et locataires pour la location de courte durée en Tunisie. Elle offre :
  - Recherche multicritères de logements (destination, type, prix)
  - Gestion des annonces par les propriétaires
  - Réservation en ligne avec calendrier de disponibilités
  - Tableau de bord propriétaire avec KPIs
  - Chat bot intelligent (Assistant Logement)
  - Back-office d'administration
  - Messagerie et notifications
 
--------------------------------------------------------------------------------
PRÉREQUIS
--------------------------------------------------------------------------------
  - XAMPP v8.x ou supérieur (Apache + MySQL + PHP)
      Téléchargement : https://www.apachefriends.org
  - Navigateur web moderne (Chrome, Firefox, Edge)
  - PHP >= 7.4
  - MySQL >= 5.7
 
--------------------------------------------------------------------------------
INSTALLATION ET COMPILATION
--------------------------------------------------------------------------------
 
ÉTAPE 1 — Installer XAMPP
  Télécharger et installer XAMPP depuis https://www.apachefriends.org
  Démarrer les services Apache et MySQL depuis le panneau de contrôle XAMPP.
 
ÉTAPE 2 — Déployer les fichiers sources
  Copier le dossier "logements" dans le répertoire web de XAMPP :
    Windows : C:\xampp\htdocs\
    Linux/Mac : /opt/lampp/htdocs/
 
ÉTAPE 3 — Créer la base de données
  a) Ouvrir phpMyAdmin : http://localhost/phpmyadmin
  b) Créer une nouvelle base de données nommée : logements_db
     (Interclassement : utf8_general_ci)
  c) Sélectionner la base "logements_db", aller dans l'onglet "SQL"
  d) Importer le fichier : logements/database.sql
     (ou utiliser Import > Choisir le fichier SQL)
 
ÉTAPE 4 — Configurer la connexion à la base de données
  Ouvrir le fichier : logements/database/config.php
  Modifier les paramètres selon votre environnement :
 
      define('DB_HOST', 'localhost');
      define('DB_NAME', 'logements_db');
      define('DB_USER', 'root');       // utilisateur MySQL
      define('DB_PASS', '');           // mot de passe MySQL (vide par défaut avec XAMPP)
 
ÉTAPE 5 — Accéder à l'application
  Ouvrir un navigateur et aller à :
      http://localhost/logements/
 
--------------------------------------------------------------------------------
COMPTES DE TEST (après alimentation de la BD)
--------------------------------------------------------------------------------
  Locataire :
    Email    : oumayma@email.com
    Mot de passe : admin123
 
  Propriétaire :
    Email    : jlassi@email.com
    Mot de passe : admin123
 
  Administrateur :
    Email    : admin@logements.tn
    Mot de passe : admin123
 
--------------------------------------------------------------------------------
STRUCTURE DU DOSSIER
--------------------------------------------------------------------------------
PFA1-25-26-05/
│
├── Rap_PFA1-25-26-05.pdf       ← Rapport final (page de garde incluse)
├── README.txt                  ← Ce fichier
│
├── logements                   ← dossier qui contient l'application web

 
--------------------------------------------------------------------------------
TECHNOLOGIES UTILISÉES
--------------------------------------------------------------------------------
  - Frontend : HTML5, CSS3, JavaScript
  - Backend  : PHP (PDO pour la connexion BDD)
  - Base de données : MySQL (via XAMPP/phpMyAdmin)
  - Modélisation : PowerAMC (MCD, MLD, MPD)
  - IDE : Visual Studio Code
 
--------------------------------------------------------------------------------
NOTES IMPORTANTES
--------------------------------------------------------------------------------
  - Vérifier que le service Apache et MySQL sont bien démarrés dans XAMPP
    avant d'accéder à l'application.
  - Les images uploadées par les utilisateurs sont stockées dans logements/uploads/logements/
    — s'assurer que ce dossier est accessible en écriture (chmod 755 sur Linux).
  - En cas d'erreur de connexion à la base de données, vérifier le fichier config.php.
 
================================================================================
 
