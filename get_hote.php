<?php
// api/get_hote.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "SELECT h.*, c.prenom, c.nom, c.email, c.telephone, c.ville 
        FROM hote h
        JOIN utilisateur c ON h.id_client = c.id_client
        WHERE h.id_hote = :id";
$stmt = Database::execute($sql, [':id' => $id]);
echo json_encode($stmt->fetch());
?>