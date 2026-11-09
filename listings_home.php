<?php
/**
 * Liste JSON des logements pour la page d'accueil (filtre catégorie sans rechargement).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

$filtres = [];
if ($type !== '' && $type !== 'all') {
    if (in_array($type, ['mer', 'montagne', 'luxe'], true)) {
        $filtres['theme'] = $type;
    } elseif (in_array($type, ['villa', 'appartement', 'studio', 'maison', 'loft', 'autre'], true)) {
        $filtres['type'] = $type;
    } else {
        echo json_encode(['ok' => true, 'logements' => []]);
        exit;
    }
}

$logements = getLogements($filtres, 100);

if (empty($logements)) {
    echo json_encode(['ok' => true, 'logements' => []]);
    exit;
}

$ids = array_map(static function ($row) {
    return (int) $row['id_logement'];
}, $logements);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id_logement, url, est_principale FROM photo WHERE id_logement IN ($placeholders) ORDER BY id_logement, est_principale DESC";
$stmt = Database::execute($sql, $ids);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$photoByLogement = [];
foreach ($rows as $row) {
    $id = (int) $row['id_logement'];
    if (!isset($photoByLogement[$id])) {
        $photoByLogement[$id] = $row['url'];
    }
}

$defaultImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
$out = [];
foreach ($logements as $l) {
    $id = (int) $l['id_logement'];
    $out[] = [
        'id_logement' => $id,
        'titre' => $l['titre'],
        'type' => $l['type'] ?? '',
        'region_ville' => $l['region_ville'],
        'prix_nuit' => (float) $l['prix_nuit'],
        'image_url' => $photoByLogement[$id] ?? $defaultImg,
    ];
}

echo json_encode(['ok' => true, 'logements' => $out], JSON_UNESCAPED_UNICODE);
