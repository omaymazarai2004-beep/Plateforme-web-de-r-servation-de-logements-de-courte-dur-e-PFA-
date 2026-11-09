<?php
// api/get_demande.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "SELECT d.*, CONCAT(c.prenom, ' ', c.nom) as client_nom, c.email as client_email, c.telephone
        FROM demande d
        JOIN utilisateur c ON d.id_client = c.id_client
        WHERE d.id_demande = :id";
$stmt = Database::execute($sql, [':id' => $id]);
echo json_encode($stmt->fetch());
?>