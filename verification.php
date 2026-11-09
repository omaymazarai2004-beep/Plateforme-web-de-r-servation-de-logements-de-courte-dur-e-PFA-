<?php
echo "=== VÉRIFICATION DU PROJET ===\n\n";

// 1. Vérifier PHP
echo "PHP version: " . phpversion() . "\n";

// 2. Vérifier les dossiers
$folders = ['admin', 'api', 'assets', 'config', 'includes'];
foreach($folders as $folder) {
    echo is_dir($folder) ? "✓ Dossier $folder existe\n" : "✗ Dossier $folder manquant\n";
}

// 3. Vérifier la base de données
require_once 'config/database.php';
try {
    $db = Database::getInstance();
    echo "✓ Connexion MySQL OK\n";
} catch(Exception $e) {
    echo "✗ Erreur MySQL: " . $e->getMessage() . "\n";
}

// 4. Vérifier les fichiers importants
$files = ['index.php', 'logement-detail.php', 'logout.php'];
foreach($files as $file) {
    echo is_file($file) ? "✓ Fichier $file existe\n" : "✗ Fichier $file manquant\n";
}
?>