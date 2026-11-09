<?php
// api/add_reservation.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = $_SESSION['user_id'];
    $id_logement = $_POST['id_logement'];
    $date_deb = $_POST['date_deb'];
    $date_fin = $_POST['date_fin'];
    $nb_voyageurs = $_POST['nb_voyageurs'];
    $commentaire = $_POST['commentaire'] ?? '';
    
    // Calculer le montant total
    $sql = "SELECT l.prix_nuit, l.titre, l.id_hote, h.id_client as id_proprietaire
            FROM logement l 
            JOIN hote h ON l.id_hote = h.id_hote
            WHERE l.id_logement = :id";
    $stmt = Database::execute($sql, [':id' => $id_logement]);
    $logement = $stmt->fetch();
    
    if (!$logement) {
        redirect('../index.php?error=Logement introuvable');
    }
    
    // Vérifier que la date d'arrivée est avant la date de départ
    if ($date_fin <= $date_deb) {
        redirect('../logement-detail.php?id=' . $id_logement . '&error=' . urlencode('La date de départ doit être après la date d\'arrivée.'));
    }
    
    // Vérifier que les dates ne sont pas dans le passé
    if ($date_deb < date('Y-m-d')) {
        redirect('../logement-detail.php?id=' . $id_logement . '&error=' . urlencode('La date d\'arrivée ne peut pas être dans le passé.'));
    }
    $deb = new DateTime($date_deb);
    $fin = new DateTime($date_fin);
    $nb_nuits = $deb->diff($fin)->days;
    $montant_total = $nb_nuits * $logement['prix_nuit'] * 1.08; // +8% frais
    
    // Vérifier les conflits de dates avec les réservations existantes (confirmées ou en attente)
    $sqlConflict = "SELECT COUNT(*) as nb FROM reservation 
                    WHERE id_logement = :id_logement 
                    AND statut IN ('confirmee', 'en_attente')
                    AND date_deb < :date_fin 
                    AND date_fin > :date_deb";
    $stmtConflict = Database::execute($sqlConflict, [
        ':id_logement' => $id_logement,
        ':date_deb' => $date_deb,
        ':date_fin' => $date_fin
    ]);
    $conflict = $stmtConflict->fetch();
    
    if ($conflict['nb'] > 0) {
        redirect('../logement-detail.php?id=' . $id_logement . '&error=' . urlencode('Ces dates sont déjà réservées ou en attente de confirmation. Veuillez choisir d\'autres dates.'));
    }
    // Créer la réservation avec statut 'en_attente'
    $data = [
        ':id_client' => $id_client,
        ':id_logement' => $id_logement,
        ':date_deb' => $date_deb,
        ':date_fin' => $date_fin,
        ':montant_total' => $montant_total,
        ':nb_voyageurs' => $nb_voyageurs,
        ':commentaire' => $commentaire
    ];
    
    if (createReservation($data)) {
        // Récupérer le nom du client pour la notification
        $sqlClient = "SELECT CONCAT(prenom, ' ', nom) as nom_complet FROM utilisateur WHERE id_client = :id";
        $stmtClient = Database::execute($sqlClient, [':id' => $id_client]);
        $client = $stmtClient->fetch();
        $clientNom = $client ? $client['nom_complet'] : 'Un client';
        
        // Notifier le client que sa réservation est en attente
        $sqlNotifClient = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (:id_client, 'Demande de réservation envoyée', 
                     :message, 'reservation')";
        Database::execute($sqlNotifClient, [
            ':id_client' => $id_client,
            ':message' => 'Votre demande de réservation pour "' . $logement['titre'] . '" a été envoyée. Elle est en attente de confirmation par le propriétaire.'
        ]);
        
        // Notifier le propriétaire qu'il a une nouvelle demande
        $sqlNotifHote = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (:id_proprietaire, 'Nouvelle demande de réservation', 
                     :message, 'reservation')";
        Database::execute($sqlNotifHote, [
            ':id_proprietaire' => $logement['id_proprietaire'],
            ':message' => $clientNom . ' souhaite réserver "' . $logement['titre'] . '" du ' . $date_deb . ' au ' . $date_fin . '. Veuillez accepter ou refuser cette demande depuis votre tableau de bord.'
        ]);
        
        redirect('../mes-demandes.php?success=' . urlencode('Votre demande de réservation a été envoyée ! Le propriétaire doit accepter ou refuser votre demande.'));
    } else {
        redirect('../logement-detail.php?id=' . $id_logement . '&error=1');
    }
}
?>