<?php
// includes/functions.php - VERSION CORRIGÉE
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isHote() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'proprietaire' || $_SESSION['user_role'] === 'admin');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Libellé français pour le type de logement (enum base).
 */
function typeLogementLabel($type) {
    if ($type === null || $type === '') {
        return 'Logement';
    }
    $map = [
        'villa' => 'Villa',
        'appartement' => 'Appartement',
        'studio' => 'Studio',
        'maison' => 'Maison',
        'loft' => 'Loft',
        'autre' => 'Autre',
    ];
    $k = strtolower((string) $type);
    return isset($map[$k]) ? $map[$k] : ucfirst($k);
}

function getLogements($filtres = [], $limit = 12, $offset = 0) {
    $sql = "SELECT l.*, c.nom as hote_nom, c.prenom as hote_prenom,
            4.5 as note_moyenne,
            (SELECT COUNT(*) FROM reservation WHERE id_logement = l.id_logement AND statut = 'terminee') as nb_reservations
            FROM logement l
            JOIN hote h ON l.id_hote = h.id_hote
            JOIN utilisateur c ON h.id_client = c.id_client
            WHERE l.statut = 'actif'";
    
    $params = [];
    
    if (!empty($filtres['type'])) {
        $sql .= " AND l.type = :type";
        $params[':type'] = $filtres['type'];
    }

    if (!empty($filtres['theme'])) {
        switch ($filtres['theme']) {
            case 'mer':
                $sql .= " AND (
                    l.region_ville LIKE '%Nabeul%' OR l.region_ville LIKE '%Mahdia%' OR l.region_ville LIKE '%Hammamet%'
                    OR l.region_ville LIKE '%Gammarth%' OR l.region_ville LIKE '%Sousse%' OR l.region_ville LIKE '%Monastir%'
                    OR l.region_ville LIKE '%Bizerte%' OR l.region_ville LIKE '%Kerkennah%' OR l.region_ville LIKE '%Djerba%'
                    OR LOWER(l.description) LIKE '%bord de mer%' OR LOWER(l.titre) LIKE '%bord de mer%'
                )";
                break;
            case 'montagne':
                $sql .= " AND (
                    l.region_ville LIKE '%Kef%' OR l.region_ville LIKE '%Jendouba%' OR l.region_ville LIKE '%Siliana%'
                    OR l.region_ville LIKE '%Zaghouan%' OR l.region_ville LIKE '%Aïn Draham%' OR l.region_ville LIKE '%Ain Draham%'
                    OR LOWER(l.description) LIKE '%montagne%' OR LOWER(l.titre) LIKE '%montagne%'
                )";
                break;
            case 'luxe':
                $sql .= " AND l.prix_nuit >= 200";
                break;
        }
    }
    
    if (!empty($filtres['ville'])) {
        $sql .= " AND l.region_ville LIKE :ville";
        $params[':ville'] = '%' . $filtres['ville'] . '%';
    }
    
    if (!empty($filtres['prix_max'])) {
        $sql .= " AND l.prix_nuit <= :prix_max";
        $params[':prix_max'] = $filtres['prix_max'];
    }
    
    $sql .= " ORDER BY l.date_creation DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = Database::execute($sql, $params);
    return $stmt->fetchAll();
}

function getLogementById($id) {
    $sql = "SELECT l.*, c.nom as hote_nom, c.prenom as hote_prenom, c.email as hote_email,
            h.id_hote, h.statut_hote
            FROM logement l
            JOIN hote h ON l.id_hote = h.id_hote
            JOIN utilisateur c ON h.id_client = c.id_client
            WHERE l.id_logement = :id";
    
    $stmt = Database::execute($sql, [':id' => $id]);
    return $stmt->fetch();
}

/**
 * Récupère les photos d'un logement
 */
function getPhotosByLogement($id_logement) {
    try {
        // Vérifier d'abord la structure de la table photo
        $columns = Database::execute("SHOW COLUMNS FROM photo")->fetchAll();
        $has_id_logement = false;
        $has_id_hote = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] == 'id_logement') $has_id_logement = true;
            if ($col['Field'] == 'id_hote') $has_id_hote = true;
        }
        
        // Chercher les photos par id_logement ou id_hote
        if ($has_id_logement) {
            $sql = "SELECT * FROM photo WHERE id_logement = :id_logement ORDER BY est_principale DESC";
            $stmt = Database::execute($sql, [':id_logement' => $id_logement]);
            return $stmt->fetchAll();
        } elseif ($has_id_hote) {
            // Récupérer l'id_hote à partir du logement
            $sql_hote = "SELECT id_hote FROM logement WHERE id_logement = :id_logement";
            $stmt_hote = Database::execute($sql_hote, [':id_logement' => $id_logement]);
            $logement = $stmt_hote->fetch();
            
            if ($logement) {
                $sql = "SELECT * FROM photo WHERE id_hote = :id_hote";
                $stmt = Database::execute($sql, [':id_hote' => $logement['id_hote']]);
                return $stmt->fetchAll();
            }
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ajoute une photo à un logement
 */
function addPhoto($id_logement, $url, $est_principale = 0, $id_hote = null) {
    try {
        // Vérifier la structure de la table photo
        $columns = Database::execute("SHOW COLUMNS FROM photo")->fetchAll();
        $has_id_logement = false;
        $has_id_hote = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] == 'id_logement') $has_id_logement = true;
            if ($col['Field'] == 'id_hote') $has_id_hote = true;
        }
        
        if ($has_id_logement && $has_id_hote) {
            if ($id_hote === null) {
                $sql_hote = "SELECT id_hote FROM logement WHERE id_logement = :id_logement";
                $stmt_hote = Database::execute($sql_hote, [':id_logement' => $id_logement]);
                $logement = $stmt_hote->fetch();
                if (!$logement) {
                    return false;
                }
                $id_hote = $logement['id_hote'];
            }

            $sql = "INSERT INTO photo (id_logement, id_hote, url, est_principale) VALUES (:id_logement, :id_hote, :url, :est_principale)";
            Database::execute($sql, [
                ':id_logement' => $id_logement,
                ':id_hote' => $id_hote,
                ':url' => $url,
                ':est_principale' => $est_principale
            ]);
            return true;
        }

        if ($has_id_logement) {
            $sql = "INSERT INTO photo (id_logement, url, est_principale) VALUES (:id_logement, :url, :est_principale)";
            Database::execute($sql, [
                ':id_logement' => $id_logement,
                ':url' => $url,
                ':est_principale' => $est_principale
            ]);
            return true;
        }

        // Ancienne structure avec id_hote uniquement
        $sql_hote = "SELECT id_hote FROM logement WHERE id_logement = :id_logement";
        $stmt_hote = Database::execute($sql_hote, [':id_logement' => $id_logement]);
        $logement = $stmt_hote->fetch();
        
        if ($logement) {
            $sql = "INSERT INTO photo (id_hote, url) VALUES (:id_hote, :url)";
            Database::execute($sql, [
                ':id_hote' => $logement['id_hote'],
                ':url' => $url
            ]);
            return true;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

function createReservation($data) {
    $sql = "INSERT INTO reservation (id_client, id_logement, date_deb, date_fin, montant_total, nb_voyageurs, commentaire, statut)
            VALUES (:id_client, :id_logement, :date_deb, :date_fin, :montant_total, :nb_voyageurs, :commentaire, 'en_attente')";
    
    return Database::execute($sql, $data);
}

function getClientReservations($id_client) {
    $sql = "SELECT r.*, l.titre, l.region_ville, l.prix_nuit
            FROM reservation r
            JOIN logement l ON r.id_logement = l.id_logement
            WHERE r.id_client = :id_client
            ORDER BY r.date_deb DESC";
    
    $stmt = Database::execute($sql, [':id_client' => $id_client]);
    return $stmt->fetchAll();
}

function getDemandesByClient($id_client) {
    $sql = "SELECT * FROM demande WHERE id_client = :id_client ORDER BY date_creation DESC";
    $stmt = Database::execute($sql, [':id_client' => $id_client]);
    return $stmt->fetchAll();
}

function createDemande($data) {
    $sql = "INSERT INTO demande (id_client, date_deb, date_fin, nb_nuits, budget, region_souhaitee, description, nb_voyageurs)
            VALUES (:id_client, :date_deb, :date_fin, :nb_nuits, :budget, :region_souhaitee, :description, :nb_voyageurs)";
    
    return Database::execute($sql, $data);
}

function getNotifications($id_client, $limit = 10) {
    $sql = "SELECT * FROM notification WHERE id_client = :id_client ORDER BY date_creation DESC LIMIT :limit";
    $stmt = Database::execute($sql, [':id_client' => $id_client, ':limit' => $limit]);
    return $stmt->fetchAll();
}

function getCorrespondances($id_client) {
    $sql = "SELECT c.*, 
            CONCAT(exp.nom, ' ', exp.prenom) as expediteur_nom,
            CONCAT(dest.nom, ' ', dest.prenom) as destinataire_nom
            FROM correspondance c
            JOIN utilisateur exp ON c.id_expediteur = exp.id_client
            JOIN utilisateur dest ON c.id_destinataire = dest.id_client
            WHERE c.id_expediteur = :id_client OR c.id_destinataire = :id_client
            ORDER BY c.date_envoi DESC";
    
    $stmt = Database::execute($sql, [':id_client' => $id_client]);
    return $stmt->fetchAll();
}

function isFavori($id_client, $id_logement) {
    $sql = "SELECT * FROM favori WHERE id_client = :id_client AND id_logement = :id_logement";
    $stmt = Database::execute($sql, [':id_client' => $id_client, ':id_logement' => $id_logement]);
    return $stmt->rowCount() > 0;
}

function addFavori($id_client, $id_logement) {
    $sql = "INSERT IGNORE INTO favori (id_client, id_logement) VALUES (:id_client, :id_logement)";
    return Database::execute($sql, [':id_client' => $id_client, ':id_logement' => $id_logement]);
}

function removeFavori($id_client, $id_logement) {
    $sql = "DELETE FROM favori WHERE id_client = :id_client AND id_logement = :id_logement";
    return Database::execute($sql, [':id_client' => $id_client, ':id_logement' => $id_logement]);
}

function getStats() {
    $stats = [];
    
    $sql = "SELECT COUNT(*) as total FROM utilisateur WHERE type_client = 'client' AND statut = 'actif'";
    $stmt = Database::execute($sql);
    $stats['clients'] = $stmt->fetch()['total'];
    
    $sql = "SELECT COUNT(*) as total FROM logement WHERE statut = 'actif'";
    $stmt = Database::execute($sql);
    $stats['logements'] = $stmt->fetch()['total'];
    
    $sql = "SELECT COUNT(*) as total, COALESCE(SUM(montant_total), 0) as revenus FROM reservation WHERE statut = 'confirmee'";
    $stmt = Database::execute($sql);
    $result = $stmt->fetch();
    $stats['reservations'] = $result['total'];
    $stats['revenus'] = $result['revenus'];
    
    return $stats;
}
// Ajouter à includes/functions.php

function getDemandesPourHote($id_hote) {
    $sql = "SELECT d.*, l.titre as logement_titre, CONCAT(c.prenom, ' ', c.nom) as client_nom
            FROM demande d
            JOIN logement l ON d.id_logement = l.id_logement
            JOIN utilisateur c ON d.id_client = c.id_client
            WHERE l.id_hote = :id_hote
            ORDER BY d.date_creation DESC";
    $stmt = Database::execute($sql, [':id_hote' => $id_hote]);
    return $stmt->fetchAll();
}

function accepterDemande($id_demande, $id_hote) {
    // Vérifier que la demande appartient bien à l'hôte
    $sql = "SELECT d.*, l.id_hote FROM demande d
            JOIN logement l ON d.id_logement = l.id_logement
            WHERE d.id_demande = :id_demande AND l.id_hote = :id_hote";
    $stmt = Database::execute($sql, [':id_demande' => $id_demande, ':id_hote' => $id_hote]);
    $demande = $stmt->fetch();
    
    if (!$demande) return false;
    
    // Mettre à jour la demande
    Database::execute("UPDATE demande SET statut = 'acceptee' WHERE id_demande = :id_demande", [':id_demande' => $id_demande]);
    
    // Créer la réservation
    $sql2 = "INSERT INTO reservation (id_client, id_logement, date_deb, date_fin, montant_total, nb_voyageurs, statut) 
             VALUES (:id_client, :id_logement, :date_deb, :date_fin, :montant, :nb_voyageurs, 'confirmee')";
    Database::execute($sql2, [
        ':id_client' => $demande['id_client'],
        ':id_logement' => $demande['id_logement'],
        ':date_deb' => $demande['date_deb'],
        ':date_fin' => $demande['date_fin'],
        ':montant' => $demande['montant'],
        ':nb_voyageurs' => $demande['nb_voyageurs']
    ]);
    
    return true;
}

function refuserDemande($id_demande, $id_hote) {
    $sql = "UPDATE demande d
            JOIN logement l ON d.id_logement = l.id_logement
            SET d.statut = 'refusee'
            WHERE d.id_demande = :id_demande AND l.id_hote = :id_hote";
    Database::execute($sql, [':id_demande' => $id_demande, ':id_hote' => $id_hote]);
    return true;
}
?>