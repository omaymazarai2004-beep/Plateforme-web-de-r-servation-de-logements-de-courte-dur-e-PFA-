<?php
// ajax/get_inspirations.php
require_once '../includes/header.php';

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 4;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    // Récupérer les destinations populaires depuis la base de données
    $sql = "SELECT 
                l.ville,
                MIN(l.prix_nuit) as prix_min,
                COUNT(DISTINCT l.id_logement) as nb_logements,
                AVG(r.note) as note_moyenne
            FROM logements l
            LEFT JOIN reservations res ON l.id_logement = res.id_logement
            LEFT JOIN avis r ON l.id_logement = r.id_logement
            WHERE l.statut = 'actif'";
    
    $params = [];
    
    if($type) {
        $sql .= " AND l.type_logement = :type";
        $params[':type'] = $type;
    }
    
    $sql .= " GROUP BY l.ville
              HAVING COUNT(DISTINCT l.id_logement) > 0
              ORDER BY nb_logements DESC, note_moyenne DESC
              LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter les URLs des images
    $imageMap = [
        'Hammamet' => 'https://images.unsplash.com/photo-1581276875532-9f2e498f47f2?w=400&q=80',
        'Sidi Bou Said' => 'https://images.unsplash.com/photo-1539020140153-e479b8c22e70?w=400&q=80',
        'Djerba' => 'https://images.unsplash.com/photo-1581428982868-4101eeb73a4f?w=400&q=80',
        'Tozeur' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&q=80',
        'Tunis' => 'https://images.unsplash.com/photo-1549563514-099d4c9e74f0?w=400&q=80',
        'Sousse' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=400&q=80',
        'Monastir' => 'https://images.unsplash.com/photo-1590077428593-a55c8427b2ac?w=400&q=80',
        'Nabeul' => 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=400&q=80'
    ];
    
    foreach($destinations as &$dest) {
        $dest['image'] = $imageMap[$dest['ville']] ?? 
                        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80';
    }
    
    echo json_encode([
        'success' => true,
        'destinations' => $destinations
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}