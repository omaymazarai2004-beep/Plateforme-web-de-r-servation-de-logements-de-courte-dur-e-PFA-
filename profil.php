<?php
// profil.php - Gestion du profil utilisateur
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$id_client = $_SESSION['user_id'];
$success = '';
$error = '';

// Récupérer les infos utilisateur
$sql = "SELECT * FROM utilisateur WHERE id_client = :id_client";
$stmt = Database::execute($sql, [':id_client' => $id_client]);
$user = $stmt->fetch();

// Mettre à jour le profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    
    if (empty($errors)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateur SET nom = :nom, prenom = :prenom, telephone = :telephone, ville = :ville, mot_de_passe = :password WHERE id_client = :id_client";
            Database::execute($sql, [
                ':nom' => $nom, ':prenom' => $prenom, ':telephone' => $telephone,
                ':ville' => $ville, ':password' => $hashed, ':id_client' => $id_client
            ]);
        } else {
            $sql = "UPDATE utilisateur SET nom = :nom, prenom = :prenom, telephone = :telephone, ville = :ville WHERE id_client = :id_client";
            Database::execute($sql, [
                ':nom' => $nom, ':prenom' => $prenom, ':telephone' => $telephone,
                ':ville' => $ville, ':id_client' => $id_client
            ]);
        }
        
        $_SESSION['user_nom'] = $prenom . ' ' . $nom;
        $success = "Profil mis à jour avec succès !";
        
        // Recharger les données
        $stmt = Database::execute("SELECT * FROM utilisateur WHERE id_client = :id_client", [':id_client' => $id_client]);
        $user = $stmt->fetch();
    } else {
        $error = implode(", ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon profil - Logements Tunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root {
    --airbnb: #FF5A5F;
    --airbnb-dark: #E00B41;
    --gray-bg: #F7F7F7;
    --gray-border: #DDDDDD;
    --text-dark: #222222;
    --text-light: #717171;
}

body {
    background: var(--gray-bg);
}

.profile-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 40px 24px;
}

.profile-header {
    text-align: center;
    margin-bottom: 32px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--airbnb), var(--airbnb-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: 0 8px 20px rgba(255,90,95,0.3);
}

.profile-avatar span {
    font-size: 40px;
    font-weight: 600;
    color: white;
}

.profile-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 4px;
}

.profile-header p {
    color: var(--text-light);
}

.form-card {
    background: white;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-border);
}

.form-card h2 {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-dark);
}

.form-card h2 i {
    color: var(--airbnb);
}

.form-card > p {
    color: var(--text-light);
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-border);
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--text-dark);
    font-size: 14px;
}

.form-group label i {
    margin-right: 6px;
    color: var(--airbnb);
    width: 20px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--gray-border);
    border-radius: 16px;
    font-size: 15px;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus {
    outline: none;
    border-color: var(--airbnb);
    box-shadow: 0 0 0 3px rgba(255,90,95,0.1);
}

.form-group input:disabled {
    background: var(--gray-bg);
    color: var(--text-light);
    cursor: not-allowed;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

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

.alert i {
    font-size: 18px;
}

.form-actions {
    margin-top: 32px;
    display: flex;
    justify-content: flex-end;
}

.btn-submit {
    background: var(--airbnb);
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-submit:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

.info-note {
    margin-top: 24px;
    padding: 16px;
    background: var(--gray-bg);
    border-radius: 16px;
    font-size: 13px;
    color: var(--text-light);
    text-align: center;
}

.info-note i {
    margin-right: 6px;
    color: var(--airbnb);
}

/* Responsive */
@media (max-width: 640px) {
    .profile-container {
        padding: 24px 16px;
    }
    
    .form-card {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-avatar span {
        font-size: 32px;
    }
    
    .profile-header h1 {
        font-size: 24px;
    }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <span><?php echo strtoupper(substr($user['prenom'] ?? 'U', 0, 1)); ?></span>
        </div>
        <h1><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h1>
        <p>Membre depuis <?php echo date('F Y', strtotime($user['date_inscription'] ?? 'now')); ?></p>
    </div>
    
    <div class="form-card">
        <h2><i class="fa-regular fa-user"></i> Informations personnelles</h2>
        <p>Gérez vos données et préférences de compte</p>
        
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
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-regular fa-user"></i> Prénom</label>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required placeholder="Votre prénom">
                </div>
                <div class="form-group">
                    <label><i class="fa-regular fa-user"></i> Nom</label>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required placeholder="Votre nom">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fa-regular fa-envelope"></i> Adresse email</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                <small style="color: var(--text-light); font-size: 12px;">L'adresse email ne peut pas être modifiée</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <input type="tel" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="+216 XX XXX XXX">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-location-dot"></i> Ville</label>
                    <input type="text" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>" placeholder="Tunis, Sousse, Sfax...">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Nouveau mot de passe</label>
                <input type="password" name="password" placeholder="Laissez vide pour ne pas modifier" autocomplete="new-password">
                <small style="color: var(--text-light); font-size: 12px;">Minimum 8 caractères</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fa-regular fa-floppy-disk"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
        
        <div class="info-note">
            <i class="fa-solid fa-shield-alt"></i> Vos informations sont sécurisées et ne seront jamais partagées sans votre consentement.
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>