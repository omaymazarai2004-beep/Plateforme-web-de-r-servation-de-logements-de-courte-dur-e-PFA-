<?php
// mes-reservations-proprietaire.php - Page des réservations du propriétaire
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour voir vos réservations');
    exit();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'proprietaire') {
    header('Location: index.php?error=Accès réservé aux propriétaires');
    exit();
}

$id_client = $_SESSION['user_id'];

$sql = "SELECT id_hote FROM hote WHERE id_client = :id_client";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$hote = $stmt->fetch();

if (!$hote) {
    header('Location: index.php?error=Compte propriétaire introuvable');
    exit();
}

$id_hote = $hote['id_hote'];

$sql = "SELECT r.*, l.titre, l.region_ville, l.prix_nuit, l.type, l.adresse_exacte,
               CONCAT(c.prenom, ' ', c.nom) AS client_nom, c.email AS client_email, c.telephone AS client_telephone
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN utilisateur c ON r.id_client = c.id_client
        WHERE l.id_hote = :id_hote
        ORDER BY r.date_deb DESC";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$reservations = $stmt->fetchAll();

function statusInfo($status) {
    switch ($status) {
        case 'en_attente': return ['label' => 'En attente',  'class' => 'pending',   'icon' => 'clock'];
        case 'confirmee':  return ['label' => 'Confirmée',   'class' => 'confirmed', 'icon' => 'circle-check'];
        case 'terminee':   return ['label' => 'Terminée',    'class' => 'finished',  'icon' => 'flag-checkered'];
        case 'annulee':    return ['label' => 'Annulée',     'class' => 'cancelled', 'icon' => 'circle-xmark'];
        default:           return ['label' => ucfirst($status), 'class' => 'unknown', 'icon' => 'circle-question'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réservations propriétaire - Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root {
    --airbnb: #FF5A5F;
    --airbnb-dark: #E00B41;
    --gray-bg: #F7F7F7;
    --gray-border: #DDDDDD;
    --text-dark: #222222;
    --text-light: #717171;
}

body {
    background: var(--gray-bg);
}

/* ── Container ── */
.reservations-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 24px;
}

/* ── En-tête ── */
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

.reservation-stats {
    background: white;
    padding: 8px 20px;
    border-radius: 40px;
    font-size: 14px;
    color: var(--text-light);
    border: 1px solid var(--gray-border);
}

.reservation-stats strong {
    color: var(--airbnb);
    font-size: 18px;
}

/* ── Empty state ── */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 24px;
    border: 1px solid var(--gray-border);
}

.empty-state i {
    font-size: 80px;
    margin-bottom: 24px;
    opacity: 0.4;
    color: var(--text-light);
}

.empty-state h3 {
    font-size: 22px;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.empty-state p {
    color: var(--text-light);
}

/* ── Filtres ── */
.filter-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid var(--gray-border);
    background: white;
    border-radius: 40px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-light);
    font-family: 'Inter', sans-serif;
}

.filter-btn.active {
    background: var(--airbnb);
    border-color: var(--airbnb);
    color: white;
}

.filter-btn:hover:not(.active) {
    border-color: var(--airbnb);
    color: var(--airbnb);
}

/* ── Liste des réservations ── */
.reservations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reservation-item {
    background: white;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.2s;
    border: 1px solid var(--gray-border);
    position: relative;
    overflow: hidden;
}

/* Accent de couleur à gauche selon statut (comme « unread » dans notifications) */
.reservation-item.status-pending   { border-left: 4px solid #F59E0B; }
.reservation-item.status-confirmed { border-left: 4px solid #3B82F6; }
.reservation-item.status-finished  { border-left: 4px solid #10B981; }
.reservation-item.status-cancelled { border-left: 4px solid #EF4444; }

.reservation-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.reservation-content {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

/* Icône colorée selon statut */
.reservation-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.reservation-icon.pending   { background: #fffbeb; color: #F59E0B; }
.reservation-icon.confirmed { background: #eff6ff; color: #3B82F6; }
.reservation-icon.finished  { background: #ecfdf5; color: #10B981; }
.reservation-icon.cancelled { background: #fee2e2; color: #EF4444; }
.reservation-icon.unknown   { background: #f3f4f6; color: #9CA3AF; }

.reservation-details {
    flex: 1;
    min-width: 0;
}

.reservation-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.reservation-title {
    font-weight: 600;
    font-size: 16px;
    color: var(--text-dark);
}

.reservation-date {
    font-size: 12px;
    color: var(--text-light);
    white-space: nowrap;
}

/* Ligne d'infos : client · séjour · montant */
.reservation-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 8px;
}

.meta-block {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 14px;
    color: var(--text-light);
    min-width: 140px;
}

.meta-block i {
    margin-top: 2px;
    font-size: 13px;
    color: var(--airbnb);
    flex-shrink: 0;
}

.meta-block strong {
    display: block;
    font-size: 13px;
    color: var(--text-dark);
    font-weight: 600;
}

/* Badge statut */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 12px;
}

.badge-pending   { background: #FEF3C7; color: #92400E; }
.badge-confirmed { background: #DBEAFE; color: #1D4ED8; }
.badge-finished  { background: #ECFDF5; color: #0F766E; }
.badge-cancelled { background: #FEE2E2; color: #991B1B; }
.badge-unknown   { background: #F3F4F6; color: #374151; }

/* ── Responsive ── */
@media (max-width: 640px) {
    .reservations-container { padding: 24px 16px; }
    .page-header h1 { font-size: 28px; }
    .reservation-content { gap: 12px; }
    .reservation-icon { width: 40px; height: 40px; font-size: 16px; }
    .reservation-header { flex-direction: column; gap: 4px; }
    .meta-block { min-width: 100%; }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="reservations-container">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-calendar-check"></i> Réservations</h1>
            <p>Consultez toutes les réservations de vos logements.</p>
        </div>
        <div class="reservation-stats">
            🏡 <strong><?php echo count($reservations); ?></strong> réservation<?php echo count($reservations) > 1 ? 's' : ''; ?>
        </div>
    </div>

    <?php if (!empty($reservations)): ?>
    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">Toutes</button>
        <button class="filter-btn" data-filter="en_attente">En attente</button>
        <button class="filter-btn" data-filter="confirmee">Confirmées</button>
        <button class="filter-btn" data-filter="terminee">Terminées</button>
        <button class="filter-btn" data-filter="annulee">Annulées</button>
    </div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h3>Aucune réservation</h3>
            <p>Vos logements n'ont pas encore reçu de réservations.</p>
        </div>

    <?php else: ?>
        <div class="reservations-list" id="reservationsList">
            <?php foreach ($reservations as $r):
                $s = statusInfo($r['statut']);
                $dateDebObj = new DateTime($r['date_deb']);
                $dateFinObj = new DateTime($r['date_fin']);
                $now        = new DateTime();
                $diff       = $now->diff($dateDebObj);

                if ($diff->days == 0) {
                    $sejourLabel = "Commence aujourd'hui";
                } elseif ($dateDebObj > $now) {
                    $sejourLabel = "Dans " . $diff->days . " jour" . ($diff->days > 1 ? 's' : '');
                } else {
                    $sejourLabel = "Du " . $dateDebObj->format('d/m/Y') . " au " . $dateFinObj->format('d/m/Y');
                }
            ?>
            <div class="reservation-item status-<?php echo $s['class']; ?>"
                 data-status="<?php echo htmlspecialchars($r['statut']); ?>">
                <div class="reservation-content">

                    <!-- Icône statut -->
                    <div class="reservation-icon <?php echo $s['class']; ?>">
                        <i class="fa-solid fa-<?php echo $s['icon']; ?>"></i>
                    </div>

                    <div class="reservation-details">
                        <div class="reservation-header">
                            <div class="reservation-title">
                                <?php echo htmlspecialchars($r['titre']); ?>
                                <span style="font-weight:400;color:var(--text-light);font-size:14px;">
                                    &mdash; <?php echo htmlspecialchars($r['region_ville']); ?>
                                </span>
                            </div>
                            <div class="reservation-date">
                                <?php echo $dateDebObj->format('d/m/Y'); ?> → <?php echo $dateFinObj->format('d/m/Y'); ?>
                            </div>
                        </div>

                        <!-- Méta : client · séjour · montant -->
                        <div class="reservation-meta">
                            <div class="meta-block">
                                <i class="fa-solid fa-user"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($r['client_nom']); ?></strong>
                                    <?php echo htmlspecialchars($r['client_email']); ?><br>
                                    <?php echo htmlspecialchars($r['client_telephone']); ?>
                                </div>
                            </div>
                            <div class="meta-block">
                                <i class="fa-solid fa-calendar-days"></i>
                                <div>
                                    <strong>Séjour</strong>
                                    <?php echo $sejourLabel; ?>
                                </div>
                            </div>
                            <div class="meta-block">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <div>
                                    <strong>Montant</strong>
                                    <?php echo number_format($r['montant_total'], 0, ',', ' '); ?> TND
                                </div>
                            </div>
                        </div>

                        <!-- Badge statut -->
                        <span class="status-badge badge-<?php echo $s['class']; ?>">
                            <i class="fa-solid fa-<?php echo $s['icon']; ?>"></i>
                            <?php echo $s['label']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
// Filtrage des réservations
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filter = this.dataset.filter;
        document.querySelectorAll('.reservation-item').forEach(item => {
            item.style.display =
                (filter === 'all' || item.dataset.status === filter) ? 'block' : 'none';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>