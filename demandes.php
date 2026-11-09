<?php
// admin/demandes.php
$page_title = "Gestion des Demandes";
require_once 'includes/header.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$sql = "SELECT d.*, CONCAT(c.prenom, ' ', c.nom) as client_nom, c.email as client_email, c.telephone
        FROM demande d
        JOIN utilisateur c ON d.id_client = c.id_client
        WHERE 1=1";
$params = [];

if($search) {
    $sql .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR d.region_souhaitee LIKE :search)";
    $params[':search'] = "%$search%";
}
if($statut) {
    $sql .= " AND d.statut = :statut";
    $params[':statut'] = $statut;
}

$sql_count = str_replace('d.*, CONCAT(c.prenom, \' \', c.nom) as client_nom, c.email as client_email, c.telephone', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY d.date_creation DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$demandes = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Demandes des clients</h2>
    <p style="color:var(--text-light);"><?php echo $total; ?> demandes au total</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher par client ou région..." style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="statutFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les statuts</option>
        <option value="en_attente" <?php echo $statut == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
        <option value="traitee" <?php echo $statut == 'traitee' ? 'selected' : ''; ?>>Traitée</option>
        <option value="refusee" <?php echo $statut == 'refusee' ? 'selected' : ''; ?>>Refusée</option>
        <option value="en_cours" <?php echo $statut == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
      </select>
    </div>

    <table>
      <thead>
        <tr><th>ID</th><th>Client</th><th>Région souhaitée</th><th>Dates</th><th>Budget</th><th>Voyageurs</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($demandes as $d): 
            $nbNuits = (strtotime($d['date_fin']) - strtotime($d['date_deb'])) / (60*60*24);
        ?>
        <tr>
          <td>D<?php echo str_pad($d['id_demande'], 3, '0', STR_PAD_LEFT); ?></td>
          <td>
            <?php echo htmlspecialchars($d['client_nom']); ?><br>
            <small style="color:var(--text-light);"><?php echo htmlspecialchars($d['client_email']); ?></small>
           </td>
          <td><?php echo htmlspecialchars($d['region_souhaitee']); ?></td>
          <td>
            <?php echo date('d/m/Y', strtotime($d['date_deb'])); ?><br>
            <small>→ <?php echo date('d/m/Y', strtotime($d['date_fin'])); ?></small>
            <br><small><?php echo $nbNuits; ?> nuits</small>
           </td>
          <td><?php echo number_format($d['budget'], 0); ?> TND</td>
          <td><?php echo $d['nb_voyageurs']; ?></td>
          <td><span class="badge <?php echo $d['statut']; ?>"><?php echo $d['statut']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="voirDetails(<?php echo $d['id_demande']; ?>)"><i class="fa-solid fa-eye"></i></button>
              <?php if($d['statut'] == 'en_attente'): ?>
              <button class="action-btn success" onclick="updateStatut(<?php echo $d['id_demande']; ?>, 'traitee')"><i class="fa-solid fa-check"></i></button>
              <button class="action-btn danger" onclick="updateStatut(<?php echo $d['id_demande']; ?>, 'refusee')"><i class="fa-solid fa-xmark"></i></button>
              <?php endif; ?>
              <?php if($d['statut'] == 'en_cours'): ?>
              <button class="action-btn success" onclick="updateStatut(<?php echo $d['id_demande']; ?>, 'traitee')"><i class="fa-solid fa-check-double"></i></button>
              <?php endif; ?>
            </div>
           </td>
         </tr>
        <?php endforeach; ?>
        <?php if(empty($demandes)): ?>
         <tr><td colspan="8" style="text-align:center;">Aucune demande trouvée</td></tr>
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
</div>

<!-- Modal Détails Demande -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:500px;">
    <button class="modal-close" onclick="closeDetailModal()">&times;</button>
    <h3>Détails de la demande</h3>
    <div id="detailContent"></div>
    <div style="display:flex;gap:10px;margin-top:20px;" id="detailActions"></div>
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

function voirDetails(id) {
    fetch(`../../api/get_demande.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            const nbNuits = (new Date(data.date_fin) - new Date(data.date_deb)) / (1000*60*60*24);
            document.getElementById('detailContent').innerHTML = `
                <div style="margin-top:20px;">
                    <p><strong>Client :</strong> ${data.client_nom}</p>
                    <p><strong>Email :</strong> ${data.client_email}</p>
                    <p><strong>Téléphone :</strong> ${data.telephone || '-'}</p>
                    <hr>
                    <p><strong>Région souhaitée :</strong> ${data.region_souhaitee}</p>
                    <p><strong>Dates :</strong> ${new Date(data.date_deb).toLocaleDateString()} - ${new Date(data.date_fin).toLocaleDateString()}</p>
                    <p><strong>Nombre de nuits :</strong> ${nbNuits}</p>
                    <p><strong>Nombre de voyageurs :</strong> ${data.nb_voyageurs}</p>
                    <p><strong>Budget :</strong> ${data.budget} TND</p>
                    <p><strong>Statut :</strong> ${data.statut}</p>
                    ${data.description ? `<p><strong>Description :</strong> ${data.description}</p>` : ''}
                </div>
            `;
            
            const actionsDiv = document.getElementById('detailActions');
            if(data.statut == 'en_attente') {
                actionsDiv.innerHTML = `
                    <button class="btn-save" onclick="updateStatut(${data.id_demande}, 'traitee')">✓ Accepter</button>
                    <button class="btn-cancel" onclick="updateStatut(${data.id_demande}, 'refusee')">✗ Refuser</button>
                `;
            } else if(data.statut == 'en_cours') {
                actionsDiv.innerHTML = `<button class="btn-save" onclick="updateStatut(${data.id_demande}, 'traitee')">✓ Marquer comme traitée</button>`;
            } else {
                actionsDiv.innerHTML = '';
            }
            
            document.getElementById('detailModal').classList.add('show');
        });
}

function updateStatut(id, nouveauStatut) {
    if(confirm(`Confirmer le passage au statut "${nouveauStatut}" ?`)) {
        window.location.href = `../../api/update_demande_statut.php?id=${id}&statut=${nouveauStatut}`;
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>