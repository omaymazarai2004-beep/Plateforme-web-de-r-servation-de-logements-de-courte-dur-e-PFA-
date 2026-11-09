<?php
// mes-reservations.php - Page des réservations du client
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour voir vos réservations');
    exit();
}

$id_client = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Gestion de l'annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $id_reservation = $_POST['id_reservation'] ?? 0;
    
    // Vérifier que la réservation appartient bien au client
    $checkSql = "SELECT r.*, l.id_hote FROM reservation r 
                 JOIN logement l ON r.id_logement = l.id_logement 
                 WHERE r.id_reservation = :id_reservation AND r.id_client = :id_client";
    $checkStmt = Database::execute($checkSql, [
        ':id_reservation' => $id_reservation,
        ':id_client' => $id_client
    ]);
    $reservation = $checkStmt->fetch();
    
    if ($reservation && strtotime($reservation['date_deb']) > time()) {
        // Annuler la réservation
        $sql = "UPDATE reservation SET statut = 'annulee' WHERE id_reservation = :id_reservation";
        Database::execute($sql, [':id_reservation' => $id_reservation]);
        $success = "Votre réservation a été annulée avec succès.";
        
        // Notifier l'hôte
        $sqlNotif = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (:id_hote, 'Réservation annulée', 'Un client a annulé sa réservation', 'reservation')";
        Database::execute($sqlNotif, [':id_hote' => $reservation['id_hote']]);
    } else {
        $error = "Impossible d'annuler cette réservation (délai dépassé ou réservation déjà passée).";
    }
}

// Récupérer les réservations CONFIRMÉES uniquement (pas en_attente ni refusee)
$sql = "SELECT r.*, 
        l.titre, 
        l.region_ville, 
        l.prix_nuit,
        l.type,
        l.adresse_exacte,
        CONCAT(hc.prenom, ' ', hc.nom) as hote_nom, 
        hc.email as hote_email,
        hc.telephone as hote_telephone
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN hote h ON l.id_hote = h.id_hote
        JOIN utilisateur hc ON h.id_client = hc.id_client
        WHERE r.id_client = :id_client AND r.statut IN ('confirmee', 'annulee', 'terminee')
        ORDER BY r.date_deb DESC";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$reservations = $stmt->fetchAll();

// Séparer réservations par statut
$upcoming = [];
$current = [];
$past = [];
$cancelled = [];

foreach ($reservations as $r) {
    $dateFin = strtotime($r['date_fin']);
    $dateDeb = strtotime($r['date_deb']);
    $now = time();
    
    if ($r['statut'] == 'annulee') {
        $cancelled[] = $r;
    } elseif ($dateFin < $now) {
        $past[] = $r;
    } elseif ($dateDeb <= $now && $dateFin >= $now) {
        $current[] = $r;
    } else {
        $upcoming[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes réservations - Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root {
    --airbnb: #FF5A5F;
    --airbnb-dark: #E00B41;
    --airbnb-light: #FF8A8F;
    --gray-bg: #F7F7F7;
    --gray-border: #DDDDDD;
    --text-dark: #222222;
    --text-light: #717171;
}

body {
    background: var(--gray-bg);
}

.bookings-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 24px;
}

.page-header {
    margin-bottom: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.page-header h1 i {
    color: var(--airbnb);
    margin-right: 10px;
}

.page-header p {
    color: var(--text-light);
}

.stats-badge {
    background: white;
    padding: 8px 20px;
    border-radius: 40px;
    font-size: 14px;
    color: var(--text-dark);
    border: 1px solid var(--gray-border);
}

.stats-badge strong {
    color: var(--airbnb);
    font-size: 18px;
}

/* Alertes */
.alert {
    padding: 14px 18px;
    border-radius: 16px;
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert i {
    font-size: 18px;
}

/* Sections */
.section {
    margin-bottom: 48px;
}

.section-title {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-dark);
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-border);
}

.section-title i {
    color: var(--airbnb);
}

.section-title .count {
    background: var(--gray-bg);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-light);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 24px;
    border: 1px solid var(--gray-border);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.4;
    color: var(--text-light);
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 24px;
}

.btn-primary {
    background: var(--airbnb);
    color: white;
    padding: 12px 32px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

.btn-outline {
    background: white;
    border: 1px solid var(--gray-border);
    padding: 10px 20px;
    border-radius: 40px;
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.btn-outline:hover {
    border-color: var(--airbnb);
    color: var(--airbnb);
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 40px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-danger:hover {
    background: #b91c1c;
    transform: scale(1.02);
}

/* Cartes réservation */
.booking-card {
    background: white;
    border-radius: 20px;
    margin-bottom: 20px;
    overflow: hidden;
    border: 1px solid var(--gray-border);
    transition: all 0.2s;
    display: flex;
    flex-wrap: wrap;
}

.booking-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.booking-image {
    width: 260px;
    min-height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.booking-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--airbnb), var(--airbnb-dark));
    color: white;
    text-align: center;
}

.booking-image-placeholder i {
    font-size: 48px;
    margin-bottom: 12px;
}

.booking-image-placeholder span {
    font-size: 14px;
    opacity: 0.9;
}

.booking-content {
    flex: 1;
    padding: 24px;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.booking-title h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text-dark);
}

.booking-title h3 a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.2s;
}

.booking-title h3 a:hover {
    color: var(--airbnb);
}

.booking-location {
    color: var(--text-light);
    font-size: 14px;
}

.booking-location i {
    margin-right: 4px;
    color: var(--airbnb);
}

.status-badge {
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.confirmee {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.terminee {
    background: #e0e7ff;
    color: #3730a3;
}

.status-badge.en_cours {
    background: #dbeafe;
    color: #2563eb;
}

.booking-dates {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 16px 0;
    padding: 12px 0;
    border-top: 1px solid var(--gray-border);
    border-bottom: 1px solid var(--gray-border);
}

.date-block {
    display: flex;
    align-items: center;
    gap: 12px;
}

.date-icon {
    width: 40px;
    height: 40px;
    background: var(--gray-bg);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--airbnb);
    font-size: 18px;
}

.date-info {
    display: flex;
    flex-direction: column;
}

.date-label {
    font-size: 11px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-value {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 15px;
}

.nights-badge {
    background: var(--gray-bg);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    color: var(--text-light);
}

.booking-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin: 16px 0;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-light);
    font-size: 14px;
}

.detail-item i {
    width: 20px;
    color: var(--airbnb);
}

.detail-item strong {
    color: var(--text-dark);
}

.booking-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

/* Modal confirmation */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 24px;
    padding: 32px;
    max-width: 450px;
    width: 90%;
    text-align: center;
}

.modal-content i {
    font-size: 64px;
    color: #dc2626;
    margin-bottom: 16px;
}

.modal-content h3 {
    font-size: 24px;
    margin-bottom: 12px;
}

.modal-content p {
    color: var(--text-light);
    margin-bottom: 24px;
}

.modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

/* Responsive */
@media (max-width: 800px) {
    .bookings-container {
        padding: 24px 16px;
    }
    
    .booking-image {
        width: 100%;
        height: 140px;
    }
    
    .booking-card {
        flex-direction: column;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .section-title {
        font-size: 20px;
    }
    
    .booking-dates {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .booking-details {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .booking-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .booking-actions {
        flex-direction: column;
    }
    
    .booking-actions .btn-outline,
    .booking-actions .btn-danger,
    .booking-actions .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="bookings-container">
    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-calendar-check"></i> Mes réservations</h1>
            <p>Vos réservations confirmées et voyages</p>
        </div>
        <div class="stats-badge">
            📊 <strong><?php echo count($reservations); ?></strong> réservation<?php echo count($reservations) > 1 ? 's' : ''; ?>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert success">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Réservations en cours -->
    <?php if(!empty($current)): ?>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-play-circle"></i> En cours
            <span class="count"><?php echo count($current); ?></span>
        </div>
        <?php foreach($current as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
            $joursRestants = ceil((strtotime($r['date_fin']) - time()) / (60*60*24));
        ?>
        <div class="booking-card">
            <div class="booking-image">
                <div class="booking-image-placeholder">
                    <i class="fa-solid fa-home"></i>
                    <span><?php echo htmlspecialchars($r['type'] ?? 'Logement'); ?></span>
                </div>
            </div>
            <div class="booking-content">
                <div class="booking-header">
                    <div class="booking-title">
                        <h3><a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>"><?php echo htmlspecialchars($r['titre']); ?></a></h3>
                        <div class="booking-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                            <?php if($r['adresse_exacte']): ?>
                                <span style="font-size: 12px;">• <?php echo htmlspecialchars($r['adresse_exacte']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-badge en_cours">
                        <i class="fa-solid fa-hourglass-half"></i> En cours
                    </span>
                </div>
                
                <div class="booking-dates">
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar-check"></i></div>
                        <div class="date-info">
                            <span class="date-label">Arrivée</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?></span>
                        </div>
                    </div>
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar-times"></i></div>
                        <div class="date-info">
                            <span class="date-label">Départ</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></span>
                        </div>
                    </div>
                    <div class="nights-badge">
                        <i class="fa-solid fa-moon"></i> <?php echo $nbNuits; ?> nuits
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-user"></i>
                        <span>Propriétaire: <strong><?php echo htmlspecialchars($r['hote_nom']); ?></strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-users"></i>
                        <span><strong><?php echo $r['nb_voyageurs']; ?></strong> voyageur(s)</span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-coins"></i>
                        <span><strong><?php echo number_format($r['montant_total'], 0); ?> TND</strong> total</span>
                    </div>
                </div>
                
                <div class="booking-actions">
                    <a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> Voir le logement
                    </a>
                    <a href="correspondances.php?to=<?php echo urlencode($r['hote_email']); ?>" class="btn-outline">
                        <i class="fa-regular fa-message"></i> Contacter le propriétaire
                    </a>
                    <?php if($r['hote_telephone']): ?>
                        <a href="tel:<?php echo $r['hote_telephone']; ?>" class="btn-outline">
                            <i class="fa-solid fa-phone"></i> Appeler
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Réservations à venir -->
    <?php if(!empty($upcoming)): ?>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-plane-departure"></i> À venir
            <span class="count"><?php echo count($upcoming); ?></span>
        </div>
        <?php foreach($upcoming as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
            $joursAvant = ceil((strtotime($r['date_deb']) - time()) / (60*60*24));
        ?>
        <div class="booking-card">
            <div class="booking-image">
                <div class="booking-image-placeholder">
                    <i class="fa-solid fa-calendar-week"></i>
                    <span>Dans <?php echo $joursAvant; ?> jour<?php echo $joursAvant > 1 ? 's' : ''; ?></span>
                </div>
            </div>
            <div class="booking-content">
                <div class="booking-header">
                    <div class="booking-title">
                        <h3><a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>"><?php echo htmlspecialchars($r['titre']); ?></a></h3>
                        <div class="booking-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $r['statut']; ?>">
                        <?php if($r['statut'] === 'confirmee'): ?>
                            <i class="fa-solid fa-check-circle"></i> Confirmée
                        <?php elseif($r['statut'] === 'en_attente'): ?>
                            <i class="fa-solid fa-clock"></i> En attente
                        <?php else: ?>
                            <?php echo $r['statut']; ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="booking-dates">
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar-plus"></i></div>
                        <div class="date-info">
                            <span class="date-label">Arrivée</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?></span>
                        </div>
                    </div>
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar-minus"></i></div>
                        <div class="date-info">
                            <span class="date-label">Départ</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></span>
                        </div>
                    </div>
                    <div class="nights-badge">
                        <i class="fa-solid fa-moon"></i> <?php echo $nbNuits; ?> nuits
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-user"></i>
                        <span>Propriétaire: <strong><?php echo htmlspecialchars($r['hote_nom']); ?></strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-users"></i>
                        <span><strong><?php echo $r['nb_voyageurs']; ?></strong> voyageur(s)</span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-coins"></i>
                        <span><strong><?php echo number_format($r['montant_total'], 0); ?> TND</strong> total</span>
                    </div>
                </div>
                
                <div class="booking-actions">
                    <a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> Voir le logement
                    </a>
                    <a href="correspondances.php?to=<?php echo urlencode($r['hote_email']); ?>" class="btn-outline">
                        <i class="fa-regular fa-message"></i> Contacter le propriétaire
                    </a>
                    <button class="btn-danger" onclick="showCancelModal(<?php echo $r['id_reservation']; ?>, '<?php echo addslashes($r['titre']); ?>')">
                        <i class="fa-solid fa-ban"></i> Annuler
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Réservations passées -->
    <?php if(!empty($past)): ?>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Voyages effectués
            <span class="count"><?php echo count($past); ?></span>
        </div>
        <?php foreach($past as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
        ?>
        <div class="booking-card" style="opacity: 0.85;">
            <div class="booking-image">
                <div class="booking-image-placeholder" style="background: linear-gradient(135deg, #9ca3af, #6b7280);">
                    <i class="fa-solid fa-check-double"></i>
                    <span>Terminé</span>
                </div>
            </div>
            <div class="booking-content">
                <div class="booking-header">
                    <div class="booking-title">
                        <h3><a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>"><?php echo htmlspecialchars($r['titre']); ?></a></h3>
                        <div class="booking-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                        </div>
                    </div>
                    <span class="status-badge terminee">
                        <i class="fa-solid fa-check"></i> Terminée
                    </span>
                </div>
                
                <div class="booking-dates">
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="date-info">
                            <span class="date-label">Séjour</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?> → <?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></span>
                        </div>
                    </div>
                    <div class="nights-badge">
                        <i class="fa-solid fa-moon"></i> <?php echo $nbNuits; ?> nuits
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-user"></i>
                        <span>Propriétaire: <strong><?php echo htmlspecialchars($r['hote_nom']); ?></strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-coins"></i>
                        <span><strong><?php echo number_format($r['montant_total'], 0); ?> TND</strong> total</span>
                    </div>
                </div>
                
                <div class="booking-actions">
                    <a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> Revoir l'annonce
                    </a>
                    <a href="laisser-avis.php?id=<?php echo $r['id_logement']; ?>&reservation=<?php echo $r['id_reservation']; ?>" class="btn-outline">
                        <i class="fa-regular fa-star"></i> Laisser un avis
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Réservations annulées -->
    <?php if(!empty($cancelled)): ?>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-ban"></i> Réservations annulées
            <span class="count"><?php echo count($cancelled); ?></span>
        </div>
        <?php foreach($cancelled as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
        ?>
        <div class="booking-card" style="opacity: 0.7;">
            <div class="booking-image">
                <div class="booking-image-placeholder" style="background: linear-gradient(135deg, #9ca3af, #6b7280);">
                    <i class="fa-solid fa-ban"></i>
                    <span>Annulé</span>
                </div>
            </div>
            <div class="booking-content">
                <div class="booking-header">
                    <div class="booking-title">
                        <h3><?php echo htmlspecialchars($r['titre']); ?></h3>
                        <div class="booking-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                        </div>
                    </div>
                    <span class="status-badge annulee">
                        <i class="fa-solid fa-xmark"></i> Annulée
                    </span>
                </div>
                
                <div class="booking-dates">
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="date-info">
                            <span class="date-label">Initialement prévu</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?> → <?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-coins"></i>
                        <span><?php echo number_format($r['montant_total'], 0); ?> TND</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Aucune réservation -->
    <?php if(empty($reservations)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h3>Aucune réservation</h3>
            <p>Vous n'avez pas encore effectué de réservation.</p>
            <a href="index.php" class="btn-primary">Explorer les logements</a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmation d'annulation -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <h3>Annuler la réservation</h3>
        <p>Êtes-vous sûr de vouloir annuler votre réservation pour <strong id="cancelTitle"></strong> ?</p>
        <p style="font-size: 14px; color: #dc2626;">Cette action est irréversible.</p>
        <form method="POST" id="cancelForm">
            <input type="hidden" name="id_reservation" id="cancelReservationId">
            <div class="modal-buttons">
                <button type="button" class="btn-outline" onclick="closeCancelModal()">Non, garder</button>
                <button type="submit" name="cancel_reservation" class="btn-danger">Oui, annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCancelModal(id, title) {
    document.getElementById('cancelReservationId').value = id;
    document.getElementById('cancelTitle').innerText = title;
    document.getElementById('cancelModal').classList.add('active');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
}

// Fermer le modal en cliquant en dehors
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>