<?php
// api/register.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $password = $_POST['password'] ?? '';
    $type_client = $_POST['type_client'] ?? 'client';
    if (!in_array($type_client, ['client', 'proprietaire', 'admin'], true)) {
        $type_client = 'client';
    }
    
    $errors = [];
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $errors[] = "Tous les champs sont requis";
    }
    
    if (strlen($password) < 4) {
        $errors[] = "Le mot de passe doit contenir au moins 4 caractères";
    }
    
    // Vérifier si l'email existe déjà
    $sql = "SELECT id_client FROM utilisateur WHERE email = :email";
    $stmt = Database::execute($sql, [':email' => $email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }
    
    if (empty($errors)) {
        // Mot de passe en clair pour simplifier (à hasher en production)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, type_client, statut) 
                VALUES (:nom, :prenom, :email, :telephone, :password, :type_client, 'actif')";
        
        Database::execute($sql, [
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':telephone' => $telephone,
            ':password' => $hashedPassword,
            ':type_client' => $type_client
        ]);
        
        $id_client = Database::lastInsertId();
        
        // Si c'est un propriétaire, créer l'entrée dans la table hote
        if ($type_client === 'proprietaire') {
            $sql2 = "INSERT INTO hote (id_client, statut_hote) VALUES (:id_client, 'en_attente')";
            Database::execute($sql2, [':id_client' => $id_client]);
        }
        
        $_SESSION['user_id'] = $id_client;
        $_SESSION['user_nom'] = $prenom . ' ' . $nom;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $type_client;
        
        header('Location: ../index.php?register=success');
    } else {
        $error_msg = urlencode(implode(',', $errors));
        header('Location: ../index.php?register=error&msg=' . $error_msg);
    }
} else {
    header('Location: ../index.php');
}
?>