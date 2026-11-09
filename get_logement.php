<?php
// api/get_logement.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "SELECT * FROM logement WHERE id_logement = :id";
$stmt = Database::execute($sql, [':id' => $id]);
echo json_encode($stmt->fetch());
?>