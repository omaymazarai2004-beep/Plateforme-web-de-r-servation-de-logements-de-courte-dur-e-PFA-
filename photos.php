<?php
// admin/photos.php
$page_title = "Gestion des Photos";
require_once 'includes/header.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$sql = "SELECT p.*, l.titre as logement_titre, CONCAT(c.prenom, ' ', c.nom) as hote_nom
        FROM photo p
        JOIN logement l ON p.id_logement = l.id_logement
        JOIN hote h ON p.id_hote = h.id_hote
        JOIN utilisateur c ON h.id_client = c.id_client
        WHERE 1=1";
$params = [];

if($search) {
    $sql .= " AND (l.titre LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}
if($statut) {
    $sql .= " AND p.statut = :statut";
    $params[':statut'] = $statut;
}

$sql_count = str_replace('p.*, l.titre as logement_titre, CONCAT(c.prenom, \' \', c.nom) as hote_nom', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY p.id_photo DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$photos = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Gestion des Photos</h2>
    <p style="color:var(--text-light);">Photos associées aux logements</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher par logement..." style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="statutFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les statuts</option>
        <option value="active" <?php echo $statut == 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="en_revision" <?php echo $statut == 'en_revision' ? 'selected' : ''; ?>>En révision</option>
        <option value="rejetee" <?php echo $statut == 'rejetee' ? 'selected' : ''; ?>>Rejetée</option>
      </select>
      <button class="topbar-btn primary" onclick="openUploadModal()"><i class="fa-solid fa-upload"></i> Uploader</button>
    </div>

    <table>
      <thead>
        <tr><th>ID Photo</th><th>Aperçu</th><th>Logement</th><th>Hôte</th><th>Description</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($photos as $p): ?>
        <tr>
          <td>P<?php echo str_pad($p['id_photo'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><img src="<?php echo htmlspecialchars($p['url']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" onerror="this.src='https://via.placeholder.com/60'"></td>
          <td><?php echo htmlspecialchars($p['logement_titre']); ?></td>
          <td><?php echo htmlspecialchars($p['hote_nom']); ?></td>
          <td><?php echo htmlspecialchars($p['description'] ?? '-'); ?></td>
          <td><span class="badge <?php echo $p['statut']; ?>"><?php echo $p['statut']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="editPhoto(<?php echo $p['id_photo']; ?>)"><i class="fa-solid fa-pen"></i></button>
              <?php if($p['statut'] == 'en_revision'): ?>
              <button class="action-btn success" onclick="updateStatut(<?php echo $p['id_photo']; ?>, 'active')"><i class="fa-solid fa-check"></i></button>
              <button class="action-btn danger" onclick="updateStatut(<?php echo $p['id_photo']; ?>, 'rejetee')"><i class="fa-solid fa-xmark"></i></button>
              <?php endif; ?>
              <button class="action-btn danger" onclick="deletePhoto(<?php echo $p['id_photo']; ?>)"><i class="fa-solid fa-trash"></i></button>
            </div>
           </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($photos)): ?>
          <tr><td colspan="7" style="text-align:center;">Aucune photo trouvée</td></tr>
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

<!-- Modal Upload Photo -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal">
    <button class="modal-close" onclick="closeUploadModal()">&times;</button>
    <h3>Uploader une photo</h3>
    <form id="uploadForm" method="POST" action="../../api/upload_photo.php" enctype="multipart/form-data">
      <div class="form-group">
        <label>Logement</label>
        <select name="id_logement" id="id_logement" required>
          <option value="">Sélectionner un logement</option>
          <?php
          $sql_logements = "SELECT l.id_logement, l.titre FROM logement WHERE statut = 'actif'";
          $stmt_logements = Database::execute($sql_logements);
          while($log = $stmt_logements->fetch()) {
              echo "<option value='{$log['id_logement']}'>{$log['titre']}</option>";
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label>Photo</label>
        <input type="file" name="photo" accept="image/*" required/>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Description de la photo"></textarea>
      </div>
      <div class="form-group">
        <label>Photo principale ?</label>
        <select name="est_principale">
          <option value="0">Non</option>
          <option value="1">Oui</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn-cancel" onclick="closeUploadModal()">Annuler</button>
        <button type="submit" class="btn-save">Uploader</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modifier Photo -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <button class="modal-close" onclick="closeEditModal()">&times;</button>
    <h3>Modifier la photo</h3>
    <form id="editForm" method="POST" action="../../api/update_photo.php">
      <input type="hidden" name="id_photo" id="edit_id_photo"/>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="edit_description" rows="2"></textarea>
      </div>
      <div class="form-group">
        <label>Statut</label>
        <select name="statut" id="edit_statut">
          <option value="active">Active</option>
          <option value="en_revision">En révision</option>
          <option value="rejetee">Rejetée</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
        <button type="submit" class="btn-save">Enregistrer</button>
      </div>
    </form>
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

function openUploadModal() {
    document.getElementById('uploadForm').reset();
    document.getElementById('uploadModal').classList.add('show');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('show');
}

function editPhoto(id) {
    fetch(`../../api/get_photo.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_id_photo').value = data.id_photo;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_statut').value = data.statut;
            document.getElementById('editModal').classList.add('show');
        });
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function updateStatut(id, nouveauStatut) {
    window.location.href = `../../api/update_photo_statut.php?id=${id}&statut=${nouveauStatut}`;
}

function deletePhoto(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
        window.location.href = `../../api/delete_photo.php?id=${id}`;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>