<?php
// Pied de page — présentation soignée, liens internes et références officielles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$y = (int) date('Y');
$logged = isset($_SESSION['user_id']);
$role = $_SESSION['user_role'] ?? '';
?>
<footer class="site-footer" role="contentinfo">
  <div class="site-footer-main">
    <div class="site-footer-col site-footer-about">
      <a href="index.php" class="site-footer-brand"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i> Logements</a>
      <p class="site-footer-tagline">Locations entre particuliers en Tunisie. Parcourez des annonces claires et gérez vos séjours ou votre activité d’hôte en toute simplicité.</p>
      <div class="site-footer-trust" aria-label="Engagements">
        <span class="site-footer-trust-item"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Paiements et données traités avec soin</span>
        <span class="site-footer-trust-item"><i class="fa-solid fa-headset" aria-hidden="true"></i> Assistance via le chat intégré</span>
      </div>
    </div>

    <div class="site-footer-col">
      <h2 class="site-footer-heading">Découvrir</h2>
      <ul class="site-footer-links">
        <li><a href="index.php">Accueil &amp; annonces</a></li>
        <li><a href="index.php#listingsGrid">Parcourir les logements</a></li>
        <?php if ($logged && $role === 'client'): ?>
          <li><a href="mes-demandes.php">Mes demandes</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="site-footer-col">
      <h2 class="site-footer-heading">Voyageurs</h2>
      <ul class="site-footer-links">
        <?php if ($logged): ?>
          <li><a href="mes-reservations.php">Mes réservations</a></li>
          <li><a href="notifications.php">Notifications</a></li>
          <li><a href="correspondance.php">Messagerie</a></li>
          <li><a href="profil.php">Mon profil</a></li>
        <?php else: ?>
          <li><a href="index.php">Créer un compte voyageur</a></li>
          <li><span class="site-footer-muted">Connectez-vous pour accéder à vos réservations et messages.</span></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="site-footer-col">
      <h2 class="site-footer-heading">Propriétaires</h2>
      <ul class="site-footer-links">
        <li><a href="publier.php">Publier une annonce</a></li>
        <?php if ($logged && $role === 'proprietaire'): ?>
          <li><a href="hote-dashboard.php">Tableau de bord</a></li>
          <li><a href="mes-reservations-proprietaire.php">Réservations reçues</a></li>
          <li><a href="correspondances.php">Messages hôte</a></li>
        <?php else: ?>
          <li><a href="index.php">Proposer mon logement</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="site-footer-col">
      <h2 class="site-footer-heading">Références officielles</h2>
      <p class="site-footer-col-intro">Sources utiles pour préparer un séjour en Tunisie (sites publics).</p>
      <ul class="site-footer-links site-footer-links-external">
        <li>
          <a href="https://www.tourisme.gov.tn/?lang=fr" target="_blank" rel="noopener noreferrer">Ministère du Tourisme <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </li>
        <li>
          <a href="https://ontt.tn/" target="_blank" rel="noopener noreferrer">Office national du tourisme tunisien (ONTT) <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </li>
      </ul>
    </div>
  </div>

  <div class="site-footer-bottom">
    <p class="site-footer-copy">&copy; <?php echo $y; ?> Logements — Tunisie. Tous droits réservés.</p>
    <nav class="site-footer-legal" aria-label="Informations légales">
      <span class="site-footer-legal-note">Plateforme de mise en relation. Les contrats de location restent entre les parties.</span>
    </nav>
  </div>
</footer>

<!-- ═══ CHATBOT ═══ -->
<link rel="stylesheet" href="assets/css/chatbot.css">
<script>
  window.CB_USER = {
    id:   <?php echo json_encode(isset($_SESSION['user_id'])   ? (int)$_SESSION['user_id']   : null); ?>,
    role: <?php echo json_encode(isset($_SESSION['user_role']) ? $_SESSION['user_role']       : 'guest'); ?>
  };
</script>
<script src="assets/js/chatbot.js" defer></script>
<!-- ════════════════ -->

</body>
</html>
