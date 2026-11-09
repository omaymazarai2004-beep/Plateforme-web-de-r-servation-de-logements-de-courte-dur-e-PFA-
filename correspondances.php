<?php
// correspondances.php - Messagerie moderne (CORRIGÉ)
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

// Envoyer un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $destinataire = trim($_POST['destinataire'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($destinataire) || empty($sujet) || empty($message)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        // Vérifier si le destinataire existe
        $sql = "SELECT id_client, prenom, nom FROM utilisateur WHERE email = :email";
        $stmt = Database::execute($sql, [':email' => $destinataire]);
        $dest = $stmt->fetch();
        
        if ($dest) {
            // Vérifier qu'on n'envoie pas un message à soi-même
            if ($dest['id_client'] == $id_client) {
                $error = "Vous ne pouvez pas vous envoyer un message à vous-même";
            } else {
                $sql2 = "INSERT INTO correspondance (id_expediteur, id_destinataire, sujet, message) 
                         VALUES (:id_exp, :id_dest, :sujet, :message)";
                Database::execute($sql2, [
                    ':id_exp' => $id_client,
                    ':id_dest' => $dest['id_client'],
                    ':sujet' => $sujet,
                    ':message' => $message
                ]);
                $success = "Message envoyé avec succès !";
            }
        } else {
            $error = "Destinataire non trouvé. Vérifiez l'adresse email.";
        }
    }
}

// Récupérer les correspondances - CORRECTION ICI
// Le problème était que :id_client était utilisé deux fois avec des alias différents
$sql = "SELECT 
            c.*, 
            CONCAT(exp.prenom, ' ', exp.nom) as expediteur_nom,
            CONCAT(dest.prenom, ' ', dest.nom) as destinataire_nom,
            exp.email as expediteur_email,
            dest.email as destinataire_email
        FROM correspondance c
        JOIN utilisateur exp ON c.id_expediteur = exp.id_client
        JOIN utilisateur dest ON c.id_destinataire = dest.id_client
        WHERE c.id_expediteur = :id_client_exp OR c.id_destinataire = :id_client_dest
        ORDER BY c.date_envoi DESC";

$stmt = Database::execute($sql, [
    ':id_client_exp' => $id_client,
    ':id_client_dest' => $id_client
]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messagerie - Logements Tunisie</title>
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
}

.messaging-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px;
}

.messaging-header {
    margin-bottom: 32px;
}

.messaging-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.messaging-header h1 i {
    color: var(--airbnb);
    margin-right: 10px;
}

.messaging-header p {
    color: var(--text-light);
    font-size: 16px;
}

.messaging-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 32px;
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-border);
}

/* Sidebar Nouveau message */
.new-message-sidebar {
    background: white;
    padding: 28px;
    border-right: 1px solid var(--gray-border);
}

.new-message-sidebar h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-dark);
}

.new-message-sidebar h3 i {
    color: var(--airbnb);
    font-size: 22px;
}

.form-group {
    margin-bottom: 20px;
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
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray-border);
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--airbnb);
    box-shadow: 0 0 0 3px rgba(255,90,95,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-send {
    width: 100%;
    background: var(--airbnb);
    color: white;
    border: none;
    padding: 14px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-send:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
}

.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
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
    font-size: 16px;
}

/* Messages list */
.messages-list {
    padding: 28px;
    max-height: 70vh;
    overflow-y: auto;
}

.messages-list h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-dark);
    position: sticky;
    top: 0;
    background: white;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--gray-border);
}

.messages-list h3 i {
    color: var(--airbnb);
}

.empty-messages {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
}

.empty-messages i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-messages h4 {
    font-size: 18px;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.message-card {
    background: var(--gray-bg);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s;
    border: 1px solid transparent;
    cursor: pointer;
}

.message-card:hover {
    border-color: var(--gray-border);
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}

.message-sender {
    display: flex;
    align-items: center;
    gap: 10px;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.message-sender-name {
    font-weight: 600;
    color: var(--text-dark);
}

.message-direction {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.message-direction.sent {
    background: #e0e7ff;
    color: #3730a3;
}

.message-direction.sent i {
    margin-right: 4px;
}

.message-direction.received {
    background: #d1fae5;
    color: #065f46;
}

.message-direction.received i {
    margin-right: 4px;
}

.message-date {
    font-size: 12px;
    color: var(--text-light);
}

.message-subject {
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--text-dark);
    font-size: 15px;
}

.message-subject i {
    margin-right: 6px;
    color: var(--airbnb);
}

.message-body {
    color: var(--text-light);
    font-size: 14px;
    line-height: 1.5;
}

.message-body-preview {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Scrollbar personnalisée */
.messages-list::-webkit-scrollbar {
    width: 6px;
}

.messages-list::-webkit-scrollbar-track {
    background: var(--gray-bg);
    border-radius: 10px;
}

.messages-list::-webkit-scrollbar-thumb {
    background: var(--gray-border);
    border-radius: 10px;
}

.messages-list::-webkit-scrollbar-thumb:hover {
    background: var(--airbnb-light);
}

/* Responsive */
@media (max-width: 900px) {
    .messaging-grid {
        grid-template-columns: 1fr;
    }
    
    .new-message-sidebar {
        border-right: none;
        border-bottom: 1px solid var(--gray-border);
    }
    
    .messaging-container {
        padding: 24px 16px;
    }
    
    .messaging-header h1 {
        font-size: 28px;
    }
}

@media (max-width: 480px) {
    .message-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .new-message-sidebar {
        padding: 20px;
    }
    
    .messages-list {
        padding: 20px;
    }
}
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="messaging-container">
    <div class="messaging-header">
        <h1><i class="fa-regular fa-message"></i> Messagerie</h1>
        <p>Échangez avec les propriétaires et les voyageurs</p>
    </div>
    
    <div class="messaging-grid">
        <!-- Sidebar Nouveau message -->
        <div class="new-message-sidebar">
            <h3><i class="fa-solid fa-pen"></i> Nouveau message</h3>
            
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
            
            <form method="POST" id="messageForm">
                <div class="form-group">
                    <label><i class="fa-regular fa-envelope"></i> Destinataire (email)</label>
                    <input type="email" name="destinataire" placeholder="exemple@email.com" required 
                           value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fa-regular fa-tag"></i> Sujet</label>
                    <input type="text" name="sujet" placeholder="Question sur votre logement..." required>
                </div>
                <div class="form-group">
                    <label><i class="fa-regular fa-comment"></i> Message</label>
                    <textarea name="message" placeholder="Bonjour, je suis intéressé par..." required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn-send">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer
                </button>
            </form>
        </div>
        
        <!-- Liste des messages -->
        <div class="messages-list">
            <h3><i class="fa-solid fa-inbox"></i> Tous les messages</h3>
            
            <?php if(empty($messages)): ?>
                <div class="empty-messages">
                    <i class="fa-regular fa-envelope-open"></i>
                    <h4>Aucun message</h4>
                    <p>Commencez une conversation avec un propriétaire ou un voyageur</p>
                </div>
            <?php else: ?>
                <?php foreach($messages as $m): 
                    $isSent = ($m['id_expediteur'] == $id_client);
                    $otherName = $isSent ? $m['destinataire_nom'] : $m['expediteur_nom'];
                    $otherInitial = strtoupper(substr($otherName, 0, 1));
                    $avatarColors = ['#FF5A5F', '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec489a'];
                    $colorIndex = abs(ord($otherInitial) - 65) % count($avatarColors);
                ?>
                <div class="message-card" onclick="viewMessage(<?php echo $m['id_correspondance']; ?>)">
                    <div class="message-header">
                        <div class="message-sender">
                            <div class="message-avatar" style="background: <?php echo $avatarColors[$colorIndex]; ?>;">
                                <?php echo $otherInitial; ?>
                            </div>
                            <div>
                                <span class="message-sender-name"><?php echo htmlspecialchars($otherName); ?></span>
                                <span class="message-direction <?php echo $isSent ? 'sent' : 'received'; ?>">
                                    <?php if($isSent): ?>
                                        <i class="fa-solid fa-arrow-right"></i> Envoyé
                                    <?php else: ?>
                                        <i class="fa-solid fa-arrow-left"></i> Reçu
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="message-date">
                            <?php 
                                $date = new DateTime($m['date_envoi']);
                                $now = new DateTime();
                                $diff = $now->diff($date);
                                
                                if($diff->days == 0) {
                                    echo "Aujourd'hui à " . $date->format('H:i');
                                } elseif($diff->days == 1) {
                                    echo "Hier à " . $date->format('H:i');
                                } else {
                                    echo $date->format('d/m/Y H:i');
                                }
                            ?>
                        </div>
                    </div>
                    <div class="message-subject">
                        <i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($m['sujet']); ?>
                    </div>
                    <div class="message-body message-body-preview">
                        <?php echo nl2br(htmlspecialchars(substr($m['message'], 0, 150))); ?>
                        <?php if(strlen($m['message']) > 150): ?>...<?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function viewMessage(id) {
    // Rediriger vers la page de détails du message
    window.location.href = 'message-detail.php?id=' + id;
}

// Auto-reset du formulaire après envoi réussi
<?php if($success): ?>
setTimeout(function() {
    document.getElementById('messageForm').reset();
}, 2000);
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>