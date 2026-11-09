<?php
// admin/includes/header.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// Statistiques pour le sidebar
$sql_clients = "SELECT COUNT(*) as total FROM utilisateur WHERE type_client = 'client'";
$sql_demandes = "SELECT COUNT(*) as total FROM demande WHERE statut = 'en_attente'";
$sql_reservations = "SELECT COUNT(*) as total FROM reservation WHERE statut = 'en_attente'";

$stmt = Database::execute($sql_clients);
$total_clients = $stmt->fetch()['total'];
$stmt = Database::execute($sql_demandes);
$total_demandes = $stmt->fetch()['total'];
$stmt = Database::execute($sql_reservations);
$total_reservations = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard — Logements</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
:root{
  --rose:#FF385C;--rose-dark:#E31C5F;
  --dark:#1a1a2e;--sidebar-w:260px;
  --white:#fff;--bg:#f8f9fc;
  --card-bg:#fff;--border:#e5e7eb;
  --text:#374151;--text-light:#6b7280;
  --success:#10b981;--warning:#f59e0b;--danger:#ef4444;--info:#3b82f6;
  --shadow:0 1px 8px rgba(0,0,0,0.08);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

/* Sidebar */
.sidebar{
  width:var(--sidebar-w);
  background:var(--dark);
  color:white;
  position:fixed;top:0;left:0;bottom:0;
  display:flex;flex-direction:column;
  overflow-y:auto;
  z-index:100;
}
.sidebar-logo{
  padding:28px 24px 24px;
  font-family:'Playfair Display',serif;
  font-size:22px;color:var(--rose);
  display:flex;align-items:center;gap:10px;
  border-bottom:1px solid rgba(255,255,255,0.08);
}
.sidebar-section{padding:20px 16px 8px;font-size:11px;letter-spacing:1px;color:rgba(255,255,255,0.35);text-transform:uppercase;}
.nav-item{
  display:flex;align-items:center;gap:12px;
  padding:12px 20px;margin:2px 8px;
  border-radius:10px;cursor:pointer;
  font-size:14px;font-weight:500;
  color:rgba(255,255,255,0.7);
  transition:all .2s;text-decoration:none;
}
.nav-item:hover{background:rgba(255,255,255,0.08);color:white;}
.nav-item.active{background:var(--rose);color:white;}
.nav-item i{width:18px;text-align:center;font-size:15px;}
.nav-badge{
  margin-left:auto;background:var(--rose);
  color:white;font-size:11px;font-weight:700;
  padding:2px 8px;border-radius:20px;
}
.sidebar-footer{
  margin-top:auto;padding:20px;
  border-top:1px solid rgba(255,255,255,0.08);
}
.admin-profile{
  display:flex;align-items:center;gap:12px;
}
.admin-avatar{
  width:40px;height:40px;border-radius:50%;
  background:var(--rose);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:700;
}
.admin-info{font-size:13px;}
.admin-info strong{display:block;color:white;}
.admin-info span{color:rgba(255,255,255,0.45);font-size:11px;}

/* Main */
.main{
  margin-left:var(--sidebar-w);
  flex:1;display:flex;flex-direction:column;
  min-height:100vh;
}
.topbar{
  background:white;
  padding:0 32px;height:70px;
  display:flex;align-items:center;justify-content:space-between;
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:50;
}
.topbar h1{font-family:'Playfair Display',serif;font-size:22px;font-weight:600;}
.topbar-right{display:flex;align-items:center;gap:16px;}
.topbar-btn{
  padding:8px 16px;border-radius:10px;
  font-size:13px;font-weight:500;cursor:pointer;
  border:1.5px solid var(--border);background:white;
  transition:all .15s;display:flex;align-items:center;gap:6px;
}
.topbar-btn:hover{border-color:var(--rose);color:var(--rose);}
.topbar-btn.primary{background:var(--rose);color:white;border-color:var(--rose);}
.content{padding:32px;flex:1;}
.page{display:none;}
.page.active{display:block;}

/* Cards */
.card{
  background:var(--card-bg);
  border-radius:16px;padding:24px;
  border:1px solid var(--border);
}
.card-header{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:20px;
}
.card-header h3{font-size:16px;font-weight:600;}
.btn-sm{
  padding:6px 14px;border-radius:8px;font-size:12px;
  font-weight:500;cursor:pointer;
  border:1px solid var(--border);background:white;
}

/* Tables */
table{width:100%;border-collapse:collapse;}
th{
  padding:10px 16px;text-align:left;
  font-size:12px;font-weight:600;
  color:var(--text-light);background:#f9fafb;
}
td{padding:14px 16px;font-size:14px;border-bottom:1px solid #f3f4f6;}
.badge{
  padding:4px 10px;border-radius:20px;
  font-size:12px;font-weight:600;
}
.badge.actif{background:#ecfdf5;color:var(--success);}
.badge.inactif{background:#fef2f2;color:var(--danger);}
.badge.attente{background:#fffbeb;color:var(--warning);}
.badge.confirme{background:#eff6ff;color:var(--info);}
.badge.admin{background:linear-gradient(135deg,var(--rose),var(--rose-dark));color:white;}
.badge.hote{background:#f0fdf4;color:#16a34a;}

/* Actions */
.actions{display:flex;gap:6px;}
.action-btn{
  width:32px;height:32px;border-radius:8px;
  border:1px solid var(--border);background:white;
  cursor:pointer;
}
.action-btn:hover{background:var(--rose);color:white;}
.action-btn.danger:hover{background:var(--danger);}
.action-btn.success:hover{background:var(--success);}

/* Modal */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,0.5);z-index:1000;
  align-items:center;justify-content:center;
}
.modal-overlay.show{display:flex;}
.modal{
  background:white;border-radius:20px;
  padding:32px;width:100%;max-width:500px;
}
.modal-close{
  float:right;background:none;border:none;
  font-size:20px;cursor:pointer;
}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;margin-bottom:6px;}
.form-group input,.form-group select,.form-group textarea{
  width:100%;padding:10px 12px;
  border:1px solid var(--border);border-radius:8px;
}
.btn-save{
  background:var(--rose);color:white;
  border:none;padding:12px 24px;
  border-radius:8px;cursor:pointer;
}
.btn-cancel{
  background:#f3f4f6;border:1px solid var(--border);
  padding:12px 24px;border-radius:8px;cursor:pointer;
}
.alert{
  padding:14px 20px;border-radius:12px;
  margin-bottom:20px;
}
.alert.success{background:#ecfdf5;color:#065f46;}
.alert.warning{background:#fffbeb;color:#92400e;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <i class="fa-solid fa-house-chimney"></i>
    <div>Logements<span>Admin Panel</span></div>
  </div>
  <div class="sidebar-section">Principal</div>
  <a class="nav-item" href="../admin-dashboard.php">
    <i class="fa-solid fa-gauge-high"></i> Tableau de bord
  </a>
  <div class="sidebar-section">Gestion</div>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
    <i class="fa-solid fa-users"></i> Clients
    <span class="nav-badge"><?php echo $total_clients; ?></span>
  </a>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hotes.php' ? 'active' : ''; ?>" href="hotes.php">
    <i class="fa-solid fa-user-tie"></i> Hôtes
  </a>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'logements.php' ? 'active' : ''; ?>" href="logements.php">
    <i class="fa-solid fa-building"></i> Logements
  </a>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>" href="reservations.php">
    <i class="fa-solid fa-calendar-days"></i> Réservations
    <span class="nav-badge"><?php echo $total_reservations; ?></span>
  </a>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'demandes.php' ? 'active' : ''; ?>" href="demandes.php">
    <i class="fa-solid fa-inbox"></i> Demandes
    <span class="nav-badge"><?php echo $total_demandes; ?></span>
  </a>
  <div class="sidebar-section">Contenu</div>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'photos.php' ? 'active' : ''; ?>" href="photos.php">
    <i class="fa-solid fa-images"></i> Photos
  </a>
  <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'mcd.php' ? 'active' : ''; ?>" href="mcd.php">
    <i class="fa-solid fa-diagram-project"></i> MCD Projet
  </a>
  <div class="sidebar-footer">
    <div class="admin-profile">
      <div class="admin-avatar">A</div>
      <div class="admin-info">
        <strong>Administrateur</strong>
        <span><?php echo $_SESSION['user_email']; ?></span>
      </div>
    </div>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
    <div class="topbar-right">
      <button class="topbar-btn" onclick="location.reload()"><i class="fa-solid fa-rotate"></i> Actualiser</button>
      <a href="../../logout.php" class="topbar-btn"><i class="fa-solid fa-sign-out-alt"></i> Déconnexion</a>
      <a href="../../index.php" class="topbar-btn"><i class="fa-solid fa-arrow-left"></i> Site</a>
    </div>
  </div>
  <div class="content">