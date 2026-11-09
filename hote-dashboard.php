<?php
// hote-dashboard.php - Tableau de bord pour les propriétaires
// À placer à la racine du projet
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour accéder à votre tableau de bord');
    exit();
}

// Récupérer l'ID hôte
$id_client = $_SESSION['user_id'];
$sql = "SELECT id_hote, statut_hote FROM hote WHERE id_client = :id_client";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$hote = $stmt->fetch();

// Si l'utilisateur n'est pas encore hôte
if (!$hote) {
    header('Location: index.php?error=Accès réservé aux propriétaires');
    exit();
}

if ($id_client == 1) {
    header('Location: admin-dashboard.php');
    exit();
}

$id_hote = $hote['id_hote'];
$statut_hote = $hote['statut_hote'];

// Traitement des actions
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Changer le statut d'un logement (activer/désactiver)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id_logement = (int)($_POST['id_logement'] ?? 0);
    $nouveau_statut = $_POST['nouveau_statut'] ?? '';
    
    $sql = "UPDATE logement SET statut = :statut WHERE id_logement = :id_logement AND id_hote = :id_hote";
    Database::execute($sql, [
        ':statut' => $nouveau_statut,
        ':id_logement' => $id_logement,
        ':id_hote' => $id_hote
    ]);
    $success = "Statut du logement mis à jour";
}

// Supprimer un logement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logement'])) {
    $id_logement = (int)($_POST['id_logement'] ?? 0);
    
    // Vérifier si le logement a des réservations
    $sql = "SELECT COUNT(*) as count FROM reservation WHERE id_logement = :id_logement";
    $stmt = Database::execute($sql, [':id_logement' => $id_logement]);
    $reservations = $stmt->fetch();
    
    if ($reservations['count'] > 0) {
        $error = "Impossible de supprimer ce logement car il a des réservations. Vous pouvez le désactiver à la place.";
    } else {
        $sql = "DELETE FROM logement WHERE id_logement = :id_logement AND id_hote = :id_hote";
        Database::execute($sql, [
            ':id_logement' => $id_logement,
            ':id_hote' => $id_hote
        ]);
        $success = "Logement supprimé avec succès";
    }
}

// Récupérer les statistiques
// Nombre total de logements
$sql = "SELECT COUNT(*) as total FROM logement WHERE id_hote = :id_hote";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$total_logements = $stmt->fetch()['total'];

// Logements actifs
$sql = "SELECT COUNT(*) as total FROM logement WHERE id_hote = :id_hote AND statut IN ('valide', 'actif')";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$logements_actifs = $stmt->fetch()['total'];

// Logements en attente
$sql = "SELECT COUNT(*) as total FROM logement WHERE id_hote = :id_hote AND statut IN ('en_revision', 'en_attente')";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$logements_attente = $stmt->fetch()['total'];

// Nombre total de réservations
$sql = "SELECT COUNT(*) as total FROM reservation r 
        JOIN logement l ON r.id_logement = l.id_logement 
        WHERE l.id_hote = :id_hote";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$total_reservations = $stmt->fetch()['total'];

// Réservations en attente
$sql = "SELECT COUNT(*) as total FROM reservation r 
        JOIN logement l ON r.id_logement = l.id_logement 
        WHERE l.id_hote = :id_hote AND r.statut = 'en_attente'";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$reservations_en_attente = $stmt->fetch()['total'];

// Chiffre d'affaires total
$sql = "SELECT SUM(r.montant_total) as total FROM reservation r 
        JOIN logement l ON r.id_logement = l.id_logement 
        WHERE l.id_hote = :id_hote AND r.statut = 'confirmee'";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$ca_total = $stmt->fetch()['total'] ?? 0;

// Récupérer les logements de l'hôte
$sql = "SELECT l.*, 
        (SELECT COUNT(*) FROM reservation WHERE id_logement = l.id_logement) as nb_reservations,
        (SELECT SUM(montant_total) FROM reservation WHERE id_logement = l.id_logement AND statut = 'confirmee') as revenus
        FROM logement l 
        WHERE l.id_hote = :id_hote 
        ORDER BY l.date_creation DESC";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$logements = $stmt->fetchAll();

// Récupérer les réservations en attente (pour la section dédiée)
$sql = "SELECT r.*, l.titre, l.region_ville, l.prix_nuit,
        CONCAT(c.prenom, ' ', c.nom) as client_nom,
        c.email as client_email,
        c.telephone as client_telephone
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN utilisateur c ON r.id_client = c.id_client
        WHERE l.id_hote = :id_hote AND r.statut = 'en_attente'
        ORDER BY r.date_creation DESC";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$pending_reservations = $stmt->fetchAll();

// Récupérer les réservations récentes (toutes)
$sql = "SELECT r.*, l.titre, l.region_ville,
        CONCAT(c.prenom, ' ', c.nom) as client_nom,
        c.email as client_email,
        c.telephone as client_telephone
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN utilisateur c ON r.id_client = c.id_client
        WHERE l.id_hote = :id_hote
        ORDER BY r.date_creation DESC
        LIMIT 10";
$stmt = Database::execute($sql, [':id_hote' => $id_hote]);
$reservations_recentes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord Propriétaire — Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* ═══════════════════════════════════════════════════
   hote-dashboard.php — Design unifié avec index.php
═══════════════════════════════════════════════════ */
:root {
    --airbnb:       #FF385C;
    --airbnb-dark:  #E31C5F;
    --airbnb-light: #FF7096;
    --black:        #222222;
    --gray-dark:    #717171;
    --gray:         #DDDDDD;
    --gray-light:   #F7F7F7;
    --white:        #FFFFFF;
    --shadow-sm:    0 2px 8px rgba(0,0,0,0.08);
    --shadow-md:    0 6px 16px rgba(0,0,0,0.12);
    --shadow-lg:    0 12px 28px rgba(0,0,0,0.15);
    --radius-sm:    12px;
    --radius-md:    16px;
    --radius-lg:    24px;
    --transition:   all 0.25s ease;
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Inter', sans-serif;
    color: var(--black);
    background: var(--gray-light);
}

/* ── Topbar (même look que la nav de index.php) ── */
.owner-topbar {
    background: var(--white);
    border-bottom: 1px solid var(--gray);
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: var(--shadow-sm);
}
.owner-topbar-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80px;
    gap: 16px;
}
.owner-brand {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    color: var(--airbnb);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.owner-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}
.owner-link {
    padding: 10px 16px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    color: var(--black);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
    white-space: nowrap;
}
.owner-link:hover { background: var(--gray-light); }
.owner-link.owner-link-current {
    color: var(--airbnb);
    font-weight: 600;
    background: rgba(255, 56, 92, 0.1);
}
.owner-link-primary {
    background: var(--airbnb);
    color: var(--white) !important;
    border-radius: 24px;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.owner-link-primary:hover {
    background: var(--airbnb-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,56,92,0.35);
}

/* ── Hero banner propriétaire ── */
.owner-hero {
    background: linear-gradient(135deg, #fff5f7 0%, #ffe0e8 60%, #fff8f0 100%);
    border-bottom: 1px solid #fce7ec;
    padding: 40px 0 32px;
}
.owner-hero-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.owner-hero-text h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 700;
    color: var(--black);
    margin-bottom: 6px;
}
.owner-hero-text h1 i { color: var(--airbnb); margin-right: 10px; }
.owner-hero-text p { color: var(--gray-dark); font-size: 15px; }
.owner-hero-actions { display: flex; gap: 10px; }

/* ── Container principal ── */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 36px 40px;
}

/* ── Alertes ── */
.alert {
    padding: 14px 18px;
    border-radius: var(--radius-sm);
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid transparent;
}
.alert.success { background:#d1fae5; color:#065f46; border-color:#a7f3d0; }
.alert.error   { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
.alert.warning { background:#fffbeb; color:#d97706; border-color:#fde68a; }
.alert i { font-size:16px; flex-shrink:0; }

/* ── Stats grid ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
}
.stat-card {
    background: var(--white);
    border-radius: var(--radius-md);
    padding: 22px 20px;
    border: 1px solid var(--gray);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: rgba(255,56,92,0.2);
}
.stat-card.urgent {
    border: 2px solid #F59E0B;
    background: #FFFBEB;
}
.stat-icon {
    width: 44px; height: 44px;
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.stat-icon.blue   { background:#EFF6FF; color:#3B82F6; }
.stat-icon.green  { background:#ECFDF5; color:#10B981; }
.stat-icon.orange { background:#FFFBEB; color:#F59E0B; }
.stat-icon.purple { background:#F5F3FF; color:#8B5CF6; }
.stat-icon.red    { background:#FFF1F2; color:var(--airbnb); }
.stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--black);
    line-height: 1;
}
.stat-label {
    font-size: 13px;
    color: var(--gray-dark);
    font-weight: 500;
}

/* ── Section titles ── */
.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--black);
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 2px solid var(--gray);
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title i { color: var(--airbnb); }

/* ── Bouton ajouter ── */
.btn-add {
    background: var(--airbnb);
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    padding: 9px 18px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.btn-add:hover {
    background: var(--airbnb-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,56,92,0.25);
}

/* ── Tables ── */
.table-container {
    background: var(--white);
    border-radius: var(--radius-md);
    border: 1px solid var(--gray);
    overflow: hidden;
    margin-bottom: 36px;
    box-shadow: var(--shadow-sm);
}
table { width:100%; border-collapse:collapse; }
thead th {
    background: var(--gray-light);
    padding: 13px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-dark);
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 1px solid var(--gray);
}
tbody td {
    padding: 14px 16px;
    font-size: 14px;
    color: var(--black);
    border-bottom: 1px solid var(--gray);
    vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: var(--gray-light); }
tbody td small  { color: var(--gray-dark); font-size: 12px; }
tbody td strong { font-weight: 600; }

/* ── Badges statut ── */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.status-badge.valide,
.status-badge.actif       { background:#D1FAE5; color:#065F46; }
.status-badge.en_attente,
.status-badge.en_revision { background:#FEF9C3; color:#92400E; }
.status-badge.confirmee   { background:#DBEAFE; color:#1E40AF; }
.status-badge.inactif     { background:var(--gray-light); color:var(--gray-dark); }
.status-badge.refuse,
.status-badge.refusee,
.status-badge.annulee     { background:#FEE2E2; color:#991B1B; }
.status-badge.terminee    { background:#F3F4F6; color:#374151; }

/* ── Boutons action ── */
.btn-action {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--gray);
    background: var(--white);
    color: var(--gray-dark);
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    transition: var(--transition);
    margin-right: 4px;
}
.btn-action:hover        { background:var(--gray-light); color:var(--black); }
.btn-action.edit:hover   { background:#EFF6FF; color:#3B82F6; border-color:#BFDBFE; }
.btn-action.toggle:hover { background:#ECFDF5; color:#10B981; border-color:#A7F3D0; }
.btn-action.delete:hover { background:#FEE2E2; color:#DC2626; border-color:#FECACA; }

/* ── Empty state ── */
.empty-state {
    text-align: center;
    padding: 64px 32px;
    color: var(--gray-dark);
}
.empty-state i  { font-size:52px; color:var(--gray); margin-bottom:16px; display:block; }
.empty-state h3 { font-size:18px; font-weight:600; color:var(--black); margin-bottom:8px; }
.empty-state p  { font-size:14px; }

/* ── Section demandes en attente ── */
.pending-section {
    background: var(--white);
    border: 2px solid #F59E0B;
    border-radius: var(--radius-md);
    padding: 24px;
    margin-bottom: 36px;
    box-shadow: var(--shadow-sm);
}
.pending-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 12px;
    flex-wrap: wrap;
}
.pending-title-wrap {
    display: flex; align-items: center; gap: 12px;
}
.pending-title-wrap h2 {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--black);
}
.pending-badge {
    background: #F59E0B;
    color: var(--white);
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
}
.pending-card {
    background: var(--gray-light);
    border: 1px solid var(--gray);
    border-radius: var(--radius-sm);
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
    transition: var(--transition);
}
.pending-card:last-child { margin-bottom: 0; }
.pending-card:hover { box-shadow: var(--shadow-sm); border-color: #F59E0B; }
.pending-info { flex: 1; }
.pending-info h4 { font-size:15px; font-weight:700; color:var(--black); margin-bottom:6px; }
.pending-logement { font-size:13px; color:var(--gray-dark); margin-bottom:8px; }
.pending-meta {
    display: flex; flex-wrap: wrap; gap: 6px;
}
.pending-meta span {
    background: var(--white);
    border: 1px solid var(--gray);
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 12px;
    color: var(--black);
    display: flex; align-items: center; gap: 4px;
}
.pending-comment {
    margin-top: 10px;
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: #92400E;
    font-style: italic;
}
.pending-actions { display:flex; flex-direction:column; gap:8px; flex-shrink:0; }
.btn-accept, .btn-refuse {
    padding: 9px 18px;
    border-radius: var(--radius-sm);
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: var(--transition);
    white-space: nowrap;
}
.btn-accept       { background:#10B981; color:var(--white); }
.btn-accept:hover { background:#059669; transform:translateY(-1px); box-shadow:0 4px 10px rgba(16,185,129,.25); }
.btn-refuse       { background:#FEE2E2; color:#DC2626; }
.btn-refuse:hover { background:#DC2626; color:var(--white); transform:translateY(-1px); }

/* ── Modals ── */
.modal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(4px);
}
.modal.active { display: flex; }
.modal-content {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 36px;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: var(--shadow-lg);
    animation: modal-pop .2s ease;
}
@keyframes modal-pop {
    from { opacity:0; transform: scale(.95) translateY(10px); }
    to   { opacity:1; transform: none; }
}
.modal-content > i  { font-size:40px; margin-bottom:16px; display:block; }
.modal-content h3   { font-family:'Playfair Display',serif; font-size:22px; font-weight:700; color:var(--black); margin-bottom:10px; }
.modal-content p    { color:var(--gray-dark); font-size:14px; margin-bottom:6px; line-height:1.6; }
.modal-buttons      { display:flex; gap:10px; justify-content:center; margin-top:24px; flex-wrap:wrap; }
.btn-primary, .btn-outline, .btn-danger {
    padding: 11px 24px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid transparent;
    transition: var(--transition);
}
.btn-primary       { background:var(--airbnb); color:var(--white); }
.btn-primary:hover { background:var(--airbnb-dark); transform:translateY(-1px); }
.btn-outline       { background:var(--white); color:var(--black); border-color:var(--gray); }
.btn-outline:hover { border-color:var(--black); }
.btn-danger        { background:#DC2626; color:var(--white); }
.btn-danger:hover  { background:#B91C1C; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .owner-topbar-content { padding: 0 20px; }
    .owner-hero-inner     { padding: 0 20px; }
    .dashboard-container  { padding: 24px 20px; }
    .stats-grid           { grid-template-columns: repeat(2,1fr); }
    .pending-card         { flex-direction: column; }
    .pending-actions      { flex-direction: row; }
}
@media (max-width: 600px) {
    .owner-nav .owner-link span { display: none; }
    .stats-grid  { grid-template-columns: 1fr 1fr; }
    table        { font-size: 13px; }
    thead th, tbody td { padding: 10px 12px; }
    .owner-hero-actions { flex-direction: column; }
}
</style>
</head>
<body>

<header class="owner-topbar">
    <div class="owner-topbar-content">
        <a href="index.php" class="owner-brand">
            <i class="fa-solid fa-house-chimney"></i> Logements
        </a>
        <nav class="owner-nav">
            <a href="index.php" class="owner-link">
                <i class="fa-solid fa-compass"></i>
                <span>Explorer</span>
            </a>
            <a href="mes-reservations-proprietaire.php" class="owner-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Réservations</span>
                <?php if($reservations_en_attente > 0): ?>
                <span style="background:var(--airbnb);color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;font-weight:700;"><?php echo $reservations_en_attente; ?></span>
                <?php endif; ?>
            </a>
            <a href="correspondances.php" class="owner-link">
                <i class="fa-regular fa-message"></i>
                <span>Messages</span>
            </a>
            <a href="notifications.php" class="owner-link">
                <i class="fa-regular fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="profil.php" class="owner-link">
                <i class="fa-solid fa-user"></i>
                <span>Profil</span>
            </a>
            <a href="publier.php" class="owner-link-primary">
                <i class="fa-solid fa-house-chimney"></i>
                <span>Publier</span>
            </a>
            <a href="logout.php" class="owner-link" style="color:var(--gray-dark);">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </nav>
    </div>
</header>

<!-- Hero banner propriétaire -->
<section class="owner-hero">
    <div class="owner-hero-inner">
        <div class="owner-hero-text">
            <h1><i class="fa-solid fa-chart-line"></i> Tableau de bord Propriétaire</h1>
            <p>Bonjour <?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Propriétaire'); ?> 👋 — Gérez vos logements et vos réservations</p>
        </div>
        <div class="owner-hero-actions">
            <a href="publier.php" class="owner-link-primary">
                <i class="fa-solid fa-house-chimney"></i> Publier un logement
            </a>
            <a href="mes-reservations-proprietaire.php" class="owner-link" style="border:1.5px solid var(--gray); background:var(--white);">
                <i class="fa-solid fa-calendar-check"></i> Voir les réservations
            </a>
        </div>
    </div>
</section>

<div class="dashboard-container">
    
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
    
    <?php if($statut_hote == 'en_attente'): ?>
        <div class="alert warning">
            <i class="fa-solid fa-clock"></i> Votre compte propriétaire est en attente de validation par notre équipe. Vous pourrez publier des logements dès qu'il sera approuvé.
        </div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-home"></i>
            </div>
            <div class="stat-value"><?php echo $total_logements; ?></div>
            <div class="stat-label">Total logements</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $logements_actifs; ?></div>
            <div class="stat-label">Logements actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="stat-value"><?php echo $logements_attente; ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?php echo $total_reservations; ?></div>
            <div class="stat-label">Réservations</div>
        </div>
        <div class="stat-card <?php echo $reservations_en_attente > 0 ? 'urgent' : ''; ?>">
            <div class="stat-icon orange">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="stat-value"><?php echo $reservations_en_attente; ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="stat-value"><?php echo number_format($ca_total, 0); ?> TND</div>
            <div class="stat-label">Chiffre d'affaires</div>
        </div>
    </div>
    
    <!-- Mes logements -->
    <div class="section-title">
        <i class="fa-solid fa-building"></i> Mes logements
        <a href="publier.php" class="btn-add" style="margin-left: auto;">
            <i class="fa-solid fa-plus"></i> Ajouter un logement
        </a>

    </div>
    
    <div class="table-container">
        <?php if(empty($logements)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-building"></i>
                <h3>Aucun logement</h3>
                <p>Vous n'avez pas encore publié de logement.</p>
                <a href="publier.php" class="btn-add" style="margin-top: 16px;">Publier mon premier logement</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Ville/Région</th>
                        <th>Prix/nuit</th>
                        <th>Statut</th>
                        <th>Réservations</th>
                        <th>Revenus</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logements as $l): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($l['titre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($l['region_ville']); ?></td>
                        <td><?php echo number_format($l['prix_nuit'], 0); ?> TND</td>
                        <td>
                            <span class="status-badge <?php echo $l['statut']; ?>">
                                <?php 
                                    $statut_text = [
                                        'valide' => '✓ Validé',
                                        'actif' => '✓ Actif',
                                        'en_revision' => '⏳ En révision',
                                        'en_attente' => '⏳ En attente',
                                        'refuse' => '✗ Refusé',
                                        'inactif' => '⭘ Inactif'
                                    ];
                                    echo $statut_text[$l['statut']] ?? $l['statut'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo $l['nb_reservations'] ?? 0; ?></td>
                        <td><?php echo number_format($l['revenus'] ?? 0, 0); ?> TND</td>
                        <td>
                            <a href="modifier-logement.php?id=<?php echo $l['id_logement']; ?>" class="btn-action edit" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <?php if(in_array($l['statut'], ['valide', 'actif'], true)): ?>
                                <button onclick="toggleStatus(<?php echo $l['id_logement']; ?>, 'inactif', '<?php echo addslashes($l['titre']); ?>')" class="btn-action toggle" title="Désactiver">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            <?php elseif($l['statut'] == 'inactif'): ?>
                                <button onclick="toggleStatus(<?php echo $l['id_logement']; ?>, 'actif', '<?php echo addslashes($l['titre']); ?>')" class="btn-action toggle" title="Activer">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            <?php endif; ?>
                            <?php if(($l['nb_reservations'] ?? 0) == 0): ?>
                                <button onclick="showDeleteModal(<?php echo $l['id_logement']; ?>, '<?php echo addslashes($l['titre']); ?>')" class="btn-action delete" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Demandes en attente -->
    <?php if(!empty($pending_reservations)): ?>
    <div class="pending-section">
        <div class="pending-header">
            <div class="pending-title-wrap">
                <i class="fa-solid fa-bell" style="font-size: 24px; color: #f59e0b;"></i>
                <h2>Demandes de réservation en attente</h2>
            </div>
            <span class="pending-badge"><?php echo count($pending_reservations); ?> nouvelle<?php echo count($pending_reservations) > 1 ? 's' : ''; ?></span>
        </div>
        
        <?php foreach($pending_reservations as $pr): 
            $nbNuits = (strtotime($pr['date_fin']) - strtotime($pr['date_deb'])) / (60*60*24);
        ?>
        <div class="pending-card">
            <div class="pending-info">
                <h4><i class="fa-solid fa-user" style="color: var(--airbnb); margin-right: 6px;"></i> <?php echo htmlspecialchars($pr['client_nom']); ?></h4>
                <div class="pending-logement">
                    <i class="fa-solid fa-house"></i> <?php echo htmlspecialchars($pr['titre']); ?> - <?php echo htmlspecialchars($pr['region_ville']); ?>
                </div>
                <div class="pending-meta">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($pr['date_deb'])); ?> → <?php echo date('d/m/Y', strtotime($pr['date_fin'])); ?></span>
                    <span><i class="fa-solid fa-moon"></i> <?php echo $nbNuits; ?> nuits</span>
                    <span><i class="fa-solid fa-users"></i> <?php echo $pr['nb_voyageurs']; ?> voyageur<?php echo $pr['nb_voyageurs'] > 1 ? 's' : ''; ?></span>
                    <span><i class="fa-solid fa-coins"></i> <strong><?php echo number_format($pr['montant_total'], 0); ?> TND</strong></span>
                    <?php if(!empty($pr['client_email'])): ?>
                        <span><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($pr['client_email']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if(!empty($pr['commentaire'])): ?>
                <div class="pending-comment">
                    <i class="fa-regular fa-comment" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($pr['commentaire']); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="pending-actions">
                <button class="btn-accept" onclick="showAcceptModal(<?php echo $pr['id_reservation']; ?>, '<?php echo addslashes($pr['client_nom']); ?>', '<?php echo addslashes($pr['titre']); ?>')">
                    <i class="fa-solid fa-check"></i> Accepter
                </button>
                <button class="btn-refuse" onclick="showRefuseModal(<?php echo $pr['id_reservation']; ?>, '<?php echo addslashes($pr['client_nom']); ?>', '<?php echo addslashes($pr['titre']); ?>')">
                    <i class="fa-solid fa-xmark"></i> Refuser
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Réservations récentes -->
    <div class="section-title">
        <i class="fa-solid fa-clock"></i> Toutes les réservations
    </div>
    
    <div class="table-container">
        <?php if(empty($reservations_recentes)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar"></i>
                <h3>Aucune réservation</h3>
                <p>Vous n'avez pas encore reçu de réservation.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Logement</th>
                        <th>Dates</th>
                        <th>Voyageurs</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations_recentes as $r): 
                        $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($r['client_nom']); ?></strong><br>
                            <small><?php echo htmlspecialchars($r['client_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($r['titre']); ?><br><small><?php echo htmlspecialchars($r['region_ville']); ?></small></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($r['date_deb'])); ?><br>
                            → <?php echo date('d/m/Y', strtotime($r['date_fin'])); ?>
                            <br><small><?php echo $nbNuits; ?> nuits</small>
                        </td>
                        <td><?php echo $r['nb_voyageurs']; ?></td>
                        <td><?php echo number_format($r['montant_total'], 0); ?> TND</td>
                        <td>
                            <span class="status-badge <?php echo $r['statut']; ?>">
                                <?php 
                                    $status_text = [
                                        'confirmee' => '✓ Confirmée',
                                        'en_attente' => '⏳ En attente',
                                        'annulee' => '✗ Annulée',
                                        'refusee' => '✗ Refusée',
                                        'terminee' => '🏁 Terminée'
                                    ];
                                    echo $status_text[$r['statut']] ?? $r['statut'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if($r['statut'] === 'en_attente'): ?>
                                <button onclick="showAcceptModal(<?php echo $r['id_reservation']; ?>, '<?php echo addslashes($r['client_nom']); ?>', '<?php echo addslashes($r['titre']); ?>')" class="btn-action toggle" title="Accepter">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button onclick="showRefuseModal(<?php echo $r['id_reservation']; ?>, '<?php echo addslashes($r['client_nom']); ?>', '<?php echo addslashes($r['titre']); ?>')" class="btn-action delete" title="Refuser">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            <?php endif; ?>
                            <a href="correspondances.php?to=<?php echo urlencode($r['client_email']); ?>" class="btn-action edit" title="Contacter">
                                <i class="fa-regular fa-message"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmation pour toggle status -->
<div id="toggleModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-toggle-on" style="color: var(--airbnb);"></i>
        <h3>Changer le statut</h3>
        <p>Êtes-vous sûr de vouloir <strong id="toggleAction"></strong> le logement <strong id="toggleTitle"></strong> ?</p>
        <form method="POST" id="toggleForm">
            <input type="hidden" name="id_logement" id="toggleId">
            <input type="hidden" name="nouveau_statut" id="toggleNewStatus">
            <div class="modal-buttons">
                <button type="button" class="btn-outline" onclick="closeToggleModal()">Annuler</button>
                <button type="submit" name="toggle_status" class="btn-primary">Confirmer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation pour suppression -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-trash-can" style="color: #dc2626;"></i>
        <h3>Supprimer le logement</h3>
        <p>Êtes-vous sûr de vouloir supprimer définitivement <strong id="deleteTitle"></strong> ?</p>
        <p style="font-size: 14px; color: #dc2626;">Cette action est irréversible.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="id_logement" id="deleteId">
            <div class="modal-buttons">
                <button type="button" class="btn-outline" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" name="delete_logement" class="btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation pour accepter -->
<div id="acceptModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
        <h3>Accepter la réservation</h3>
        <p>Confirmez-vous la réservation de <strong id="acceptClientNom"></strong> pour <strong id="acceptLogementTitre"></strong> ?</p>
        <form method="POST" action="api/update_reservation_statut.php" id="acceptForm">
            <input type="hidden" name="id_reservation" id="acceptReservationId">
            <input type="hidden" name="action" value="accepter">
            <div class="modal-buttons">
                <button type="button" class="btn-outline" onclick="closeAcceptModal()">Annuler</button>
                <button type="submit" class="btn-primary" style="background: #10b981; border: none; color: white; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">✓ Accepter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation pour refuser -->
<div id="refuseModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-circle-xmark" style="color: #dc2626;"></i>
        <h3>Refuser la réservation</h3>
        <p>Êtes-vous sûr de vouloir refuser la réservation de <strong id="refuseClientNom"></strong> pour <strong id="refuseLogementTitre"></strong> ?</p>
        <p style="font-size: 14px; color: #dc2626;">Le client sera notifié du refus.</p>
        <form method="POST" action="api/update_reservation_statut.php" id="refuseForm">
            <input type="hidden" name="id_reservation" id="refuseReservationId">
            <input type="hidden" name="action" value="refuser">
            <div class="modal-buttons">
                <button type="button" class="btn-outline" onclick="closeRefuseModal()">Annuler</button>
                <button type="submit" class="btn-danger" style="background: #dc2626; border: none; color: white; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">✗ Refuser</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleStatus(id, newStatus, title) {
    document.getElementById('toggleId').value = id;
    document.getElementById('toggleNewStatus').value = newStatus;
    document.getElementById('toggleTitle').innerText = title;
    document.getElementById('toggleAction').innerText = newStatus === 'actif' ? 'activer' : 'désactiver';
    document.getElementById('toggleModal').classList.add('active');
}

function closeToggleModal() {
    document.getElementById('toggleModal').classList.remove('active');
}

function showDeleteModal(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTitle').innerText = title;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// Accepter / Refuser modals
function showAcceptModal(id, clientNom, logementTitre) {
    document.getElementById('acceptReservationId').value = id;
    document.getElementById('acceptClientNom').innerText = clientNom;
    document.getElementById('acceptLogementTitre').innerText = logementTitre;
    document.getElementById('acceptModal').classList.add('active');
}

function closeAcceptModal() {
    document.getElementById('acceptModal').classList.remove('active');
}

function showRefuseModal(id, clientNom, logementTitre) {
    document.getElementById('refuseReservationId').value = id;
    document.getElementById('refuseClientNom').innerText = clientNom;
    document.getElementById('refuseLogementTitre').innerText = logementTitre;
    document.getElementById('refuseModal').classList.add('active');
}

function closeRefuseModal() {
    document.getElementById('refuseModal').classList.remove('active');
}

// Fermer les modals en cliquant en dehors
document.getElementById('toggleModal').addEventListener('click', function(e) {
    if (e.target === this) closeToggleModal();
});
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.getElementById('acceptModal').addEventListener('click', function(e) {
    if (e.target === this) closeAcceptModal();
});
document.getElementById('refuseModal').addEventListener('click', function(e) {
    if (e.target === this) closeRefuseModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>
</html>