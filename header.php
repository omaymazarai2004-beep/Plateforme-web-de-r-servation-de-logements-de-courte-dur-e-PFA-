<?php
// includes/header.php - CORRIGÉ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LOGEMENTS — Trouvez votre chez-vous</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* Navigation */
nav {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: white;
  border-bottom: 1px solid var(--gray, #ddd);
  padding: 0 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 80px;
}
.logo {
  font-family: 'Playfair Display', serif;
  font-size: 26px;
  font-weight: 700;
  color: #FF385C;
  text-decoration: none;
}
.nav-center {
  display: flex;
  align-items: center;
  gap: 8px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
.nav-link {
  padding: 10px 16px;
  border-radius: 24px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  color: #222;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
}
.nav-link i {
  margin-right: 8px;
  font-size: 0.95em;
}
.nav-link.active {
  background: transparent;
  color: #FF385C !important;
  font-weight: 600;
}
.nav-link.active i {
  color: #FF385C;
}
.nav-link:hover {
  background: rgba(255,56,92,0.08);
  color: #FF385C;
}
.nav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
}
.nav-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 8px 8px 16px;
  border: 1px solid #ddd;
  border-radius: 24px;
  cursor: pointer;
}
.avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #FF385C;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}
.dropdown {
  position: absolute;
  top: 70px;
  right: 0;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 30px rgba(0,0,0,0.15);
  min-width: 220px;
  padding: 8px 0;
  display: none;
  z-index: 1500;
}
.dropdown.show { display: block; }
.dropdown a, .dropdown button {
  display: block;
  width: 100%;
  padding: 12px 20px;
  text-decoration: none;
  color: #222;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
}
.dropdown a:hover, .dropdown button:hover { background: #f7f7f7; }
.dropdown-divider { height: 1px; background: #ddd; margin: 6px 0; }
.admin-link { color: #FF385C !important; font-weight: 600; }

/* Modal amélioré */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  z-index: 2000;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-overlay.show {
  display: flex;
  animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 24px;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 40px;
  position: relative;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-close {
  position: absolute;
  top: 24px;
  right: 24px;
  background: #f5f5f5;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #666;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: #FF385C;
  color: white;
  transform: rotate(90deg);
}

.modal h2 {
  font-family: 'Playfair Display', serif;
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #222;
  text-align: center;
}

.modal h2::after {
  content: '';
  display: block;
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, #FF385C, #FF7096);
  margin: 12px auto 0;
  border-radius: 2px;
}
.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #222;
}

.form-group input, .form-group select {
  width: 100%;
  padding: 16px 20px;
  border: 2px solid #e1e5e9;
  border-radius: 12px;
  font-size: 16px;
  outline: none;
  transition: all 0.2s ease;
  background: #fafbfc;
}

.form-group input:focus, .form-group select:focus {
  border-color: #FF385C;
  background: white;
  box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
}

.btn-primary {
  width: 100%;
  padding: 18px 24px;
  background: linear-gradient(135deg, #FF385C, #FF7096);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 8px;
  box-shadow: 0 4px 12px rgba(255, 56, 92, 0.2);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 56, 92, 0.3);
}

.btn-primary:active {
  transform: translateY(0);
}

.switch-form {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: #666;
}

.switch-form a {
  color: #FF385C;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s ease;
}

.switch-form a:hover {
  color: #E00B41;
  text-decoration: underline;
}
}
.btn-primary {
  width: 100%;
  padding: 16px;
  background: #FF385C;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
.switch-form { text-align: center; margin-top: 20px; font-size: 14px; }
.switch-form a { color: #FF385C; text-decoration: none; }
.alert {
  position: fixed;
  top: 90px;
  right: 20px;
  padding: 12px 20px;
  border-radius: 12px;
  z-index: 2000;
  animation: slideIn 0.3s ease;
}
.alert.success { background: #10b981; color: white; }
.alert.error { background: #ef4444; color: white; }
@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

/* Account Types Selection améliorée */
.account-types {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;
}

.account-type-option {
  position: relative;
}

.account-type-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.account-type-option label {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 2px solid #e1e5e9;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafbfc;
  position: relative;
  overflow: hidden;
}

.account-type-option label::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 56, 92, 0.05), transparent);
  transition: left 0.5s ease;
}

.account-type-option input[type="radio"]:checked + label {
  border-color: #FF385C;
  background: linear-gradient(135deg, rgba(255, 56, 92, 0.05), rgba(255, 112, 150, 0.05));
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 56, 92, 0.15);
}

.account-type-option input[type="radio"]:checked + label::before {
  left: 100%;
}

.account-type-option label:hover {
  border-color: #FF385C;
  background: rgba(255, 56, 92, 0.02);
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(255, 56, 92, 0.1);
}

.type-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f5f5f5, #e1e5e9);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  font-size: 18px;
  transition: all 0.3s ease;
  flex-shrink: 0;
  position: relative;
  z-index: 2;
}

.account-type-option input[type="radio"]:checked + label .type-icon {
  background: linear-gradient(135deg, #FF385C, #FF7096);
  color: white;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(255, 56, 92, 0.3);
}

.type-info {
  flex: 1;
  position: relative;
  z-index: 2;
}

.type-info strong {
  display: block;
  font-size: 16px;
  font-weight: 700;
  color: #222;
  margin-bottom: 4px;
}

.type-info small {
  font-size: 14px;
  color: #666;
  font-weight: 500;
  line-height: 1.4;
}
  font-size: 14px;
  font-weight: 600;
  color: #222;
  margin-bottom: 2px;
}

.type-info small {
  font-size: 12px;
  color: #666;
  font-weight: 400;
}
</style>
</head>
<body>

<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav>
  <a href="index.php" class="logo"><i class="fa-solid fa-house-chimney"></i> Logements</a>
  <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
    <div class="nav-center">
      <a href="mes-demandes.php" class="nav-link <?php echo $currentPage === 'mes-demandes.php' ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i>Mes demandes</a>
      <a href="mes-reservations.php" class="nav-link <?php echo $currentPage === 'mes-reservations.php' ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i>Mes réservations</a>
      <a href="notifications.php" class="nav-link <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>"><i class="fa-solid fa-bell"></i>Notifications</a>
      <a href="correspondance.php" class="nav-link <?php echo $currentPage === 'correspondance.php' ? 'active' : ''; ?>"><i class="fa-solid fa-comments"></i>Correspondance</a>
    </div>
  <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'proprietaire'): ?>
    <div class="nav-center">
      <a href="publier.php" class="nav-link"><i class="fa-solid fa-house-chimney"></i>Publier un logement</a>
    </div>
  <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="nav-center">
      <a href="admin-dashboard.php" class="nav-link <?php echo $currentPage === 'admin-dashboard.php' ? 'active' : ''; ?>"><i class="fa-solid fa-gauge-high"></i>Tableau de bord</a>
    </div>
  <?php endif; ?>
  <div class="nav-right">
    <div class="nav-profile" onclick="toggleDropdown()">
      <i class="fa-solid fa-bars"></i>
      <div class="avatar" id="avatarIcon">
        <?php if(isset($_SESSION['user_id'])): ?>
          <span><?php echo strtoupper(substr($_SESSION['user_nom'] ?? 'U', 0, 1)); ?></span>
        <?php else: ?>
          <i class="fa-solid fa-user"></i>
        <?php endif; ?>
      </div>
    </div>
    <div class="dropdown" id="dropdown">
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="profil.php"><i class="fa-solid fa-user"></i> Mon profil</a>
        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
          <!-- Liens principaux maintenant dans la barre de navigation -->
        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'proprietaire'): ?>
          <a href="hote-dashboard.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord hôte</a>
          <a href="publier.php"><i class="fa-solid fa-plus"></i> Publier un logement</a>
        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="admin-dashboard.php"><i class="fa-solid fa-gauge-high"></i> Tableau de bord</a>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Se déconnecter</a>
      <?php else: ?>
        <a href="#" onclick="showLogin()"><i class="fa-solid fa-sign-in-alt"></i> Se connecter</a>
        <a href="#" onclick="showRegister()"><i class="fa-solid fa-user-plus"></i> S'inscrire</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Modals -->
<div class="modal-overlay" id="loginModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('loginModal')">&times;</button>
    <h2>Connexion</h2>
    <form method="POST" action="api/login.php">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn-primary">Se connecter</button>
    </form>
    <div class="switch-form">Pas encore de compte ? <a href="#" onclick="showRegister()">S'inscrire</a></div>
  </div>
</div>

<div class="modal-overlay" id="registerModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
    <h2>Inscription</h2>
    <form method="POST" action="api/register.php">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label>Prénom</label><input type="text" name="prenom" required></div>
        <div class="form-group"><label>Nom</label><input type="text" name="nom" required></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>Téléphone</label><input type="tel" name="telephone"></div>
      <div class="form-group"><label>Mot de passe</label><input type="password" name="password" required></div>
      <div class="form-group">
        <label>Type de compte</label>
        <div class="account-types">
          <div class="account-type-option">
            <input type="radio" id="type_client" name="type_client" value="client" checked>
            <label for="type_client">
              <div class="type-icon"><i class="fa-solid fa-user"></i></div>
              <div class="type-info">
                <strong>Client (Voyageur)</strong>
                <small>Je cherche à louer un logement</small>
              </div>
            </label>
          </div>
          <div class="account-type-option">
            <input type="radio" id="type_proprietaire" name="type_client" value="proprietaire">
            <label for="type_proprietaire">
              <div class="type-icon"><i class="fa-solid fa-home"></i></div>
              <div class="type-info">
                <strong>Propriétaire</strong>
                <small>Je propose mon logement en location</small>
              </div>
            </label>
          </div>
          <div class="account-type-option">
            <input type="radio" id="type_admin" name="type_client" value="admin">
            <label for="type_admin">
              <div class="type-icon"><i class="fa-solid fa-crown"></i></div>
              <div class="type-info">
                <strong>Administrateur</strong>
                <small>Accès réservé à l'équipe Logements</small>
              </div>
            </label>
          </div>
        </div>
      </div>
      <button type="submit" class="btn-primary">S'inscrire</button>
    </form>
    <div class="switch-form">Déjà un compte ? <a href="#" onclick="showLogin()">Se connecter</a></div>
  </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById('dropdown').classList.toggle('show');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-profile') && !e.target.closest('.dropdown')) {
        document.getElementById('dropdown').classList.remove('show');
    }
});
function showLogin() {
    document.getElementById('loginModal').classList.add('show');
    document.getElementById('dropdown').classList.remove('show');
}
function showRegister() {
    document.getElementById('registerModal').classList.add('show');
    document.getElementById('loginModal').classList.remove('show');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}
<?php if(isset($_GET['success'])): ?>
    // Removed connexion reussite message
<?php elseif(isset($_GET['error'])): ?>
    showNotification('Email ou mot de passe incorrect', 'error');
<?php endif; ?>
function showNotification(message, type) {
    var div = document.createElement('div');
    div.className = 'alert ' + type;
    div.innerHTML = message;
    document.body.appendChild(div);
    setTimeout(function() { div.remove(); }, 3000);
}
</script>