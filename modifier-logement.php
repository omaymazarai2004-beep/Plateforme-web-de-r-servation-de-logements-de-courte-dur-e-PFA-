<?php
// modifier-logement.php - Modifier un logement existant
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour modifier un logement');
    exit();
}

$id_logement = $_GET['id'] ?? 0;

// Vérifier que le logement appartient bien à l'utilisateur connecté
$sql = "SELECT l.*, h.id_client, h.id_hote 
        FROM logement l 
        JOIN hote h ON l.id_hote = h.id_hote 
        WHERE l.id_logement = :id_logement AND h.id_client = :id_client";
$stmt = Database::execute($sql, [
    ':id_logement' => $id_logement,
    ':id_client' => $_SESSION['user_id']
]);
$logement = $stmt->fetch();

if (!$logement) {
    header('Location: index.php?error=Logement non trouvé ou accès non autorisé');
    exit();
}

$id_hote = $logement['id_hote'];
$success = '';
$error = '';

// Vérifier si la table photo a la bonne structure
try {
    // Tenter de récupérer les photos avec id_logement
    $photos = Database::execute("SELECT * FROM photo WHERE id_logement = :id_logement ORDER BY est_principale DESC, id_photo ASC", [
        ':id_logement' => $id_logement
    ])->fetchAll();
} catch (Exception $e) {
    // Si erreur, essayer avec id_hote (ancienne structure)
    $photos = Database::execute("SELECT * FROM photo WHERE id_hote = :id_hote", [
        ':id_hote' => $id_hote
    ])->fetchAll();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $region_ville = $_POST['region_ville'] ?? '';
    $prix_nuit = $_POST['prix_nuit'] ?? 0;
    $nb_lits = $_POST['nb_lits'] ?? 1;
    $nb_chambres = $_POST['nb_chambres'] ?? 1;
    $nb_sdb = $_POST['nb_sdb'] ?? 1;
    $surface = $_POST['surface'] ?? null;
    $adresse_exacte = $_POST['adresse_exacte'] ?? '';
    
    if (empty($titre) || empty($description) || empty($type) || empty($region_ville) || $prix_nuit <= 0) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Mettre à jour le logement
            $sql = "UPDATE logement SET 
                    titre = :titre,
                    description = :description,
                    type = :type,
                    region_ville = :region_ville,
                    prix_nuit = :prix_nuit,
                    nb_lits = :nb_lits,
                    nb_chambres = :nb_chambres,
                    nb_sdb = :nb_sdb,
                    surface = :surface,
                    adresse_exacte = :adresse_exacte
                    WHERE id_logement = :id_logement AND id_hote = :id_hote";
            
            Database::execute($sql, [
                ':titre' => $titre,
                ':description' => $description,
                ':type' => $type,
                ':region_ville' => $region_ville,
                ':prix_nuit' => $prix_nuit,
                ':nb_lits' => $nb_lits,
                ':nb_chambres' => $nb_chambres,
                ':nb_sdb' => $nb_sdb,
                ':surface' => $surface,
                ':adresse_exacte' => $adresse_exacte,
                ':id_logement' => $id_logement,
                ':id_hote' => $id_hote
            ]);
            
            // Créer le dossier uploads s'il n'existe pas
            $upload_dir = 'uploads/logements/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Traitement des nouvelles photos
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $max_size = 5 * 1024 * 1024;
                
                // Récupérer le nombre de photos existantes
                $existing_photos = Database::execute("SELECT COUNT(*) as count FROM photo WHERE id_logement = :id_logement", [
                    ':id_logement' => $id_logement
                ])->fetch();
                $has_no_photos = ($existing_photos['count'] == 0);
                
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_type = $_FILES['photos']['type'][$key];
                        $file_size = $_FILES['photos']['size'][$key];
                        $file_tmp = $_FILES['photos']['tmp_name'][$key];
                        
                        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                            $extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                            $new_name = uniqid('photo_') . '_' . time() . '_' . $key . '.' . $extension;
                            $destination = $upload_dir . $new_name;
                            
                            if (move_uploaded_file($file_tmp, $destination)) {
                                // Déterminer si c'est la photo principale
                                $is_principal = ($has_no_photos && $key == 0) ? 1 : 0;
                                
                                // Insérer dans la table photo avec la bonne structure
                                $sql_photo = "INSERT INTO photo (id_logement, url, est_principale) VALUES (:id_logement, :url, :est_principale)";
                                Database::execute($sql_photo, [
                                    ':id_logement' => $id_logement,
                                    ':url' => $destination,
                                    ':est_principale' => $is_principal
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Suppression des photos sélectionnées
            if (isset($_POST['delete_photos']) && !empty($_POST['delete_photos'])) {
                $delete_ids = $_POST['delete_photos'];
                foreach ($delete_ids as $id_photo) {
                    // Récupérer l'URL de la photo pour supprimer le fichier
                    $photo = Database::execute("SELECT url FROM photo WHERE id_photo = :id_photo AND id_logement = :id_logement", [
                        ':id_photo' => $id_photo,
                        ':id_logement' => $id_logement
                    ])->fetch();
                    
                    if ($photo) {
                        // Supprimer le fichier
                        if (file_exists($photo['url'])) {
                            unlink($photo['url']);
                        }
                        // Supprimer de la base
                        Database::execute("DELETE FROM photo WHERE id_photo = :id_photo", [':id_photo' => $id_photo]);
                    }
                }
            }
            
            // Recharger les photos
            $photos = Database::execute("SELECT * FROM photo WHERE id_logement = :id_logement ORDER BY est_principale DESC, id_photo ASC", [
                ':id_logement' => $id_logement
            ])->fetchAll();
            
            $success = "Logement mis à jour avec succès !";
            
        } catch(Exception $e) {
            $error = "Erreur lors de la modification: " . $e->getMessage();
        }
    }
}

// Définir la photo principale
$photo_principale = null;
foreach ($photos as $p) {
    if (isset($p['est_principale']) && $p['est_principale'] == 1) {
        $photo_principale = $p;
        break;
    }
}
if (!$photo_principale && !empty($photos)) {
    $photos[0]['est_principale'] = 1;
    $photo_principale = $photos[0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier mon logement - Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root {
    --airbnb: #FF5A5F;
    --airbnb-dark: #E00B41;
    --airbnb-light: #FF8A8F;
    --gray-bg: #F7F7F7;
    --gray-border: #DDDDDD;
    --text-dark: #222222;
    --text-light: #717171;
}

body {
    background: var(--gray-bg);
    font-family: 'Inter', sans-serif;
}

/* Navigation */
nav {
    background: white;
    padding: 16px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-border);
    margin-bottom: 30px;
}

.logo {
    font-size: 24px;
    font-weight: 700;
    color: var(--airbnb);
    text-decoration: none;
}

.nav-back {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid var(--gray-border);
    border-radius: 40px;
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 500;
    transition: all 0.2s;
}

.nav-back:hover {
    border-color: var(--airbnb);
    color: var(--airbnb);
}

/* Container */
.form-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px 40px;
}

.form-card {
    background: white;
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-border);
}

.form-card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin-bottom: 12px;
    color: var(--text-dark);
}

.form-card p {
    color: var(--text-light);
    margin-bottom: 32px;
}

/* Formulaires */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--gray-border);
    border-radius: 16px;
    font-size: 16px;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--airbnb);
    box-shadow: 0 0 0 3px rgba(255,90,95,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Photos existantes */
.photos-section {
    margin-bottom: 30px;
}

.photos-section h3 {
    font-size: 18px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
}

.photo-item {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    background: var(--gray-bg);
    border: 1px solid var(--gray-border);
}

.photo-item img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.photo-item .principal-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--airbnb);
    color: white;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.photo-item .delete-check {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,0.6);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.photo-item .delete-check:hover {
    background: var(--airbnb);
}

.photo-item .delete-check input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Upload photos */
.photo-upload {
    border: 2px dashed var(--gray-border);
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    background: var(--gray-bg);
    transition: all 0.2s;
    cursor: pointer;
    margin-bottom: 24px;
}

.photo-upload:hover {
    border-color: var(--airbnb);
    background: rgba(255,90,95,0.05);
}

.photo-upload i {
    font-size: 48px;
    color: var(--airbnb);
    margin-bottom: 16px;
}

.photo-upload p {
    margin-bottom: 8px;
    font-size: 14px;
}

.photo-upload small {
    color: var(--text-light);
    font-size: 12px;
}

#file-input {
    display: none;
}

.photos-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
    margin-top: 20px;
}

.preview-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 1;
    background: var(--gray-bg);
}

.preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-item .remove-photo {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,0.6);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

/* Alertes */
.alert {
    padding: 14px 18px;
    border-radius: 16px;
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Boutons */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 32px;
}

.btn-cancel {
    background: white;
    border: 1px solid var(--gray-border);
    padding: 12px 24px;
    border-radius: 40px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-dark);
}

.btn-cancel:hover {
    background: var(--gray-bg);
}

.btn-submit {
    background: var(--airbnb);
    border: none;
    padding: 12px 32px;
    border-radius: 40px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

/* Responsive */
@media (max-width: 768px) {
    nav {
        padding: 12px 20px;
    }
    
    .form-card {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .photos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>
<body>

<nav>
    <a href="index.php" class="logo"><i class="fa-solid fa-house-chimney"></i> Logements</a>
    <a href="hote-dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i> Retour au dashboard</a>
</nav>

<div class="form-container">
    <div class="form-card">
        <h2><i class="fa-solid fa-pen-to-square"></i> Modifier mon logement</h2>
        <p>Mettez à jour les informations de votre annonce</p>
        
        <?php if($success): ?>
            <div class="alert success">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="editForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Titre de l'annonce *</label>
                    <input type="text" name="titre" value="<?php echo htmlspecialchars($logement['titre']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Type de logement *</label>
                    <select name="type" required>
                        <option value="">Sélectionnez</option>
                        <option value="villa" <?php echo $logement['type'] == 'villa' ? 'selected' : ''; ?>>Villa</option>
                        <option value="appartement" <?php echo $logement['type'] == 'appartement' ? 'selected' : ''; ?>>Appartement</option>
                        <option value="studio" <?php echo $logement['type'] == 'studio' ? 'selected' : ''; ?>>Studio</option>
                        <option value="maison" <?php echo $logement['type'] == 'maison' ? 'selected' : ''; ?>>Maison</option>
                        <option value="loft" <?php echo $logement['type'] == 'loft' ? 'selected' : ''; ?>>Loft</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description détaillée *</label>
                <textarea name="description" rows="5" required><?php echo htmlspecialchars($logement['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Région / Ville *</label>
                    <input type="text" name="region_ville" value="<?php echo htmlspecialchars($logement['region_ville']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Adresse exacte</label>
                    <input type="text" name="adresse_exacte" value="<?php echo htmlspecialchars($logement['adresse_exacte'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prix par nuit (TND) *</label>
                    <input type="number" name="prix_nuit" step="10" value="<?php echo $logement['prix_nuit']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Surface (m²)</label>
                    <input type="number" name="surface" value="<?php echo $logement['surface'] ?? ''; ?>" placeholder="Ex: 120">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre de chambres *</label>
                    <input type="number" name="nb_chambres" value="<?php echo $logement['nb_chambres']; ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Nombre de lits *</label>
                    <input type="number" name="nb_lits" value="<?php echo $logement['nb_lits']; ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Salles de bain</label>
                    <input type="number" name="nb_sdb" value="<?php echo $logement['nb_sdb']; ?>" min="1">
                </div>
            </div>
            
            <!-- Photos existantes -->
            <?php if(!empty($photos)): ?>
            <div class="photos-section">
                <h3><i class="fa-solid fa-images"></i> Photos actuelles (<?php echo count($photos); ?>)</h3>
                <div class="photos-grid">
                    <?php foreach($photos as $p): ?>
                    <div class="photo-item">
                        <img src="<?php echo $p['url']; ?>" alt="Photo">
                        <?php if(isset($p['est_principale']) && $p['est_principale'] == 1): ?>
                            <span class="principal-badge"><i class="fa-solid fa-star"></i> Principale</span>
                        <?php endif; ?>
                        <label class="delete-check">
                            <input type="checkbox" name="delete_photos[]" value="<?php echo $p['id_photo']; ?>">
                            <i class="fa-solid fa-trash"></i>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <small style="color: var(--text-light); margin-top: 12px; display: block;">
                    <i class="fa-solid fa-info-circle"></i> Cochez les photos à supprimer
                </small>
            </div>
            <?php endif; ?>
            
            <!-- Ajout de nouvelles photos -->
            <div class="form-group">
                <label>Ajouter des photos</label>
                <div class="photo-upload" onclick="document.getElementById('file-input').click()">
                    <i class="fa-solid fa-cloud-upload-alt"></i>
                    <p>Cliquez pour ajouter des photos</p>
                    <small>PNG, JPG, WEBP jusqu'à 5MB chacun</small>
                </div>
                <input type="file" name="photos[]" id="file-input" accept="image/jpeg,image/png,image/webp" multiple style="display: none" onchange="previewPhotos(this)">
                <div id="photos-preview" class="photos-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="window.location.href='hote-dashboard.php'">Annuler</button>
                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedFiles = [];

function previewPhotos(input) {
    const preview = document.getElementById('photos-preview');
    const files = Array.from(input.files);
    
    files.forEach(file => {
        if (!selectedFiles.some(f => f.name === file.name)) {
            selectedFiles.push(file);
        }
    });
    
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    preview.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Aperçu">
                <button type="button" class="remove-photo" onclick="removePhoto(${index})">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    selectedFiles.splice(index, 1);
    const input = document.getElementById('file-input');
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    previewPhotos(input);
}
</script>

<?php require_once 'includes/footer.php'; ?>