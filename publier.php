<?php
// publier.php - Publier un logement réservé aux propriétaires
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=connectez-vous pour publier un logement');
    exit();
}

$success = false;
$error = '';
$photos_uploaded = [];

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
    
    // Validation des champs requis
    if (empty($titre) || empty($description) || empty($type) || empty($region_ville) || $prix_nuit <= 0) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
// Vérifier que l'utilisateur est bien un propriétaire
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'proprietaire') {
        header('Location: index.php?error=Accès réservé aux propriétaires');
        exit();
    }

    $sql = "SELECT id_hote FROM hote WHERE id_client = :id_client";
    $stmt = Database::execute($sql, [':id_client' => $_SESSION['user_id']]);
    $hote = $stmt->fetch();

    if (!$hote) {
        header('Location: index.php?error=Accès réservé aux propriétaires');
        exit();
    }

    $id_hote = $hote['id_hote'];
        
        // Insérer le logement avec statut actif pour affichage immédiat sur la page d'accueil
        $sql = "INSERT INTO logement (id_hote, titre, description, type, region_ville, prix_nuit, nb_lits, nb_chambres, nb_sdb, surface, adresse_exacte, statut) 
                VALUES (:id_hote, :titre, :description, :type, :region_ville, :prix_nuit, :nb_lits, :nb_chambres, :nb_sdb, :surface, :adresse_exacte, 'actif')";
        
        try {
            Database::execute($sql, [
                ':id_hote' => $id_hote,
                ':titre' => $titre,
                ':description' => $description,
                ':type' => $type,
                ':region_ville' => $region_ville,
                ':prix_nuit' => $prix_nuit,
                ':nb_lits' => $nb_lits,
                ':nb_chambres' => $nb_chambres,
                ':nb_sdb' => $nb_sdb,
                ':surface' => $surface,
                ':adresse_exacte' => $adresse_exacte
            ]);
            
            $id_logement = Database::lastInsertId();
            
            // Traitement des photos
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $upload_dir = 'uploads/logements/';
                
                // Créer le dossier s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['photos']['name'][$key];
                        $file_type = $_FILES['photos']['type'][$key];
                        $file_size = $_FILES['photos']['size'][$key];
                        $file_tmp = $_FILES['photos']['tmp_name'][$key];
                        
                        // Vérifier le type de fichier
                        if (in_array($file_type, $allowed_types)) {
                            // Vérifier la taille
                            if ($file_size <= $max_size) {
                                // Générer un nom unique
                                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                                $new_name = uniqid('photo_') . '_' . time() . '.' . $extension;
                                $destination = $upload_dir . $new_name;
                                
                                // Déplacer le fichier
                                if (move_uploaded_file($file_tmp, $destination)) {
                                    // Insérer dans la base de données
                                    $is_principal = ($key == 0) ? 1 : 0; // La première photo est la principale
                                    if (addPhoto($id_logement, $destination, $is_principal, $id_hote)) {
                                        $photos_uploaded[] = $new_name;
                                    } else {
                                        $error .= "Erreur lors de l'enregistrement de la photo en base: $file_name<br>";
                                    }
                                } else {
                                    $error .= "Erreur lors de l'upload de la photo: $file_name<br>";
                                }
                            } else {
                                $error .= "La photo $file_name dépasse la taille maximale (5MB)<br>";
                            }
                        } else {
                            $error .= "Format non supporté pour $file_name (JPG, PNG, WEBP uniquement)<br>";
                        }
                    }
                }
            }
            
            // Notification à l'admin
            $sql2 = "INSERT INTO notification (id_client, titre, message, type) 
                     VALUES (1, 'Nouveau logement en attente', 'Un nouveau logement a été publié et attend validation', 'systeme')";
            Database::execute($sql2);
            
            $success = true;
        } catch(Exception $e) {
            $error = "Erreur lors de la publication: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publier votre logement - Logements Tunisie</title>
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
    color: var(--text-dark);
}

/* Navbar améliorée */
nav {
    background: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    padding: 16px 40px;
}

.logo {
    font-size: 24px;
    font-weight: 700;
    color: var(--airbnb);
    letter-spacing: -0.5px;
    text-decoration: none;
}

.nav-link {
    font-weight: 500;
    transition: color 0.2s;
    text-decoration: none;
    color: var(--text-dark);
}

.nav-link:hover {
    color: var(--airbnb);
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.nav-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 40px;
    border: 1px solid var(--gray-border);
    transition: all 0.2s;
}

.nav-profile:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--airbnb);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

/* Container principal */
.form-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

/* Étapes modernisées */
.publish-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 48px;
    position: relative;
    gap: 16px;
}

.publish-steps::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 40px;
    right: 40px;
    height: 2px;
    background: var(--gray-border);
    z-index: 0;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
    background: transparent;
}

.step-number {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--gray-border);
    color: var(--text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
    margin: 0 auto 12px;
    transition: all 0.2s;
}

.step.active .step-number {
    background: var(--airbnb);
    border-color: var(--airbnb);
    color: white;
    box-shadow: 0 4px 12px rgba(255,90,95,0.3);
}

.step.completed .step-number {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.step-label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-light);
}

.step.active .step-label {
    color: var(--text-dark);
    font-weight: 600;
}

/* Carte formulaire */
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

/* Champs de formulaire */
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

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Zone d'upload photos */
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

/* Preview des photos */
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
    transition: all 0.2s;
}

.preview-item .remove-photo:hover {
    background: var(--airbnb);
}

.preview-item .principal-badge {
    position: absolute;
    bottom: 4px;
    left: 4px;
    background: var(--airbnb);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 20px;
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

/* Message de succès */
.success-message {
    background: white;
    border-radius: 24px;
    padding: 48px 32px;
    text-align: center;
    box-shadow: 0 8px 28px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-border);
}

.success-message i {
    font-size: 64px;
    color: #10b981;
    margin-bottom: 24px;
}

.success-message h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 16px;
}

.success-message p {
    color: var(--text-light);
    margin-bottom: 8px;
}

.btn-primary {
    background: var(--airbnb);
    color: white;
    padding: 12px 32px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    margin-top: 24px;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

/* Alerte erreur */
.alert.error {
    background: #FEF2F2;
    border: 1px solid #FEE2E2;
    color: #B91C1C;
    padding: 14px 20px;
    border-radius: 16px;
    margin-bottom: 24px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 640px) {
    .form-card {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .publish-steps {
        gap: 8px;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
    
    .step-label {
        font-size: 12px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-cancel, .btn-submit {
        width: 100%;
        text-align: center;
    }
    
    .photos-preview {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
</head>
<body>

<nav>
  <a href="index.php" class="logo"><i class="fa-solid fa-house-chimney"></i> Logements</a>
  <div class="nav-right">
    <a href="index.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <div class="nav-profile" onclick="toggleDropdown()">
      <i class="fa-solid fa-bars"></i>
      <div class="avatar">
        <?php echo strtoupper(substr($_SESSION['user_nom'] ?? 'U', 0, 1)); ?>
      </div>
    </div>
  </div>
</nav>

<div class="form-container">
    <?php if($success): ?>
        <div class="success-message">
            <i class="fa-solid fa-circle-check"></i>
            <h2>Félicitations ! 🎉</h2>
            <p>Votre logement a été publié avec succès.</p>
            <p>Votre annonce est maintenant <strong>visible sur la page d'accueil</strong>.</p>
            <?php if(count($photos_uploaded) > 0): ?>
                <p><strong><?php echo count($photos_uploaded); ?> photo(s)</strong> ont été ajoutées.</p>
            <?php endif; ?>
            <a href="index.php" class="btn-primary">Voir sur la page d'accueil</a>
        </div>
    <?php else: ?>
    
    <div class="publish-steps">
        <div class="step active">
            <div class="step-number">1</div>
            <div class="step-label">Infos générales</div>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <div class="step-label">Photos & équipements</div>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <div class="step-label">Finalisation</div>
        </div>
    </div>

    <div class="form-card">
        <h2><i class="fa-solid fa-home"></i> Publiez votre logement</h2>
        <p>Gagnez de l'argent en partageant votre espace. Remplissez les informations ci-dessous.</p>
        
        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="publishForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Titre de l'annonce *</label>
                    <input type="text" name="titre" placeholder="Ex: Magnifique villa avec piscine" required>
                </div>
                <div class="form-group">
                    <label>Type de logement *</label>
                    <select name="type" required>
                        <option value="">Sélectionnez</option>
                        <option value="villa">Villa</option>
                        <option value="appartement">Appartement</option>
                        <option value="studio">Studio</option>
                        <option value="maison">Maison</option>
                        <option value="loft">Loft</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description détaillée *</label>
                <textarea name="description" rows="5" placeholder="Décrivez votre logement, les équipements, les alentours..." required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Région / Ville *</label>
                    <input type="text" name="region_ville" placeholder="Ex: Hammamet, Nabeul" required>
                </div>
                <div class="form-group">
                    <label>Adresse exacte</label>
                    <input type="text" name="adresse_exacte" placeholder="Rue, numéro, code postal">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prix par nuit (TND) *</label>
                    <input type="number" name="prix_nuit" step="10" placeholder="Ex: 250" required>
                </div>
                <div class="form-group">
                    <label>Surface (m²)</label>
                    <input type="number" name="surface" placeholder="Ex: 120">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre de chambres *</label>
                    <input type="number" name="nb_chambres" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Nombre de lits *</label>
                    <input type="number" name="nb_lits" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Salles de bain</label>
                    <input type="number" name="nb_sdb" value="1" min="1">
                </div>
            </div>
            
            <!-- Section Photos -->
            <div class="form-group">
                <label>Photos du logement</label>
                <div class="photo-upload" onclick="document.getElementById('file-input').click()">
                    <i class="fa-solid fa-cloud-upload-alt"></i>
                    <p>Cliquez pour ajouter des photos</p>
                    <small>PNG, JPG, WEBP jusqu'à 5MB chacun</small>
                </div>
                <input type="file" name="photos[]" id="file-input" accept="image/jpeg,image/png,image/webp" multiple style="display: none" onchange="previewPhotos(this)">
                <div id="photos-preview" class="photos-preview"></div>
                <small style="color: var(--text-light); font-size: 12px;">La première photo sera la photo principale de l'annonce</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Annuler</button>
                <button type="submit" class="btn-submit">Publier mon logement</button>
            </div>
        </form>
    </div>
    
    <div style="margin-top: 24px; text-align: center; color: var(--text-light); font-size: 13px;">
        <i class="fa-solid fa-shield-alt"></i> Votre annonce sera visible immédiatement sur la page d'accueil
    </div>
    <?php endif; ?>
</div>

<script>
let selectedFiles = [];

function previewPhotos(input) {
    const preview = document.getElementById('photos-preview');
    const files = Array.from(input.files);
    
    // Ajouter les nouveaux fichiers
    files.forEach(file => {
        if (!selectedFiles.some(f => f.name === file.name)) {
            selectedFiles.push(file);
        }
    });
    
    // Mettre à jour l'input avec tous les fichiers
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    // Afficher les previews
    preview.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Photo ${index + 1}">
                <button type="button" class="remove-photo" onclick="removePhoto(${index})">
                    <i class="fa-solid fa-times"></i>
                </button>
                ${index === 0 ? '<div class="principal-badge">Principale</div>' : ''}
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    selectedFiles.splice(index, 1);
    
    // Mettre à jour l'input
    const input = document.getElementById('file-input');
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    // Rafraîchir les previews
    previewPhotos(input);
}

function toggleDropdown() {
    document.getElementById('dropdown')?.classList.toggle('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>