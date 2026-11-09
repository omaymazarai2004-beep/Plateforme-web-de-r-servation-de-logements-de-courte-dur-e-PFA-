<?php
// api/save_logement.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

$id_logement = $_POST['id_logement'] ?? 0;
$titre = $_POST['titre'] ?? '';
$description = $_POST['description'] ?? '';
$type = $_POST['type'] ?? '';
$region_ville = $_POST['region_ville'] ?? '';
$prix_nuit = $_POST['prix_nuit'] ?? 0;
$nb_lits = $_POST['nb_lits'] ?? 1;
$nb_chambres = $_POST['nb_chambres'] ?? 1;
$statut = $_POST['statut'] ?? 'actif';

if($id_logement) {
    $sql = "UPDATE logement SET titre = :titre, description = :description, type = :type, region_ville = :region_ville, 
            prix_nuit = :prix_nuit, nb_lits = :nb_lits, nb_chambres = :nb_chambres, statut = :statut 
            WHERE id_logement = :id_logement";
    Database::execute($sql, [
        ':titre' => $titre, ':description' => $description, ':type' => $type,
        ':region_ville' => $region_ville, ':prix_nuit' => $prix_nuit,
        ':nb_lits' => $nb_lits, ':nb_chambres' => $nb_chambres,
        ':statut' => $statut, ':id_logement' => $id_logement
    ]);
} else {
    // Pour l'ajout, on associe à un hôte par défaut (le premier)
    $sql_hote = "SELECT id_hote FROM hote LIMIT 1";
    $stmt = Database::execute($sql_hote);
    $hote = $stmt->fetch();
    $id_hote = $hote ? $hote['id_hote'] : 1;
    
    $sql = "INSERT INTO logement (id_hote, titre, description, type, region_ville, prix_nuit, nb_lits, nb_chambres, statut) 
            VALUES (:id_hote, :titre, :description, :type, :region_ville, :prix_nuit, :nb_lits, :nb_chambres, :statut)";
    Database::execute($sql, [
        ':id_hote' => $id_hote, ':titre' => $titre, ':description' => $description,
        ':type' => $type, ':region_ville' => $region_ville, ':prix_nuit' => $prix_nuit,
        ':nb_lits' => $nb_lits, ':nb_chambres' => $nb_chambres, ':statut' => $statut
    ]);
}

header('Location: ../admin/logements.php');
?>