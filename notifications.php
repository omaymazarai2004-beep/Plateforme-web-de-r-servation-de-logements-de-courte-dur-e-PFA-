<?php
// notifications.php - Centre de notifications
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$id_client = $_SESSION['user_id'];

// Marquer toutes les notifications comme lues
$sql = "UPDATE notification SET lu = 1 WHERE id_client = :id_client AND lu = 0";
Database::execute($sql, [':id_client' => $id_client]);

// Récupérer les notifications
$sql = "SELECT * FROM notification WHERE id_client = :id_client ORDER BY date_creation DESC";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$notifications = $stmt->fetchAll();

// Compter par type
$typeCount = [];
foreach ($notifications as $n) {
    $type = $n['type'];
    $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - Logements Tunisie</title>
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

.notifications-container {
    max-width: 800px;
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

.notification-stats {
    background: white;
    padding: 8px 20px;
    border-radius: 40px;
    font-size: 14px;
    color: var(--text-light);
    border: 1px solid var(--gray-border);
}

.notification-stats strong {
    color: var(--airbnb);
    font-size: 18px;
}

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

/* Liste des notifications */
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-item {
    background: white;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.2s;
    border: 1px solid var(--gray-border);
    position: relative;
    overflow: hidden;
}

.notification-item.unread {
    border-left: 4px solid var(--airbnb);
    background: linear-gradient(90deg, rgba(255,90,95,0.02) 0%, white 5%);
}

.notification-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.notification-content {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.notification-icon.reservation {
    background: #eff6ff;
    color: #3b82f6;
}

.notification-icon.demande {
    background: #fffbeb;
    color: #f59e0b;
}

.notification-icon.systeme {
    background: #f3e8ff;
    color: #9333ea;
}

.notification-icon.paiement {
    background: #d1fae5;
    color: #10b981;
}

.notification-details {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.notification-title {
    font-weight: 600;
    font-size: 16px;
    color: var(--text-dark);
}

.notification-date {
    font-size: 12px;
    color: var(--text-light);
}

.notification-message {
    color: var(--text-light);
    font-size: 14px;
    line-height: 1.5;
}

.notification-actions {
    margin-top: 12px;
    display: flex;
    gap: 12px;
}

.btn-link {
    background: none;
    border: none;
    color: var(--airbnb);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-link:hover {
    background: rgba(255,90,95,0.1);
}

/* Filtres */
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

/* Responsive */
@media (max-width: 640px) {
    .notifications-container {
        padding: 24px 16px;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .notification-content {
        gap: 12px;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    
    .notification-header {
        flex-direction: column;
        gap: 4px;
    }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="notifications-container">
    <div class="page-header">
        <div>
            <h1><i class="fa-regular fa-bell"></i> Notifications</h1>
            <p>Restez informé de vos activités</p>
        </div>
        <div class="notification-stats">
            📬 <strong><?php echo count($notifications); ?></strong> notification<?php echo count($notifications) > 1 ? 's' : ''; ?>
        </div>
    </div>
    
    <?php if(!empty($notifications)): ?>
    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">Toutes</button>
        <button class="filter-btn" data-filter="reservation">Réservations</button>
        <button class="filter-btn" data-filter="demande">Demandes</button>
        <button class="filter-btn" data-filter="systeme">Système</button>
        <button class="filter-btn" data-filter="paiement">Paiements</button>
    </div>
    <?php endif; ?>
    
    <?php if(empty($notifications)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-bell-slash"></i>
            <h3>Aucune notification</h3>
            <p>Vous serez notifié lorsqu'il y aura des mises à jour concernant vos réservations ou demandes.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list" id="notificationsList">
            <?php foreach($notifications as $n): ?>
            <div class="notification-item <?php echo $n['lu'] ? '' : 'unread'; ?>" data-type="<?php echo $n['type']; ?>">
                <div class="notification-content">
                    <div class="notification-icon <?php echo $n['type']; ?>">
                        <i class="fa-solid fa-<?php 
                            echo $n['type'] == 'reservation' ? 'calendar-check' : 
                                 ($n['type'] == 'demande' ? 'file-lines' : 
                                 ($n['type'] == 'paiement' ? 'credit-card' : 'bell'));
                        ?>"></i>
                    </div>
                    <div class="notification-details">
                        <div class="notification-header">
                            <div class="notification-title"><?php echo htmlspecialchars($n['titre']); ?></div>
                            <div class="notification-date">
                                <?php 
                                    $date = new DateTime($n['date_creation']);
                                    $now = new DateTime();
                                    $diff = $now->diff($date);
                                    
                                    if($diff->days == 0) {
                                        echo "Aujourd'hui à " . $date->format('H:i');
                                    } elseif($diff->days == 1) {
                                        echo "Hier à " . $date->format('H:i');
                                    } else {
                                        echo $date->format('d/m/Y H:i');
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($n['message'])); ?>
                        </div>
                        <?php if(!$n['lu']): ?>
                        <div class="notification-actions">
                            <button class="btn-link" onclick="markAsRead(<?php echo $n['id_notification']; ?>, this)">
                                <i class="fa-regular fa-check-circle"></i> Marquer comme lu
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Filtrage des notifications
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const items = document.querySelectorAll('.notification-item');
        
        items.forEach(item => {
            if(filter === 'all' || item.dataset.type === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Marquer comme lu (simulation - à connecter à une route AJAX)
function markAsRead(id, element) {
    // Ici vous pouvez faire un appel AJAX pour marquer la notification comme lue
    const notification = element.closest('.notification-item');
    notification.classList.remove('unread');
    element.parentElement.remove();
    
    // Optionnel: appel fetch
    fetch('ajax/mark-notification-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(err => console.log(err));
}
</script>

<?php require_once 'includes/footer.php'; ?>