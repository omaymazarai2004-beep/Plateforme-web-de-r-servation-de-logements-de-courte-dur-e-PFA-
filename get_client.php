<?php
// api/get_client.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "SELECT id_client, prenom, nom, email, telephone, ville, statut FROM utilisateur WHERE id_client = :id";
$stmt = Database::execute($sql, [':id' => $id]);
echo json_encode($stmt->fetch());
?>