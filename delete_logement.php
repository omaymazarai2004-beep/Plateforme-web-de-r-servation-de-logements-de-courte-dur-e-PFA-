<?php
// api/delete_logement.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "DELETE FROM logement WHERE id_logement = :id";
Database::execute($sql, [':id' => $id]);

header('Location: ../admin/logements.php');
?>