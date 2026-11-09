<?php
// admin-dashboard.php - Tableau de bord administrateur avec style moderne
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est admin (id_client = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header('Location: index.php?error=Accès non autorisé - Zone administrateur');
    exit();
}

$active_tab = $_GET['tab'] ?? 'dashboard';
$success = '';
$error = '';

// ==================== TRAITEMENTS DES ACTIONS ====================

// Valider un logement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_logement'])) {
    $id_logement = $_POST['id_logement'] ?? 0;
    $action = $_POST['action_type'] ?? 'valider';
    $statut = ($action == 'valider') ? 'valide' : 'refuse';
    
    $sql = "UPDATE logement SET statut = :statut WHERE id_logement = :id_logement";
    Database::execute($sql, [':statut' => $statut, ':id_logement' => $id_logement]);
    $success = "Logement " . ($action == 'valider' ? "validé" : "refusé") . " avec succès";
}

// Valider un hôte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_hote'])) {
    $id_hote = $_POST['id_hote'] ?? 0;
    $action = $_POST['action_type'] ?? 'valider';
    $statut = ($action == 'valider') ? 'valide' : 'refuse';
    
    $sql = "UPDATE hote SET statut_hote = :statut WHERE id_hote = :id_hote";
    Database::execute($sql, [':statut' => $statut, ':id_hote' => $id_hote]);
    $success = "Hôte " . ($action == 'valider' ? "validé" : "refusé") . " avec succès";
}

// Supprimer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id_client = $_POST['id_client'] ?? 0;
    
    if ($id_client != 1) {
        $sql = "DELETE FROM reservation WHERE id_client = :id_client";
        Database::execute($sql, [':id_client' => $id_client]);
        $sql = "DELETE FROM notification WHERE id_client = :id_client";
        Database::execute($sql, [':id_client' => $id_client]);
        $sql = "DELETE FROM utilisateur WHERE id_client = :id_client";
        Database::execute($sql, [':id_client' => $id_client]);
        $success = "Utilisateur supprimé avec succès";
    } else {
        $error = "Impossible de supprimer le compte administrateur";
    }
}

// Supprimer un logement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logement'])) {
    $id_logement = $_POST['id_logement'] ?? 0;
    $sql = "DELETE FROM reservation WHERE id_logement = :id_logement";
    Database::execute($sql, [':id_logement' => $id_logement]);
    $sql = "DELETE FROM logement WHERE id_logement = :id_logement";
    Database::execute($sql, [':id_logement' => $id_logement]);
    $success = "Logement supprimé avec succès";
}

// Envoyer une notification globale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $titre = $_POST['titre'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'systeme';
    
    if (!empty($titre) && !empty($message)) {
        $sql = "INSERT INTO notification (id_client, titre, message, type) 
                 SELECT id_client, :titre, :message, :type FROM utilisateur";
        Database::execute($sql, [':titre' => $titre, ':message' => $message, ':type' => $type]);
        $success = "Notification envoyée à tous les utilisateurs";
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}

// ==================== RÉCUPÉRATION DES DONNÉES ====================

// Statistiques globales
$total_clients = Database::execute("SELECT COUNT(*) as total FROM utilisateur")->fetch()['total'];
$total_hotes = Database::execute("SELECT COUNT(*) as total FROM hote")->fetch()['total'];
$total_logements = Database::execute("SELECT COUNT(*) as total FROM logement")->fetch()['total'];
$logements_attente = Database::execute("SELECT COUNT(*) as total FROM logement WHERE statut = 'en_revision'")->fetch()['total'];
$hotes_attente = Database::execute("SELECT COUNT(*) as total FROM hote WHERE statut_hote = 'en_attente'")->fetch()['total'];
$total_reservations = Database::execute("SELECT COUNT(*) as total FROM reservation")->fetch()['total'];
$ca_total = Database::execute("SELECT SUM(montant_total) as total FROM reservation WHERE statut = 'confirmee'")->fetch()['total'] ?? 0;

// Logements en attente
$logements_validation = Database::execute("
    SELECT l.*, CONCAT(c.prenom, ' ', c.nom) as hote_nom, c.email as hote_email
    FROM logement l
    JOIN hote h ON l.id_hote = h.id_hote
    JOIN utilisateur c ON h.id_client = c.id_client
    WHERE l.statut = 'en_revision'
    ORDER BY l.date_creation DESC
")->fetchAll();

// Hôtes en attente
$hotes_validation = Database::execute("
    SELECT h.*, c.prenom, c.nom, c.email, c.telephone, c.ville, c.date_inscription
    FROM hote h
    JOIN utilisateur c ON h.id_client = c.id_client
    WHERE h.statut_hote = 'en_attente'
    ORDER BY c.date_inscription DESC
")->fetchAll();

// Tous les utilisateurs
$utilisateurs = Database::execute("
    SELECT c.*, 
        CASE WHEN h.id_hote IS NOT NULL THEN 'proprietaire' ELSE 'client' END as role,
        h.statut_hote as hote_statut
    FROM utilisateur c
    LEFT JOIN hote h ON c.id_client = h.id_client
    ORDER BY c.date_inscription DESC
")->fetchAll();

// Tous les logements
$tous_logements = Database::execute("
    SELECT l.*, CONCAT(c.prenom, ' ', c.nom) as hote_nom
    FROM logement l
    JOIN hote h ON l.id_hote = h.id_hote
    JOIN utilisateur c ON h.id_client = c.id_client
    ORDER BY l.date_creation DESC
")->fetchAll();

// Toutes les réservations
$reservations = Database::execute("
    SELECT r.*, l.titre as logement_titre, l.region_ville,
        CONCAT(c.prenom, ' ', c.nom) as client_nom, c.email as client_email,
        CONCAT(hc.prenom, ' ', hc.nom) as hote_nom
    FROM reservation r
    JOIN logement l ON r.id_logement = l.id_logement
    JOIN utilisateur c ON r.id_client = c.id_client
    JOIN hote h ON l.id_hote = h.id_hote
    JOIN utilisateur hc ON h.id_client = hc.id_client
    ORDER BY r.date_creation DESC
    LIMIT 50
")->fetchAll();

// Notifications récentes
$notifications = Database::execute("
    SELECT n.*, CONCAT(c.prenom, ' ', c.nom) as client_nom
    FROM notification n
    JOIN utilisateur c ON n.id_client = c.id_client
    ORDER BY n.date_creation DESC
    LIMIT 20
")->fetchAll();

// Tous les hôtes
$all_hotes = Database::execute("
    SELECT h.*, c.prenom, c.nom, c.email, c.telephone, c.ville,
        (SELECT COUNT(*) FROM logement WHERE id_hote = h.id_hote) as nb_logements
    FROM hote h 
    JOIN utilisateur c ON h.id_client = c.id_client
    ORDER BY c.date_inscription DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════════════
   admin-dashboard.php — Design unifié avec hote-dashboard.php
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

/* ── Topbar Admin ── */
.admin-topbar {
    background: var(--white);
    border-bottom: 1px solid var(--gray);
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: var(--shadow-sm);
}
.admin-topbar-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80px;
    gap: 16px;
}
.admin-brand {
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
.admin-brand i { color: #FFD700; }
.admin-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}
.admin-link {
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
.admin-link:hover { background: var(--gray-light); }
.admin-link.active {
    background: var(--airbnb);
    color: var(--white);
}
.admin-link-primary {
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
.admin-link-primary:hover {
    background: var(--airbnb-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,56,92,0.35);
}

/* ── Hero banner Admin ── */
.admin-hero {
    background: linear-gradient(135deg, #fff5f7 0%, #ffe0e8 60%, #fff8f0 100%);
    border-bottom: 1px solid #fce7ec;
    padding: 40px 0 32px;
}
.admin-hero-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.admin-hero-text h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 700;
    color: var(--black);
    margin-bottom: 6px;
}
.admin-hero-text h1 i { color: #FFD700; margin-right: 10px; }
.admin-hero-text p { color: var(--gray-dark); font-size: 15px; }
.admin-hero-actions { display: flex; gap: 10px; }

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
.stat-icon.cyan   { background:#ECFEFF; color:#06B6D4; }
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
.section-actions { margin-left: auto; display: flex; gap: 8px; }

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
.status-badge.actif,
.status-badge.confirmee { background:#D1FAE5; color:#065F46; }
.status-badge.en_attente,
.status-badge.en_revision { background:#FEF9C3; color:#92400E; }
.status-badge.refuse,
.status-badge.refusee,
.status-badge.annulee     { background:#FEE2E2; color:#991B1B; }
.status-badge.inactif     { background:var(--gray-light); color:var(--gray-dark); }
.status-badge.terminee    { background:#F3F4F6; color:#374151; }
.status-badge.proprietaire { background:#DBEAFE; color:#1E40AF; }
.status-badge.client      { background:#F3F4F6; color:#374151; }

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
.btn-action.validate:hover { background:#D1FAE5; color:#065F46; border-color:#86EFAC; }
.btn-action.refuse:hover   { background:#FEE2E2; color:#DC2626; border-color:#FECACA; }
.btn-action.delete:hover   { background:#FEE2E2; color:#DC2626; border-color:#FECACA; }
.btn-action.view:hover     { background:#EFF6FF; color:#3B82F6; border-color:#BFDBFE; }

/* ── Formulaire notification ── */
.notif-form {
    background: var(--white);
    border-radius: var(--radius-md);
    padding: 24px;
    border: 1px solid var(--gray);
    margin-bottom: 36px;
    box-shadow: var(--shadow-sm);
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.form-group { margin-bottom: 16px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { 
    display: block; 
    font-weight: 600; 
    margin-bottom: 8px; 
    font-size: 13px;
    color: var(--black);
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray);
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: var(--transition);
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--airbnb);
    box-shadow: 0 0 0 3px rgba(255,56,92,0.1);
}
.btn-primary {
    background: var(--airbnb);
    color: var(--white);
    border: none;
    padding: 12px 24px;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
}
.btn-primary:hover {
    background: var(--airbnb-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,56,92,0.25);
}
.btn-sm {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    border: 1px solid var(--gray);
    background: var(--white);
    cursor: pointer;
    text-decoration: none;
    color: var(--black);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.btn-sm:hover { border-color: var(--black); background: var(--gray-light); }

/* ── Empty state ── */
.empty-state {
    text-align: center;
    padding: 64px 32px;
    color: var(--gray-dark);
}
.empty-state i  { font-size:52px; color:var(--gray); margin-bottom:16px; display:block; }
.empty-state h3 { font-size:18px; font-weight:600; color:var(--black); margin-bottom:8px; }
.empty-state p  { font-size:14px; }

/* ── Section validation en attente ── */
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
.pending-meta { font-size:13px; color:var(--gray-dark); margin-bottom:8px; }
.pending-actions { display:flex; gap:8px; flex-shrink:0; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .admin-topbar-content { padding: 0 20px; }
    .admin-hero-inner     { padding: 0 20px; }
    .dashboard-container  { padding: 24px 20px; }
    .stats-grid           { grid-template-columns: repeat(2,1fr); }
    .form-grid            { grid-template-columns: 1fr; }
    .pending-card         { flex-direction: column; }
    .pending-actions      { flex-direction: row; width: 100%; }
    .pending-actions form { flex: 1; }
    .pending-actions button { width: 100%; }
}
@media (max-width: 600px) {
    .admin-nav .admin-link span { display: none; }
    .stats-grid  { grid-template-columns: 1fr 1fr; }
    table        { font-size: 13px; }
    thead th, tbody td { padding: 10px 12px; }
    .admin-hero-actions { flex-direction: column; }
    .section-actions { flex-direction: column; width: 100%; }
    .section-actions .btn-sm { width: 100%; justify-content: center; }
}
</style>
</head>
<body>

<header class="admin-topbar">
    <div class="admin-topbar-content">
        <a href="index.php" class="admin-brand">
            <i class="fa-solid fa-crown"></i> Admin
        </a>
        <nav class="admin-nav">
            <a href="?tab=dashboard" class="admin-link <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="?tab=logements" class="admin-link <?php echo $active_tab == 'logements' ? 'active' : ''; ?>">
                <i class="fa-solid fa-building"></i>
                <span>Logements</span>
            </a>
            <a href="?tab=hotes" class="admin-link <?php echo $active_tab == 'hotes' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-tie"></i>
                <span>Propriétaires</span>
            </a>
            <a href="?tab=utilisateurs" class="admin-link <?php echo $active_tab == 'utilisateurs' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Utilisateurs</span>
            </a>
            <a href="?tab=reservations" class="admin-link <?php echo $active_tab == 'reservations' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Réservations</span>
            </a>
            <a href="?tab=notifications" class="admin-link <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="index.php" class="admin-link" style="color:var(--gray-dark);">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour</span>
            </a>
            <a href="logout.php" class="admin-link" style="color:var(--gray-dark);">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </nav>
    </div>
</header>

<!-- Hero banner Admin -->
<section class="admin-hero">
    <div class="admin-hero-inner">
        <div class="admin-hero-text">
            <h1><i class="fa-solid fa-gauge-high"></i> Administration</h1>
            <p>Bienvenue <?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Administrateur'); ?> — Gérez la plateforme</p>
        </div>
        <div class="admin-hero-actions">
            <a href="index.php" class="btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Retour au site
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
    
    <!-- DASHBOARD -->
    <?php if($active_tab == 'dashboard'): ?>
    
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $total_clients; ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="stat-value"><?php echo $total_hotes; ?></div>
            <div class="stat-label">Hôtes</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">
                <i class="fa-solid fa-home"></i>
            </div>
            <div class="stat-value"><?php echo $total_logements; ?></div>
            <div class="stat-label">Logements</div>
        </div>
        <div class="stat-card <?php echo $logements_attente > 0 ? 'urgent' : ''; ?>">
            <div class="stat-icon orange">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="stat-value"><?php echo $logements_attente; ?></div>
            <div class="stat-label">En validation</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?php echo $total_reservations; ?></div>
            <div class="stat-label">Réservations</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="stat-value"><?php echo number_format($ca_total, 0); ?> TND</div>
            <div class="stat-label">CA total</div>
        </div>
    </div>
    
    <!-- Logements en attente -->
    <?php if(!empty($logements_validation)): ?>
    <div class="pending-section">
        <div class="pending-header">
            <div class="pending-title-wrap">
                <i class="fa-solid fa-clock" style="font-size: 24px; color: #f59e0b;"></i>
                <h2>Logements en attente de validation</h2>
            </div>
            <span class="pending-badge"><?php echo count($logements_validation); ?> en attente</span>
        </div>
        
        <?php foreach($logements_validation as $l): ?>
        <div class="pending-card">
            <div class="pending-info">
                <h4><i class="fa-solid fa-house" style="color: var(--airbnb); margin-right: 6px;"></i> <?php echo htmlspecialchars($l['titre']); ?></h4>
                <div class="pending-meta">
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($l['hote_nom']); ?> • 
                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($l['region_ville']); ?> • 
                    <i class="fa-solid fa-tag"></i> <?php echo number_format($l['prix_nuit'], 0); ?> TND/nuit
                </div>
            </div>
            <div class="pending-actions">
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="id_logement" value="<?php echo $l['id_logement']; ?>">
                    <input type="hidden" name="action_type" value="valider">
                    <button type="submit" name="validate_logement" class="btn-action validate" title="Valider">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </form>
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="id_logement" value="<?php echo $l['id_logement']; ?>">
                    <input type="hidden" name="action_type" value="refuser">
                    <button type="submit" name="validate_logement" class="btn-action refuse" title="Refuser">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </form>
                <a href="logement-detail.php?id=<?php echo $l['id_logement']; ?>" class="btn-action view" title="Voir" target="_blank">
                    <i class="fa-solid fa-eye"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Hôtes en attente -->
    <?php if(!empty($hotes_validation)): ?>
    <div class="pending-section" style="border-color: #8B5CF6;">
        <div class="pending-header">
            <div class="pending-title-wrap">
                <i class="fa-solid fa-user-clock" style="font-size: 24px; color: #8b5cf6;"></i>
                <h2>Hôtes en attente de validation</h2>
            </div>
            <span class="pending-badge" style="background: #8B5CF6;"><?php echo count($hotes_validation); ?> en attente</span>
        </div>
        
        <?php foreach($hotes_validation as $h): ?>
        <div class="pending-card">
            <div class="pending-info">
                <h4><i class="fa-solid fa-user-tie" style="color: #8B5CF6; margin-right: 6px;"></i> <?php echo htmlspecialchars($h['prenom'] . ' ' . $h['nom']); ?></h4>
                <div class="pending-meta">
                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($h['email']); ?> • 
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($h['telephone'] ?? '-'); ?> • 
                    <i class="fa-solid fa-city"></i> <?php echo htmlspecialchars($h['ville'] ?? '-'); ?>
                </div>
            </div>
            <div class="pending-actions">
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="id_hote" value="<?php echo $h['id_hote']; ?>">
                    <input type="hidden" name="action_type" value="valider">
                    <button type="submit" name="validate_hote" class="btn-action validate" title="Valider">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </form>
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="id_hote" value="<?php echo $h['id_hote']; ?>">
                    <input type="hidden" name="action_type" value="refuser">
                    <button type="submit" name="validate_hote" class="btn-action refuse" title="Refuser">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Aperçu rapide -->
    <div class="section-title">
        <i class="fa-solid fa-list"></i> Aperçu rapide
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Métrique</th>
                    <th>Valeur</th>
                    <th>Action rapide</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Total utilisateurs</strong></td>
                    <td><?php echo $total_clients; ?></td>
                    <td><a href="?tab=utilisateurs" class="btn-sm">Voir tous <i class="fa-solid fa-arrow-right"></i></a></td>
                </tr>
                <tr>
                    <td><strong>Total hôtes</strong></td>
                    <td><?php echo $total_hotes; ?></td>
                    <td><a href="?tab=hotes" class="btn-sm">Voir tous <i class="fa-solid fa-arrow-right"></i></a></td>
                </tr>
                <tr>
                    <td><strong>Total logements</strong></td>
                    <td><?php echo $total_logements; ?></td>
                    <td><a href="?tab=logements" class="btn-sm">Voir tous <i class="fa-solid fa-arrow-right"></i></a></td>
                </tr>
                <tr>
                    <td><strong>Réservations totales</strong></td>
                    <td><?php echo $total_reservations; ?></td>
                    <td><a href="?tab=reservations" class="btn-sm">Voir toutes <i class="fa-solid fa-arrow-right"></i></a></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php endif; ?>
    
    <!-- LOGEMENTS -->
    <?php if($active_tab == 'logements'): ?>
    <div class="section-title">
        <i class="fa-solid fa-building"></i> Tous les logements
    </div>
    
    <div class="table-container">
        <?php if(empty($tous_logements)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-building"></i>
                <h3>Aucun logement</h3>
                <p>Aucun logement n'a été publié sur la plateforme.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Hôte</th>
                        <th>Ville</th>
                        <th>Prix/nuit</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tous_logements as $l): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($l['titre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($l['hote_nom']); ?></td>
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
                        <td>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Supprimer ce logement ?')">
                                <input type="hidden" name="id_logement" value="<?php echo $l['id_logement']; ?>">
                                <button type="submit" name="delete_logement" class="btn-action delete" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <a href="logement-detail.php?id=<?php echo $l['id_logement']; ?>" class="btn-action view" title="Voir" target="_blank">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- HÔTES -->
    <?php if($active_tab == 'hotes'): ?>
    <div class="section-title">
        <i class="fa-solid fa-user-tie"></i> Tous les hôtes
    </div>
    
    <div class="table-container">
        <?php if(empty($all_hotes)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-user"></i>
                <h3>Aucun hôte</h3>
                <p>Aucun propriétaire n'est inscrit sur la plateforme.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th>Statut</th>
                        <th>Logements</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_hotes as $h): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($h['prenom'] . ' ' . $h['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($h['email']); ?></td>
                        <td><?php echo htmlspecialchars($h['ville'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $h['statut_hote']; ?>">
                                <?php 
                                    $hote_statut_text = [
                                        'valide' => '✓ Validé',
                                        'en_attente' => '⏳ En attente',
                                        'refuse' => '✗ Refusé'
                                    ];
                                    echo $hote_statut_text[$h['statut_hote']] ?? $h['statut_hote'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo $h['nb_logements']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- UTILISATEURS -->
    <?php if($active_tab == 'utilisateurs'): ?>
    <div class="section-title">
        <i class="fa-solid fa-users"></i> Tous les utilisateurs
    </div>
    
    <div class="table-container">
        <?php if(empty($utilisateurs)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-users"></i>
                <h3>Aucun utilisateur</h3>
                <p>Aucun utilisateur n'est inscrit sur la plateforme.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th>Rôle</th>
                        <th>Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($utilisateurs as $u): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['ville'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $u['role']; ?>">
                                <?php echo $u['role'] == 'proprietaire' ? '🏠 Propriétaire' : '👤 Client'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['date_inscription'])); ?></td>
                        <td>
                            <?php if($u['id_client'] != 1): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Supprimer cet utilisateur ? Cette action est irréversible.')">
                                <input type="hidden" name="id_client" value="<?php echo $u['id_client']; ?>">
                                <button type="submit" name="delete_user" class="btn-action delete" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="color: var(--gray); font-size: 12px;">Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- RÉSERVATIONS -->
    <?php if($active_tab == 'reservations'): ?>
    <div class="section-title">
        <i class="fa-solid fa-calendar-check"></i> Toutes les réservations
    </div>
    
    <div class="table-container">
        <?php if(empty($reservations)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar"></i>
                <h3>Aucune réservation</h3>
                <p>Aucune réservation n'a été effectuée sur la plateforme.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Hôte</th>
                        <th>Logement</th>
                        <th>Dates</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations as $r): 
                        $nbNuits = (strtotime($r['date_fin']) - strtotime($r['date_deb'])) / (60*60*24);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($r['client_nom']); ?></strong><br>
                            <small><?php echo htmlspecialchars($r['client_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($r['hote_nom']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($r['logement_titre']); ?><br>
                            <small><?php echo htmlspecialchars($r['region_ville']); ?></small>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($r['date_deb'])); ?><br>
                            → <?php echo date('d/m/Y', strtotime($r['date_fin'])); ?>
                            <br><small><?php echo $nbNuits; ?> nuits</small>
                        </td>
                        <td><strong><?php echo number_format($r['montant_total'], 0); ?> TND</strong></td>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- NOTIFICATIONS -->
    <?php if($active_tab == 'notifications'): ?>
    
    <div class="notif-form">
        <div class="section-title" style="margin: 0 0 20px 0; padding: 0; border: none;">
            <i class="fa-solid fa-paper-plane"></i> Envoyer une notification à tous
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="titre" placeholder="Ex: Nouvelle fonctionnalité" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="systeme">📢 Système</option>
                        <option value="reservation">🏠 Réservation</option>
                        <option value="demande">📝 Demande</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Message</label>
                    <textarea name="message" rows="4" placeholder="Votre message..." required></textarea>
                </div>
            </div>
            <button type="submit" name="send_notification" class="btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Envoyer à tous les utilisateurs
            </button>
        </form>
    </div>
    
    <div class="section-title">
        <i class="fa-solid fa-clock-rotate-left"></i> Dernières notifications envoyées
    </div>
    
    <div class="table-container">
        <?php if(empty($notifications)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-bell"></i>
                <h3>Aucune notification</h3>
                <p>Aucune notification n'a été envoyée.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Titre</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($notifications as $n): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($n['client_nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($n['titre']); ?></td>
                        <td><?php echo substr(htmlspecialchars($n['message']), 0, 50); ?>...</td>
                        <td><?php echo date('d/m/Y H:i', strtotime($n['date_creation'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
</div>

</body>
</html>