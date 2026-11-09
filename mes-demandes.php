<?php
// mes-demandes.php - Demandes de réservation en attente et refusées
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour voir vos demandes');
    exit();
}

$id_client = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Récupérer les demandes (en_attente + refusee) depuis la table reservation
$sql = "SELECT r.*, 
        l.titre, 
        l.region_ville, 
        l.prix_nuit,
        l.type,
        CONCAT(hc.prenom, ' ', hc.nom) as hote_nom, 
        hc.email as hote_email,
        hc.telephone as hote_telephone
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN hote h ON l.id_hote = h.id_hote
        JOIN utilisateur hc ON h.id_client = hc.id_client
        WHERE r.id_client = :id_client AND r.statut IN ('en_attente', 'refusee')
        ORDER BY r.date_creation DESC";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$demandes = $stmt->fetchAll();

// Séparer par statut
$pending = [];
$refused = [];
foreach ($demandes as $d) {
    if ($d['statut'] === 'en_attente') {
        $pending[] = $d;
    } else {
        $refused[] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes demandes - Logements Tunisie</title>
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

.requests-container {
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
    margin-right: 10px;
    color: var(--airbnb);
}

.page-header p {
    color: var(--text-light);
    font-size: 16px;
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
    color: #f59e0b;
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

.btn-explore {
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

.btn-explore:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

/* Pending banner */
.pending-banner {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 2px solid #f59e0b;
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #92400e;
    font-weight: 500;
}

.pending-banner i {
    font-size: 24px;
    color: #f59e0b;
}

.pending-pulse {
    animation: pendingPulse 2s ease-in-out infinite;
}

@keyframes pendingPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Request cards */
.request-card {
    background: white;
    border-radius: 20px;
    margin-bottom: 20px;
    overflow: hidden;
    border: 1px solid var(--gray-border);
    transition: all 0.2s;
    display: flex;
    flex-wrap: wrap;
}

.request-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.request-image {
    width: 220px;
    min-height: 200px;
    position: relative;
    overflow: hidden;
}

.request-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    gap: 8px;
}

.request-image-placeholder i {
    font-size: 48px;
}

.request-image-placeholder span {
    font-size: 14px;
    opacity: 0.9;
    font-weight: 500;
}

.request-content {
    flex: 1;
    padding: 24px;
    min-width: 280px;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.request-title h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text-dark);
}

.request-title h3 a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.2s;
}

.request-title h3 a:hover {
    color: var(--airbnb);
}

.request-location {
    color: var(--text-light);
    font-size: 14px;
}

.request-location i {
    margin-right: 4px;
    color: var(--airbnb);
}

.status-badge {
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.en_attente {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.refusee {
    background: #fee2e2;
    color: #dc2626;
}

.request-dates {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 16px 0;
    padding: 12px 0;
    border-top: 1px solid var(--gray-border);
    border-bottom: 1px solid var(--gray-border);
    flex-wrap: wrap;
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

.request-details {
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

.request-info-box {
    margin-top: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.request-info-box.waiting {
    background: #fffbeb;
    color: #92400e;
}

.request-info-box.refused {
    background: #fef2f2;
    color: #991b1b;
}

.request-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
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

/* Responsive */
@media (max-width: 800px) {
    .requests-container {
        padding: 24px 16px;
    }
    
    .request-image {
        width: 100%;
        height: 120px;
        min-height: auto;
    }
    
    .request-card {
        flex-direction: column;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .request-dates {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .request-details {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .request-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .request-actions {
        flex-direction: column;
    }
    
    .request-actions .btn-outline,
    .request-actions .btn-explore {
        width: 100%;
        justify-content: center;
        text-align: center;
    }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="requests-container">
    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-paper-plane"></i> Mes demandes</h1>
            <p>Suivez l'état de vos demandes de réservation</p>
        </div>
        <?php if(!empty($pending)): ?>
        <div class="stats-badge">
            ⏳ <strong><?php echo count($pending); ?></strong> en attente
        </div>
        <?php endif; ?>
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
    
    <!-- Demandes en attente -->
    <?php if(!empty($pending)): ?>
    <div class="pending-banner">
        <i class="fa-solid fa-hourglass-half pending-pulse"></i>
        <div>Vous avez <strong><?php echo count($pending); ?></strong> demande<?php echo count($pending) > 1 ? 's' : ''; ?> en attente de confirmation par le propriétaire.</div>
    </div>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-hourglass-half"></i> En attente d'approbation
            <span class="count"><?php echo count($pending); ?></span>
        </div>
        <?php foreach($pending as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
        ?>
        <div class="request-card">
            <div class="request-image">
                <div class="request-image-placeholder" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fa-solid fa-hourglass-half pending-pulse"></i>
                    <span>En attente</span>
                </div>
            </div>
            <div class="request-content">
                <div class="request-header">
                    <div class="request-title">
                        <h3><a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>"><?php echo htmlspecialchars($r['titre']); ?></a></h3>
                        <div class="request-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                        </div>
                    </div>
                    <span class="status-badge en_attente">
                        <i class="fa-solid fa-clock"></i> En attente
                    </span>
                </div>
                
                <div class="request-dates">
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
                
                <div class="request-details">
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
                
                <?php if(!empty($r['commentaire'])): ?>
                <div style="margin-top: 8px; padding: 8px 12px; background: #f7f7f7; border-radius: 8px; font-size: 13px; color: var(--text-light);">
                    <i class="fa-regular fa-comment" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($r['commentaire']); ?>
                </div>
                <?php endif; ?>
                
                <div class="request-info-box waiting">
                    <i class="fa-solid fa-info-circle"></i>
                    Le propriétaire doit accepter votre demande. Vous serez notifié dès qu'il aura répondu.
                </div>
                
                <div class="request-actions">
                    <a href="logement-detail.php?id=<?php echo $r['id_logement']; ?>" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> Voir le logement
                    </a>
                    <a href="correspondances.php?to=<?php echo urlencode($r['hote_email']); ?>" class="btn-outline">
                        <i class="fa-regular fa-message"></i> Contacter le propriétaire
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Demandes refusées -->
    <?php if(!empty($refused)): ?>
    <div class="section">
        <div class="section-title">
            <i class="fa-solid fa-circle-xmark"></i> Demandes refusées
            <span class="count"><?php echo count($refused); ?></span>
        </div>
        <?php foreach($refused as $r): 
            $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
        ?>
        <div class="request-card" style="opacity: 0.75;">
            <div class="request-image">
                <div class="request-image-placeholder" style="background: linear-gradient(135deg, #dc2626, #991b1b);">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span>Refusée</span>
                </div>
            </div>
            <div class="request-content">
                <div class="request-header">
                    <div class="request-title">
                        <h3><?php echo htmlspecialchars($r['titre']); ?></h3>
                        <div class="request-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r['region_ville']); ?>
                        </div>
                    </div>
                    <span class="status-badge refusee">
                        <i class="fa-solid fa-xmark"></i> Refusée
                    </span>
                </div>
                
                <div class="request-dates">
                    <div class="date-block">
                        <div class="date-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="date-info">
                            <span class="date-label">Demandé pour</span>
                            <span class="date-value"><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?> → <?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></span>
                        </div>
                    </div>
                    <div class="nights-badge">
                        <i class="fa-solid fa-moon"></i> <?php echo $nbNuits; ?> nuits
                    </div>
                </div>
                
                <div class="request-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-coins"></i>
                        <span><strong><?php echo number_format($r['montant_total'], 0); ?> TND</strong></span>
                    </div>
                </div>
                
                <div class="request-info-box refused">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Le propriétaire a refusé cette demande. Vous pouvez chercher un autre logement.
                </div>
                
                <div class="request-actions">
                    <a href="index.php" class="btn-explore">
                        <i class="fa-solid fa-search"></i> Chercher un autre logement
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Aucune demande -->
    <?php if(empty($demandes)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-paper-plane"></i>
            <h3>Aucune demande en cours</h3>
            <p>Vous n'avez pas de demande de réservation en attente. Toutes vos réservations confirmées se trouvent dans <strong>Mes réservations</strong>.</p>
            <a href="index.php" class="btn-explore">Explorer les logements</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>