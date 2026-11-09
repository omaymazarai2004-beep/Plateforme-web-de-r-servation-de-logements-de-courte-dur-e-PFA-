<?php
// api/get_reservation.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id_client = $_GET['id_client'] ?? 0;
$id_logement = $_GET['id_logement'] ?? 0;
$date_deb = $_GET['date_deb'] ?? '';

$sql = "SELECT r.*, l.titre, CONCAT(c.prenom, ' ', c.nom) as client_nom,
        CONCAT(hc.prenom, ' ', hc.nom) as hote_nom,
        DATEDIFF(r.date_fin, r.date_deb) as nb_nuits
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN utilisateur c ON r.id_client = c.id_client
        JOIN hote h ON l.id_hote = h.id_hote
        JOIN utilisateur hc ON h.id_client = hc.id_client
        WHERE r.id_client = :id_client AND r.id_logement = :id_logement AND r.date_deb = :date_deb";
$stmt = Database::execute($sql, [
    ':id_client' => $id_client,
    ':id_logement' => $id_logement,
    ':date_deb' => $date_deb
]);
echo json_encode($stmt->fetch());
?>