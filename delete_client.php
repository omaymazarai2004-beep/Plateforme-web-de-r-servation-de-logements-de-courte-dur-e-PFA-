<?php
// api/delete_client.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$sql = "DELETE FROM utilisateur WHERE id_client = :id AND type_client = 'client'";
Database::execute($sql, [':id' => $id]);

header('Location: ../admin/clients.php');
?>