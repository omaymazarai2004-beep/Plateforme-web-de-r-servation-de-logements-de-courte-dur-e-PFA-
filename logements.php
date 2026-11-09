<?php
// admin/logements.php
$page_title = "Gestion des Logements";
require_once 'includes/header.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$ville = $_GET['ville'] ?? '';

$sql = "SELECT l.*, CONCAT(c.prenom, ' ', c.nom) as hote_nom
        FROM logement l
        JOIN hote h ON l.id_hote = h.id_hote
        JOIN utilisateur c ON h.id_client = c.id_client
        WHERE 1=1";
$params = [];

if($search) {
    $sql .= " AND l.titre LIKE :search";
    $params[':search'] = "%$search%";
}
if($type) {
    $sql .= " AND l.type = :type";
    $params[':type'] = $type;
}
if($ville) {
    $sql .= " AND l.region_ville LIKE :ville";
    $params[':ville'] = "%$ville%";
}

$sql_count = str_replace('l.*', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY l.id_logement DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$logements = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Gestion des Logements</h2>
    <p style="color:var(--text-light);"><?php echo $total; ?> logements sur la plateforme</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher un logement..." style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="typeFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les types</option>
        <option value="villa" <?php echo $type == 'villa' ? 'selected' : ''; ?>>Villa</option>
        <option value="appartement" <?php echo $type == 'appartement' ? 'selected' : ''; ?>>Appartement</option>
        <option value="studio" <?php echo $type == 'studio' ? 'selected' : ''; ?>>Studio</option>
        <option value="maison" <?php echo $type == 'maison' ? 'selected' : ''; ?>>Maison</option>
      </select>
      <input type="text" id="villeFilter" placeholder="Ville" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($ville); ?>"/>
      <button class="topbar-btn primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Ajouter</button>
    </div>

    <table>
      <thead>
        <tr><th>ID</th><th>Titre</th><th>Hôte</th><th>Type</th><th>Ville</th><th>Prix/nuit</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($logements as $l): ?>
        <tr>
          <td>L<?php echo str_pad($l['id_logement'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo htmlspecialchars($l['titre']); ?></td>
          <td><?php echo htmlspecialchars($l['hote_nom']); ?></td>
          <td><?php echo $l['type']; ?></td>
          <td><?php echo htmlspecialchars($l['region_ville']); ?></td>
          <td><?php echo number_format($l['prix_nuit'], 0); ?> TND</td>
          <td><span class="badge <?php echo $l['statut']; ?>"><?php echo $l['statut']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="editLogement(<?php echo $l['id_logement']; ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
              <button class="action-btn" onclick="deleteLogement(<?php echo $l['id_logement']; ?>)"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>  