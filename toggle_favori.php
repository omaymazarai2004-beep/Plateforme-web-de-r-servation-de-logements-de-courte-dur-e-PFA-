<?php
// api/toggle_favori.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non connecté']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_logement = $_POST['id_logement'] ?? 0;

if (!$id_logement) {
    echo json_encode(['error' => 'ID logement manquant']);
    exit();
}

if (isFavori($id_client, $id_logement)) {
    removeFavori($id_client, $id_logement);
    echo json_encode(['favori' => false]);
} else {
    addFavori($id_client, $id_logement);
    echo json_encode(['favori' => true]);
}
?>