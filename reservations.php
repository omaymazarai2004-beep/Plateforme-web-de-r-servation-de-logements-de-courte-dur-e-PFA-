<?php
// admin/reservations.php
$page_title = "Gestion des Réservations";
require_once 'includes/header.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$sql = "SELECT r.*, l.titre, l.region_ville, 
        CONCAT(c.prenom, ' ', c.nom) as client_nom,
        CONCAT(hc.prenom, ' ', hc.nom) as hote_nom
        FROM reservation r
        JOIN logement l ON r.id_logement = l.id_logement
        JOIN utilisateur c ON r.id_client = c.id_client
        JOIN hote h ON l.id_hote = h.id_hote
        JOIN utilisateur hc ON h.id_client = hc.id_client
        WHERE 1=1";
$params = [];

if($search) {
    $sql .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR l.titre LIKE :search)";
    $params[':search'] = "%$search%";
}
if($statut) {
    $sql .= " AND r.statut = :statut";
    $params[':statut'] = $statut;
}

$sql_count = str_replace('r.*, l.titre, l.region_ville, CONCAT(c.prenom, \' \', c.nom) as client_nom, CONCAT(hc.prenom, \' \', hc.nom) as hote_nom', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY r.date_creation DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$reservations = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Gestion des Réservations</h2>
    <p style="color:var(--text-light);">Conformément au MCD — clé primaire composite (id_client, id_logement, date_deb, date_fin)</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher par client ou logement..." style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="statutFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les statuts</option>
        <option value="en_attente" <?php echo $statut == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
        <option value="confirmee" <?php echo $statut == 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
        <option value="annulee" <?php echo $statut == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
        <option value="terminee" <?php echo $statut == 'terminee' ? 'selected' : ''; ?>>Terminée</option>
      </select>
    </div>

    <table>
      <thead>
        <tr><th>Client</th><th>Logement</th><th>Hôte</th><th>Date début</th><th>Date fin</th><th>Montant</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($reservations as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['client_nom']); ?><br><small style="color:var(--text-light);">#<?php echo $r['id_client']; ?></small></td>
          <td><?php echo htmlspecialchars($r['titre']); ?><br><small><?php echo htmlspecialchars($r['region_ville']); ?></small></td>
          <td><?php echo htmlspecialchars($r['hote_nom']); ?></td>
          <td><?php echo date('d/m/Y', strtotime($r['date_deb'])); ?></td>
          <td><?php echo date('d/m/Y', strtotime($r['date_fin'])); ?></td>
          <td><?php echo number_format($r['montant_total'], 0); ?> TND</td>
          <td><span class="badge <?php echo $r['statut']; ?>"><?php echo $r['statut']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="voirDetails(<?php echo $r['id_client']; ?>, <?php echo $r['id_logement']; ?>, '<?php echo $r['date_deb']; ?>')"><i class="fa-solid fa-eye"></i></button>
              <?php if($r['statut'] == 'en_attente'): ?>
              <button class="action-btn success" onclick="updateStatut(<?php echo $r['id_client']; ?>, <?php echo $r['id_logement']; ?>, '<?php echo $r['date_deb']; ?>', '<?php echo $r['date_fin']; ?>', 'confirmee')"><i class="fa-solid fa-check"></i></button>
              <?php endif; ?>
              <?php if($r['statut'] != 'annulee' && $r['statut'] != 'terminee'): ?>
              <button class="action-btn danger" onclick="updateStatut(<?php echo $r['id_client']; ?>, <?php echo $r['id_logement']; ?>, '<?php echo $r['date_deb']; ?>', '<?php echo $r['date_fin']; ?>', 'annulee')"><i class="fa-solid fa-xmark"></i></button>
              <?php endif; ?>
            </div>
           </td>
         </tr>
        <?php endforeach; ?>
        <?php if(empty($reservations)): ?>
         <tr><td colspan="8" style="text-align:center;">Aucune réservation trouvée</td></tr>
        <?php endif; ?>
      </tbody>
     </table>

    <?php if($total_pages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:20px;">
      <?php for($i = 1; $i <= $total_pages; $i++): ?>
      <button class="page-btn" onclick="goToPage(<?php echo $i; ?>)" style="width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:<?php echo $i == $page ? 'var(--rose)' : 'white'; ?>"><?php echo $i; ?></button>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
  
  <div class="card" style="margin-top:20px;">
    <div class="card-header">
      <h3><i class="fa-solid fa-circle-info"></i> Note sur le MCD</h3>
    </div>
    <p style="font-size:14px;color:var(--text-light);">La clé primaire de Réservation est la combinaison unique (id_client, id_logement, date_deb, date_fin) — conformément aux corrections de Mme Zoulele Kouki.</p>
  </div>
</div>

<!-- Modal Détails -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:600px;">
    <button class="modal-close" onclick="closeDetailModal()">&times;</button>
    <h3>Détails de la réservation</h3>
    <div id="detailContent"></div>
  </div>
</div>

<script>
function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

function applyFilters() {
    const url = new URL(window.location.href);
    const search = document.getElementById('searchInput').value;
    const statut = document.getElementById('statutFilter').value;
    if(search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    if(statut) url.searchParams.set('statut', statut);
    else url.searchParams.delete('statut');
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

document.getElementById('searchInput').addEventListener('keyup', applyFilters);
document.getElementById('statutFilter').addEventListener('change', applyFilters);

function voirDetails(idClient, idLogement, dateDeb) {
    fetch(`../../api/get_reservation.php?id_client=${idClient}&id_logement=${idLogement}&date_deb=${dateDeb}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('detailContent').innerHTML = `
                <div style="margin-top:20px;">
                    <p><strong>Client :</strong> ${data.client_nom}</p>
                    <p><strong>Logement :</strong> ${data.titre}</p>
                    <p><strong>Hôte :</strong> ${data.hote_nom}</p>
                    <p><strong>Dates :</strong> ${new Date(data.date_deb).toLocaleDateString()} - ${new Date(data.date_fin).toLocaleDateString()}</p>
                    <p><strong>Nombre de nuits :</strong> ${data.nb_nuits}</p>
                    <p><strong>Nombre de voyageurs :</strong> ${data.nb_voyageurs}</p>
                    <p><strong>Montant total :</strong> ${data.montant_total} TND</p>
                    <p><strong>Statut :</strong> ${data.statut}</p>
                    <p><strong>Date de création :</strong> ${new Date(data.date_creation).toLocaleString()}</p>
                    ${data.commentaire ? `<p><strong>Commentaire :</strong> ${data.commentaire}</p>` : ''}
                </div>
            `;
            document.getElementById('detailModal').classList.add('show');
        });
}

function updateStatut(idClient, idLogement, dateDeb, dateFin, nouveauStatut) {
    if(confirm(`Confirmer le passage au statut "${nouveauStatut}" ?`)) {
        window.location.href = `../../api/update_reservation_statut.php?id_client=${idClient}&id_logement=${idLogement}&date_deb=${dateDeb}&date_fin=${dateFin}&statut=${nouveauStatut}`;
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>