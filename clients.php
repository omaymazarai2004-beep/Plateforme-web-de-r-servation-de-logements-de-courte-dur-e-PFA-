<?php
// admin/clients.php
$page_title = "Gestion des Clients";
require_once 'includes/header.php';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$sql = "SELECT * FROM utilisateur WHERE type_client = 'client'";
$params = [];

if($search) {
    $sql .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if($statut) {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $statut;
}

$sql_count = str_replace('*', 'COUNT(*) as total', $sql);
$stmt = Database::execute($sql_count, $params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY id_client DESC LIMIT $limit OFFSET $offset";
$stmt = Database::execute($sql, $params);
$clients = $stmt->fetchAll();
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">Gestion des Clients</h2>
    <p style="color:var(--text-light);"><?php echo $total; ?> clients inscrits sur la plateforme</p>
  </div>

  <div class="card">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="searchInput" placeholder="Rechercher un client..." class="search-input" style="flex:1;padding:10px 16px;border:1px solid var(--border);border-radius:10px;" value="<?php echo htmlspecialchars($search); ?>"/>
      <select id="statutFilter" style="padding:10px 16px;border:1px solid var(--border);border-radius:10px;">
        <option value="">Tous les statuts</option>
        <option value="actif" <?php echo $statut == 'actif' ? 'selected' : ''; ?>>Actif</option>
        <option value="inactif" <?php echo $statut == 'inactif' ? 'selected' : ''; ?>>Inactif</option>
        <option value="suspendu" <?php echo $statut == 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
      </select>
      <button class="topbar-btn primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Ajouter</button>
    </div>

    <table>
      <thead>
        <tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Téléphone</th><th>Ville</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($clients as $c): ?>
        <tr>
          <td>C<?php echo str_pad($c['id_client'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></td>
          <td><?php echo htmlspecialchars($c['email']); ?></td>
          <td><?php echo htmlspecialchars($c['telephone'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($c['ville'] ?? '-'); ?></td>
          <td><span class="badge <?php echo $c['statut']; ?>"><?php echo $c['statut']; ?></span></td>
          <td>
            <div class="actions">
              <button class="action-btn" onclick="editClient(<?php echo $c['id_client']; ?>)"><i class="fa-solid fa-pen"></i></button>
              <button class="action-btn danger" onclick="deleteClient(<?php echo $c['id_client']; ?>)"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($clients)): ?>
        <tr><td colspan="7" style="text-align:center;">Aucun client trouvé</td></tr>
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

<!-- Modal Ajouter/Modifier Client -->
<div class="modal-overlay" id="clientModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">&times;</button>
    <h3 id="modalTitle">Ajouter un client</h3>
    <form id="clientForm" method="POST" action="../../api/save_client.php">
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
        <label>Statut</label>
        <select name="statut" id="statut">
          <option value="actif">Actif</option>
          <option value="inactif">Inactif</option>
          <option value="suspendu">Suspendu</option>
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
    document.getElementById('modalTitle').textContent = 'Ajouter un client';
    document.getElementById('clientForm').reset();
    document.getElementById('id_client').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('clientModal').classList.add('show');
}

function editClient(id) {
    fetch(`../../api/get_client.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Modifier le client';
            document.getElementById('id_client').value = data.id_client;
            document.getElementById('prenom').value = data.prenom;
            document.getElementById('nom').value = data.nom;
            document.getElementById('email').value = data.email;
            document.getElementById('telephone').value = data.telephone || '';
            document.getElementById('ville').value = data.ville || '';
            document.getElementById('statut').value = data.statut;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('clientModal').classList.add('show');
        });
}

function deleteClient(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
        window.location.href = `../../api/delete_client.php?id=${id}`;
    }
}

function closeModal() {
    document.getElementById('clientModal').classList.remove('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>