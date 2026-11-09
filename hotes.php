<?php
// admin/hotes.php
$page_title = "Gestion des Propriétaires";
require_once 'includes/header.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$sql = "SELECT h.*, c.prenom, c.nom, c.email, c.telephone, c.ville,
        (SELECT COUNT(*) FROM logement WHERE id_hote = h.id_hote) as nb_logements
        FROM hote h
        JOIN utilisateur c ON h.id_client = c.id_client
        WHERE c.type_client = 'proprietaire'";
$params = [];

if($search) {
    $sql .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR c.email LIKE :search)";
    $params[':search'] = "%$search%";
}
if($statut) {
    $sql .= " AND h.statut_hote = :statut";
    $params[':statut'] = $statut;
}

$sql_count = str_replace('h.*, c.prenom, c.nom, c.email, c.telephone, c.ville, (SELECT COUNT(*) FROM logement WHERE id_hote = h.id_hote) as nb_logements', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY h.id_hote DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$hotes = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Gestion des Hôtes</h2>
    <p style="color:var(--text-light);"><?php echo $total; ?> hôtes inscrits sur la plateforme</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher un hôte..." style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="statutFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les statuts</option>
        <option value="actif" <?php echo $statut == 'actif' ? 'selected' : ''; ?>>Actif</option>
        <option value="suspendu" <?php echo $statut == 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
        <option value="en_attente" <?php echo $statut == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
      </select>
      <button class="topbar-btn primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Ajouter</button>
    </div>

    <table>
      <thead>
        <tr><th>ID Hôte</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Ville</th><th>Nb logements</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($hotes as $h): ?>
        <tr>
          <td>H<?php echo str_pad($h['id_hote'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo htmlspecialchars($h['prenom'] . ' ' . $h['nom']); ?></td>
          <td><?php echo htmlspecialchars($h['email']); ?></td>
          <td><?php echo htmlspecialchars($h['telephone'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($h['ville'] ?? '-'); ?></td>
          <td><?php echo $h['nb_logements']; ?></td>
          <td><span class="badge <?php echo $h['statut_hote']; ?>"><?php echo $h['statut_hote']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="editHote(<?php echo $h['id_hote']; ?>)"><i class="fa-solid fa-pen"></i></button>
              <button class="action-btn <?php echo $h['statut_hote'] == 'actif' ? 'danger' : 'success'; ?>" onclick="toggleStatut(<?php echo $h['id_hote']; ?>, '<?php echo $h['statut_hote']; ?>')">
                <i class="fa-solid fa-<?php echo $h['statut_hote'] == 'actif' ? 'ban' : 'check'; ?>"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($hotes)): ?>
        <tr><td colspan="8" style="text-align:center;">Aucun hôte trouvé</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if($total_pages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:20px;">
      <?php for($i = 1; $i <= $total_pages; $i++): ?>
      <button class="page-btn" onclick="goToPage(<?php echo $i; ?>)" style="width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:<?php echo $i == $page ? 'var(--rose)' : 'white'; ?>;color:<?php echo $i == $page ? 'white' : 'var(--text)'; ?>"><?php echo $i; ?></button>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Ajouter/Modifier Hôte -->
<div class="modal-overlay" id="hoteModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">&times;</button>
    <h3 id="modalTitle">Ajouter un hôte</h3>
    <form id="hoteForm" method="POST" action="../../api/save_hote.php">
      <input type="hidden" name="id_hote" id="id_hote"/>
      <input type="hidden" name="id_client" id="id_client"/>
      <div class="form-group">
        <label>Prénom</label>
        <input type="text" name="prenom" id="prenom" required/>
      </div>
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="nom" id="nom" required/>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="email" required/>
      </div>
      <div class="form-group">
        <label>Téléphone</label>
        <input type="text" name="telephone" id="telephone"/>
      </div>
      <div class="form-group">
        <label>Ville</label>
        <input type="text" name="ville" id="ville"/>
      </div>
      <div class="form-group">
        <label>Statut hôte</label>
        <select name="statut_hote" id="statut_hote">
          <option value="actif">Actif</option>
          <option value="suspendu">Suspendu</option>
          <option value="en_attente">En attente</option>
        </select>
      </div>
      <div class="form-group" id="passwordGroup">
        <label>Mot de passe</label>
        <input type="password" name="password" id="password" placeholder="Laissez vide pour conserver l'actuel"/>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
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

document.getElementById('searchInput').addEventListener('keyup', function() {
    const url = new URL(window.location.href);
    if(this.value) url.searchParams.set('search', this.value);
    else url.searchParams.delete('search');
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
});

document.getElementById('statutFilter').addEventListener('change', function() {
    const url = new URL(window.location.href);
    if(this.value) url.searchParams.set('statut', this.value);
    else url.searchParams.delete('statut');
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
});

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un hôte';
    document.getElementById('hoteForm').reset();
    document.getElementById('id_hote').value = '';
    document.getElementById('id_client').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('hoteModal').classList.add('show');
}

function editHote(id) {
    fetch(`../../api/get_hote.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Modifier le hôte';
            document.getElementById('id_hote').value = data.id_hote;
            document.getElementById('id_client').value = data.id_client;
            document.getElementById('prenom').value = data.prenom;
            document.getElementById('nom').value = data.nom;
            document.getElementById('email').value = data.email;
            document.getElementById('telephone').value = data.telephone || '';
            document.getElementById('ville').value = data.ville || '';
            document.getElementById('statut_hote').value = data.statut_hote;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('hoteModal').classList.add('show');
        });
}

function toggleStatut(id, statut) {
    const nouveau = statut == 'actif' ? 'suspendu' : 'actif';
    if(confirm(`Confirmer le ${nouveau == 'actif' ? 'réactivation' : 'suspension'} de cet hôte ?`)) {
        window.location.href = `../../api/toggle_hote_statut.php?id=${id}&statut=${nouveau}`;
    }
}

function closeModal() {
    document.getElementById('hoteModal').classList.remove('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>