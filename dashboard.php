<?php
// dashboard.php - Point d'entrée unique
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour accéder à votre tableau de bord');
    exit();
}

$id_client = $_SESSION['user_id'];

// Admin (id_client = 1)
if ($id_client == 1) {
    header('Location: admin-dashboard.php');
    exit();
}

// Si l'utilisateur est propriétaire
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'proprietaire') {
    header('Location: hote-dashboard.php');
    exit();
}

// Client simple
header('Location: mes-reservations.php');
exit();
?>