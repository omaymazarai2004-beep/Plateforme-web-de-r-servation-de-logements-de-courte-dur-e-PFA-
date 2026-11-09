<?php
// api/login.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(empty($email) || empty($password)) {
        header('Location: ../index.php?error=1');
        exit();
    }
    
    $sql = "SELECT * FROM utilisateur WHERE email = :email AND statut = 'actif'";
    $stmt = Database::execute($sql, [':email' => $email]);
    $user = $stmt->fetch();
    
    // Vérification du mot de passe (admin123 en clair pour test)
    if($user && ($password === 'admin123' || password_verify($password, $user['mot_de_passe']))) {
        $_SESSION['user_id'] = $user['id_client'];
        $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['type_client'];
        
        // Si c'est un propriétaire, récupérer son id_hote
        if ($user['type_client'] === 'proprietaire') {
            $sql2 = "SELECT id_hote FROM hote WHERE id_client = :id_client";
            $stmt2 = Database::execute($sql2, [':id_client' => $user['id_client']]);
            $hote = $stmt2->fetch();
            if ($hote) {
                $_SESSION['id_hote'] = $hote['id_hote'];
            }
        }
        
        header('Location: ../index.php?success=1');
    } else {
        header('Location: ../index.php?error=1');
    }
} else {
    header('Location: ../index.php');
}
?>