<?php
// correspondance.php - Critères de logement pour locataire
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    header('Location: index.php');
    exit();
}

$ville = trim($_GET['ville'] ?? '');
$type = $_GET['type'] ?? '';
$theme = $_GET['theme'] ?? '';
$budget_max = trim($_GET['budget_max'] ?? '');
$nb_chambres = trim($_GET['nb_chambres'] ?? '');
$nb_voyageurs = trim($_GET['nb_voyageurs'] ?? '');
$date_deb = $_GET['date_deb'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$mot_cle = trim($_GET['mot_cle'] ?? '');

$logements = [];
$searchPerformed = false;
$resultsCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
    $ville !== '' || $type !== '' || $theme !== '' || $budget_max !== '' || $nb_chambres !== '' ||
    $nb_voyageurs !== '' || $date_deb !== '' || $date_fin !== '' || $mot_cle !== ''
)) {
    $searchPerformed = true;
    $sql = "SELECT l.*, c.nom as hote_nom, c.prenom as hote_prenom, l.description as description
            FROM logement l
            JOIN hote h ON l.id_hote = h.id_hote
            JOIN utilisateur c ON h.id_client = c.id_client
            WHERE l.statut = 'actif'";
    $params = [];

    if ($ville !== '') {
        $sql .= " AND l.region_ville LIKE :ville";
        $params[':ville'] = '%' . $ville . '%';
    }

    if ($type !== '') {
        $sql .= " AND l.type = :type";
        $params[':type'] = $type;
    }

    if ($theme !== '') {
        switch ($theme) {
            case 'mer':
                $sql .= " AND (
                    l.region_ville LIKE '%Nabeul%' OR l.region_ville LIKE '%Mahdia%' OR l.region_ville LIKE '%Hammamet%' OR l.region_ville LIKE '%Gammarth%' OR l.region_ville LIKE '%Sousse%' OR l.region_ville LIKE '%Monastir%' OR l.region_ville LIKE '%Bizerte%' OR l.region_ville LIKE '%Kerkennah%' OR l.region_ville LIKE '%Djerba%' OR LOWER(l.description) LIKE '%bord de mer%' OR LOWER(l.titre) LIKE '%bord de mer%'
                )";
                break;
            case 'montagne':
                $sql .= " AND (
                    l.region_ville LIKE '%Kef%' OR l.region_ville LIKE '%Jendouba%' OR l.region_ville LIKE '%Siliana%' OR l.region_ville LIKE '%Zaghouan%' OR LOWER(l.description) LIKE '%montagne%' OR LOWER(l.titre) LIKE '%montagne%'
                )";
                break;
            case 'luxe':
                $sql .= " AND l.prix_nuit >= 200";
                break;
        }
    }

    if ($budget_max !== '' && is_numeric($budget_max)) {
        $sql .= " AND l.prix_nuit <= :prix_max";
        $params[':prix_max'] = $budget_max;
    }

    if ($nb_chambres !== '' && is_numeric($nb_chambres)) {
        $sql .= " AND l.nb_chambres >= :nb_chambres";
        $params[':nb_chambres'] = $nb_chambres;
    }

    if ($nb_voyageurs !== '' && is_numeric($nb_voyageurs)) {
        $sql .= " AND l.nb_lits >= :nb_voyageurs";
        $params[':nb_voyageurs'] = $nb_voyageurs;
    }

    if ($mot_cle !== '') {
        $sql .= " AND (LOWER(l.titre) LIKE :mot_cle OR LOWER(l.description) LIKE :mot_cle OR LOWER(l.region_ville) LIKE :mot_cle)";
        $params[':mot_cle'] = '%' . strtolower($mot_cle) . '%';
    }

    $sql .= " ORDER BY l.date_creation DESC LIMIT 36";
    $stmt = Database::execute($sql, $params);
    $logements = $stmt->fetchAll();
    $resultsCount = count($logements);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Correspondance - Critères de logement</title>
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
body { background: var(--gray-bg); }
.criteria-container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }
.criteria-header { margin-bottom: 30px; }
.criteria-header h1 { font-family: 'Playfair Display', serif; font-size: 38px; color: var(--text-dark); margin-bottom: 10px; }
.criteria-header p { color: var(--text-light); font-size: 16px; }
.criteria-grid { display: grid; grid-template-columns: 380px 1fr; gap: 28px; }
.criteria-sidebar { background: white; border: 1px solid var(--gray-border); border-radius: 24px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.06); }
.criteria-sidebar h2 { font-size: 22px; margin-bottom: 20px; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 16px; border: 1px solid var(--gray-border); border-radius: 14px; font-size: 15px; background: #fff; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--airbnb); outline: none; box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.12); }
.form-group textarea { min-height: 120px; resize: vertical; }
.btn-search { width: 100%; background: var(--airbnb); color: white; border: none; border-radius: 14px; padding: 16px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, background 0.2s ease; }
.btn-search:hover { transform: translateY(-1px); background: var(--airbnb-dark); }
.search-results { display: grid; gap: 20px; }
.result-summary { margin-bottom: 20px; padding: 20px 24px; background: white; border: 1px solid var(--gray-border); border-radius: 20px; color: var(--text-dark); font-weight: 600; }
.result-card { display: grid; grid-template-columns: 220px 1fr; gap: 20px; background: white; border-radius: 24px; border: 1px solid var(--gray-border); overflow: hidden; box-shadow: 0 12px 32px rgba(0,0,0,0.05); }
.result-card img { width: 100%; height: 100%; object-fit: cover; }
.result-card-body { padding: 22px; display: flex; flex-direction: column; justify-content: space-between; }
.result-card-title { font-size: 20px; font-weight: 700; margin-bottom: 10px; color: var(--text-dark); }
.result-card-meta { color: var(--text-light); font-size: 14px; margin-bottom: 16px; }
.result-card-tags { display: flex; flex-wrap: wrap; gap: 10px; }
.tag { background: #f5f5f5; color: var(--text-dark); padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; }
.result-card-description { color: var(--text-light); font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
.result-card a { align-self: flex-start; text-decoration: none; color: white; background: var(--airbnb); padding: 12px 20px; border-radius: 999px; font-weight: 600; transition: background 0.2s ease; }
.result-card a:hover { background: var(--airbnb-dark); }
.empty-search { background: white; border: 1px solid var(--gray-border); border-radius: 24px; padding: 40px 30px; text-align: center; color: var(--text-light); }
.empty-search h3 { margin-bottom: 12px; color: var(--text-dark); }
@media (max-width: 980px) { .criteria-grid { grid-template-columns: 1fr; } .result-card { grid-template-columns: 1fr; } .result-card img { height: 220px; } }
@media (max-width: 640px) { .criteria-container { padding: 24px 16px; } .criteria-header h1 { font-size: 32px; } }
</style>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="criteria-container">
    <div class="criteria-header">
        <h1><i class="fa-solid fa-sliders"></i> Correspondance</h1>
        <p>Choisissez vos critères de logement pour trouver la location idéale.</p>
    </div>

    <div class="criteria-grid">
        <div class="criteria-sidebar">
            <h2><i class="fa-solid fa-filter"></i> Critères de recherche</h2>
            <form method="GET" action="correspondance.php">
                <div class="form-group">
                    <label>Destination</label>
                    <input type="text" name="ville" value="<?php echo htmlspecialchars($ville); ?>" placeholder="Ex : Tunis, Sousse, Djerba">
                </div>
                <div class="form-group">
                    <label>Type de logement</label>
                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="appartement" <?php echo $type === 'appartement' ? 'selected' : ''; ?>>Appartement</option>
                        <option value="villa" <?php echo $type === 'villa' ? 'selected' : ''; ?>>Villa</option>
                        <option value="maison" <?php echo $type === 'maison' ? 'selected' : ''; ?>>Maison</option>
                        <option value="studio" <?php echo $type === 'studio' ? 'selected' : ''; ?>>Studio</option>
                        <option value="loft" <?php echo $type === 'loft' ? 'selected' : ''; ?>>Loft</option>
                        <option value="autre" <?php echo $type === 'autre' ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ambiance</label>
                    <select name="theme">
                        <option value="">Aucun filtre</option>
                        <option value="mer" <?php echo $theme === 'mer' ? 'selected' : ''; ?>>Mer</option>
                        <option value="montagne" <?php echo $theme === 'montagne' ? 'selected' : ''; ?>>Montagne</option>
                        <option value="luxe" <?php echo $theme === 'luxe' ? 'selected' : ''; ?>>Luxe</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Budget maximum (TND)</label>
                    <input type="number" name="budget_max" min="0" value="<?php echo htmlspecialchars($budget_max); ?>" placeholder="Ex : 500">
                </div>
                <div class="form-group">
                    <label>Nombre de chambres</label>
                    <input type="number" name="nb_chambres" min="1" value="<?php echo htmlspecialchars($nb_chambres); ?>" placeholder="Ex : 2">
                </div>
                <div class="form-group">
                    <label>Nombre de voyageurs</label>
                    <input type="number" name="nb_voyageurs" min="1" value="<?php echo htmlspecialchars($nb_voyageurs); ?>" placeholder="Ex : 4">
                </div>
                <div class="form-group">
                    <label>Date d'arrivée</label>
                    <input type="date" name="date_deb" value="<?php echo htmlspecialchars($date_deb); ?>">
                </div>
                <div class="form-group">
                    <label>Date de départ</label>
                    <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                </div>
                <div class="form-group">
                    <label>Mot-clé de recherche</label>
                    <input type="text" name="mot_cle" value="<?php echo htmlspecialchars($mot_cle); ?>" placeholder="Ex : piscine, vue mer, proche centre">
                </div>
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
            </form>
        </div>

        <div class="search-results">
            <?php if($searchPerformed): ?>
                <div class="result-summary">
                    <?php if($resultsCount > 0): ?>
                        <?php echo $resultsCount; ?> logement<?php echo $resultsCount > 1 ? 's' : ''; ?> trouvé<?php echo $resultsCount > 1 ? 's' : ''; ?> pour vos critères.
                    <?php else: ?>
                        Aucun logement ne correspond à ces critères pour le moment.
                    <?php endif; ?>
                </div>
                <?php if(empty($logements)): ?>
                    <div class="empty-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h3>Aucun résultat</h3>
                        <p>Essayez un budget plus élevé, d'autres dates ou une destination différente.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($logements as $l):
                        $sqlPhoto = "SELECT url FROM photo WHERE id_logement = :id_logement AND est_principale = 1 LIMIT 1";
                        $stmtPhoto = Database::execute($sqlPhoto, [':id_logement' => $l['id_logement']]);
                        $photo = $stmtPhoto->fetch();
                        if (!$photo) {
                            $sqlPhoto = "SELECT url FROM photo WHERE id_logement = :id_logement LIMIT 1";
                            $stmtPhoto = Database::execute($sqlPhoto, [':id_logement' => $l['id_logement']]);
                            $photo = $stmtPhoto->fetch();
                        }
                        $imageUrl = $photo ? $photo['url'] : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
                    ?>
                    <div class="result-card">
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($l['titre']); ?>">
                        <div class="result-card-body">
                            <div>
                                <div class="result-card-title"><?php echo htmlspecialchars($l['titre']); ?></div>
                                <div class="result-card-meta"><?php echo htmlspecialchars($l['region_ville']); ?> • <?php echo typeLogementLabel($l['type']); ?></div>
                                <div class="result-card-tags">
                                    <span class="tag"><?php echo htmlspecialchars($l['nb_chambres']); ?> chambres</span>
                                    <span class="tag"><?php echo htmlspecialchars($l['nb_lits']); ?> lits</span>
                                    <span class="tag"><?php echo number_format($l['prix_nuit'], 0); ?> TND / nuit</span>
                                </div>
                                <p class="result-card-description"><?php echo nl2br(htmlspecialchars(substr($l['description'], 0, 160))); ?><?php echo strlen($l['description']) > 160 ? '...' : ''; ?></p>
                            </div>
                            <a href="logement-detail.php?id=<?php echo $l['id_logement']; ?>">Voir le logement</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-search">
                    <i class="fa-solid fa-info-circle"></i>
                    <h3>Choisissez vos critères</h3>
                    <p>Saisissez vos attentes et lancez la recherche pour trouver le logement qui vous convient.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
