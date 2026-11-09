<?php
// api/save_client.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

$id_client = $_POST['id_client'] ?? 0;
$prenom = $_POST['prenom'] ?? '';
$nom = $_POST['nom'] ?? '';
$email = $_POST['email'] ?? '';
$telephone = $_POST['telephone'] ?? '';
$ville = $_POST['ville'] ?? '';
$statut = $_POST['statut'] ?? 'actif';
$password = $_POST['password'] ?? '';

if($id_client) {
    // Update
    $sql = "UPDATE utilisateur SET prenom = :prenom, nom = :nom, email = :email, telephone = :telephone, ville = :ville, statut = :statut";
    $params = [':prenom' => $prenom, ':nom' => $nom, ':email' => $email, ':telephone' => $telephone, ':ville' => $ville, ':statut' => $statut];
    
    if(!empty($password)) {
        $sql .= ", mot_de_passe = :password";
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id_client = :id_client";
    $params[':id_client'] = $id_client;
    Database::execute($sql, $params);
} else {
    // Insert
    $sql = "INSERT INTO utilisateur (prenom, nom, email, telephone, ville, statut, mot_de_passe, type_client) 
            VALUES (:prenom, :nom, :email, :telephone, :ville, :statut, :password, 'client')";
    Database::execute($sql, [
        ':prenom' => $prenom,
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone,
        ':ville' => $ville,
        ':statut' => $statut,
        ':password' => password_hash($password, PASSWORD_DEFAULT)
    ]);
}

header('Location: ../admin/clients.php');
?>