<?php
// check_encoding.php - Script pour vérifier l'encodage de la base de données
require_once 'config/database.php';

echo "=== VÉRIFICATION DE L'ENCODAGE ===\n\n";

try {
    // Vérifier les variables de caractères MySQL
    $stmt = Database::execute('SHOW VARIABLES LIKE "character_set%"');
    $results = $stmt->fetchAll();

    echo "Variables d'encodage MySQL :\n";
    foreach ($results as $row) {
        echo "  " . $row['Variable_name'] . ': ' . $row['Value'] . "\n";
    }

    echo "\n";

    // Tester avec des données d'exemple
    echo "Test d'encodage avec des données :\n";

    // Vérifier s'il y a des données dans la table logement
    $stmt = Database::execute('SELECT titre, description, region_ville FROM logement LIMIT 3');
    $logements = $stmt->fetchAll();

    foreach ($logements as $i => $logement) {
        echo "Logement " . ($i + 1) . " :\n";
        echo "  Titre: " . $logement['titre'] . "\n";
        echo "  Région: " . $logement['region_ville'] . "\n";
        echo "  Description (premiers 50 chars): " . substr($logement['description'], 0, 50) . "...\n";
        echo "  Encodage détecté titre: " . mb_detect_encoding($logement['titre']) . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo 'Erreur: ' . $e->getMessage() . "\n";
}
?>