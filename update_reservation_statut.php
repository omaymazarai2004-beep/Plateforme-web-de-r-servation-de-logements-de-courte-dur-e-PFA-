<?php
// api/update_reservation_statut.php — Accepter ou refuser une réservation
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reservation = $_POST['id_reservation'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'accepter' ou 'refuser'
    $id_client = $_SESSION['user_id'];

    // Vérifier que l'utilisateur est bien le propriétaire du logement lié à cette réservation
    $sql = "SELECT r.*, l.titre, l.id_hote, h.id_client as id_proprietaire,
            CONCAT(c.prenom, ' ', c.nom) as client_nom
            FROM reservation r
            JOIN logement l ON r.id_logement = l.id_logement
            JOIN hote h ON l.id_hote = h.id_hote
            JOIN utilisateur c ON r.id_client = c.id_client
            WHERE r.id_reservation = :id_reservation";
    $stmt = Database::execute($sql, [':id_reservation' => $id_reservation]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        redirect('../hote-dashboard.php?error=' . urlencode('Réservation introuvable.'));
    }

    // Vérifier que c'est bien le propriétaire
    if ($reservation['id_proprietaire'] != $id_client) {
        redirect('../hote-dashboard.php?error=' . urlencode('Vous n\'êtes pas autorisé à gérer cette réservation.'));
    }

    // Vérifier que la réservation est en attente
    if ($reservation['statut'] !== 'en_attente') {
        redirect('../hote-dashboard.php?error=' . urlencode('Cette réservation a déjà été traitée.'));
    }

    if ($action === 'accepter') {
        // Accepter la réservation
        $sqlUpdate = "UPDATE reservation SET statut = 'confirmee' WHERE id_reservation = :id_reservation";
        Database::execute($sqlUpdate, [':id_reservation' => $id_reservation]);

        // Notifier le client
        $sqlNotif = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (:id_client, 'Réservation confirmée ✓', :message, 'reservation')";
        Database::execute($sqlNotif, [
            ':id_client' => $reservation['id_client'],
            ':message' => 'Votre réservation pour "' . $reservation['titre'] . '" a été acceptée par le propriétaire ! Préparez vos valises 🎉'
        ]);

        redirect('../hote-dashboard.php?success=' . urlencode('Réservation de ' . $reservation['client_nom'] . ' acceptée avec succès !'));
    } elseif ($action === 'refuser') {
        // Refuser la réservation
        $sqlUpdate = "UPDATE reservation SET statut = 'refusee' WHERE id_reservation = :id_reservation";
        Database::execute($sqlUpdate, [':id_reservation' => $id_reservation]);

        // Notifier le client
        $sqlNotif = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (:id_client, 'Réservation refusée', :message, 'reservation')";
        Database::execute($sqlNotif, [
            ':id_client' => $reservation['id_client'],
            ':message' => 'Votre demande de réservation pour "' . $reservation['titre'] . '" a été refusée par le propriétaire. Vous pouvez chercher un autre logement.'
        ]);

        redirect('../hote-dashboard.php?success=' . urlencode('Réservation de ' . $reservation['client_nom'] . ' refusée.'));
    } else {
        redirect('../hote-dashboard.php?error=' . urlencode('Action non reconnue.'));
    }
}
?>
