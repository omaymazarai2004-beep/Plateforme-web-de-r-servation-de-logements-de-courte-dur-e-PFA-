<?php
// logement-detail.php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: fr');

// Forcer l'encodage UTF-8 pour PHP
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Fonction pour s'assurer que les données sont en UTF-8
function ensureUTF8($string) {
    if (is_string($string)) {
        // Détecter l'encodage et convertir si nécessaire
        $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $string = mb_convert_encoding($string, 'UTF-8', $encoding);
        }
        // S'assurer que la chaîne est valide UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
        }
    }
    return $string;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Fonction pour traiter récursivement les tableaux
function ensureUTF8Recursive($data) {
    if (is_array($data)) {
        return array_map('ensureUTF8Recursive', $data);
    } elseif (is_string($data)) {
        return ensureUTF8($data);
    } else {
        return $data;
    }
}

$id = $_GET['id'] ?? 0;
$logement = getLogementById($id);

// Traiter les données du logement pour s'assurer de l'encodage UTF-8
$logement = ensureUTF8Recursive($logement);

if (!$logement) {
    header('Location: index.php');
    exit();
}

// Récupérer les photos - Version sécurisée
$photos = [];
try {
    // Vérifier si la table photo existe
    $table_check = Database::execute("SHOW TABLES LIKE 'photo'")->fetch();
    
    if ($table_check) {
        // Vérifier la structure
        $columns = Database::execute("SHOW COLUMNS FROM photo")->fetchAll();
        $has_id_logement = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] == 'id_logement') $has_id_logement = true;
        }
        
        if ($has_id_logement) {
            $sql = "SELECT * FROM photo WHERE id_logement = :id_logement ORDER BY est_principale DESC";
            $stmt = Database::execute($sql, [':id_logement' => $id]);
            $photos = $stmt->fetchAll();
        } else {
            // Ancienne structure avec id_hote
            $sql_hote = "SELECT id_hote FROM logement WHERE id_logement = :id_logement";
            $stmt_hote = Database::execute($sql_hote, [':id_logement' => $id]);
            $log = $stmt_hote->fetch();
            
            if ($log) {
                $sql = "SELECT * FROM photo WHERE id_hote = :id_hote";
                $stmt = Database::execute($sql, [':id_hote' => $log['id_hote']]);
                $photos = $stmt->fetchAll();
            }
        }
    }
} catch (Exception $e) {
    // En cas d'erreur, on continue sans photos
    $photos = [];
}

$imagePrincipale = !empty($photos) ? $photos[0]['url'] : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80';
$imagesSecondaires = array_slice($photos, 1, 4);

// Vérifier si déjà en favori
$estFavori = isLoggedIn() && isFavori($_SESSION['user_id'], $id);

$reservations = [];
try {
    $stmt = Database::execute(
        "SELECT date_deb, date_fin FROM reservation WHERE id_logement = :id_logement AND statut IN ('confirmee', 'en_attente')",
        [':id_logement' => $id]
    );
    $reservations = $stmt->fetchAll();
} catch (Exception $e) {
    $reservations = [];
}

$bookedRanges = [];
foreach ($reservations as $reservation) {
    $startDate = strtotime($reservation['date_deb']);
    $endDate = strtotime($reservation['date_fin']);

    if ($startDate !== false && $endDate !== false) {
        if ($endDate <= $startDate) {
            $endDate = strtotime('+1 day', $startDate);
        }

        $bookedRanges[] = [
            'start' => date('Y-m-d', $startDate),
            'end' => date('Y-m-d', $endDate)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="fr">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($logement['titre'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?> - Logements Tunisie</title>
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
    background: linear-gradient(180deg, #fff5f9 0%, #fff9fc 50%, #ffffff 100%);
    color: var(--text-dark);
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
}

/* Navigation */
nav {
    background: rgba(255, 255, 255, 0.97);
    padding: 16px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(221, 221, 221, 0.9);
    box-shadow: 0 16px 50px rgba(255, 90, 95, 0.08);
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    font-size: 24px;
    font-weight: 700;
    color: var(--rose);
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

/* Header */
.listing-header {
    max-width: 1200px;
    margin: 32px auto 32px;
    padding: 40px 32px;
    background: linear-gradient(180deg, rgba(255, 90, 95, 0.06), rgba(255, 255, 255, 0.98));
    border-radius: 32px;
    box-shadow: 0 20px 50px rgba(229, 11, 65, 0.08);
}

.listing-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 5vw, 48px);
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
    line-height: 1.2;
}

.listing-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    padding: 24px 28px;
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 12px 35px rgba(33, 33, 33, 0.06);
    margin-top: 24px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
    font-size: 14px;
}

.rating-star {
    color: var(--airbnb);
}

.meta-actions {
    display: flex;
    gap: 16px;
}

.meta-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    padding: 8px 12px;
    border-radius: 40px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.meta-btn:hover {
    background: var(--gray-bg);
    color: var(--airbnb);
}

/* Galerie principale - Photo principale */
.gallery-main {
    max-width: 1200px;
    margin: 32px auto 48px;
    padding: 0 32px;
    width: 100%;
}

.gallery-main img {
    width: 100%;
    height: 600px;
    object-fit: cover;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(33, 33, 33, 0.15);
    transition: transform 0.3s ease;
}

.gallery-main:hover img {
    transform: scale(1.02);
}

/* Galerie secondaire - Photos supplémentaires */
.gallery-secondary {
    max-width: 1200px;
    margin: 48px auto 64px;
    padding: 0 32px;
}

.gallery-secondary-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 24px;
    text-align: center;
}

.gallery-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 20px 0;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}

.gallery-scroll::-webkit-scrollbar {
    height: 8px;
}

.gallery-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 90, 95, 0.4);
    border-radius: 999px;
}

.gallery-scroll::-webkit-scrollbar-track {
    background: rgba(255, 90, 95, 0.1);
    border-radius: 999px;
}

.gallery-item {
    flex: 0 0 300px;
    height: 200px;
    border-radius: 16px;
    overflow: hidden;
    scroll-snap-align: start;
    position: relative;
    box-shadow: 0 8px 25px rgba(33, 33, 33, 0.1);
    transition: transform 0.3s ease;
}

.gallery-item:hover {
    transform: scale(1.05);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-no-photos {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    border: 2px dashed rgba(221, 221, 221, 0.5);
    margin: 48px auto;
    max-width: 600px;
}

.gallery-no-photos i {
    font-size: 48px;
    color: var(--text-light);
    margin-bottom: 16px;
}

.gallery-no-photos h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.gallery-no-photos p {
    color: var(--text-light);
    font-size: 16px;
}

/* Body */
.listing-body {
    max-width: 1200px;
    margin: 48px auto 64px;
    padding: 0 32px;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 64px;
    align-items: start;
}

.host-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px 0 28px;
    border-bottom: 2px solid rgba(221, 221, 221, 0.8);
    margin-bottom: 32px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 8px 25px rgba(33, 33, 33, 0.04);
}

.host-name {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.host-sub {
    color: var(--text-light);
    font-size: 16px;
    font-weight: 500;
}

.host-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--airbnb), var(--airbnb-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 22px;
    box-shadow: 0 8px 20px rgba(255, 90, 95, 0.3);
}

.features {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-bottom: 40px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 8px 25px rgba(33, 33, 33, 0.04);
}

.feature {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    padding: 16px;
    border-radius: 16px;
    transition: background 0.2s ease;
}

.feature:hover {
    background: rgba(255, 90, 95, 0.02);
}

.feature-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, rgba(255, 90, 95, 0.1), rgba(255, 90, 95, 0.05));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--airbnb);
    font-size: 20px;
    flex-shrink: 0;
}

.feature-text strong {
    display: block;
    color: var(--text-dark);
    margin-bottom: 6px;
    font-size: 16px;
    font-weight: 600;
}

.feature-text span {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.4;
}

.description {
    margin-bottom: 40px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 8px 25px rgba(33, 33, 33, 0.04);
}

.description h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--text-dark);
    position: relative;
}

.description h3::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--airbnb), var(--airbnb-dark));
    border-radius: 2px;
}

.description p {
    color: var(--text-light);
    line-height: 1.7;
    font-size: 16px;
}

.amenities {
    margin-bottom: 40px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 8px 25px rgba(33, 33, 33, 0.04);
}

.amenities h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 24px;
    color: var(--text-dark);
    position: relative;
}

.amenities h3::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--airbnb), var(--airbnb-dark));
    border-radius: 2px;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.amenity {
    display: flex;
    align-items: center;
    gap: 16px;
    color: var(--text-dark);
    font-size: 15px;
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 12px;
    transition: background 0.2s ease;
}

.amenity:hover {
    background: rgba(255, 90, 95, 0.03);
}

.amenity i {
    width: 28px;
    color: var(--airbnb);
    font-size: 18px;
}

.availability {
    margin: 32px 0 28px;
    padding: 24px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    border: 1px solid rgba(221, 221, 221, 0.5);
}

.calendar-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    color: var(--text-dark);
    text-align: center;
}

.calendar-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
    font-size: 14px;
    color: var(--text-dark);
}

.legend-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.legend-swatch {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    display: inline-block;
    border: 1px solid rgba(0,0,0,0.08);
}

.legend-available {
    background: #d4f7dc;
}

.legend-booked {
    background: #ffccd5;
}

#calendarContainer {
    display: flex;
    gap: 18px;
    overflow-x: auto;
    padding-bottom: 10px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}

#calendarContainer::-webkit-scrollbar {
    height: 10px;
}

#calendarContainer::-webkit-scrollbar-thumb {
    background: rgba(255, 90, 95, 0.35);
    border-radius: 999px;
}

#calendarContainer::-webkit-scrollbar-track {
    background: #ffe8ef;
    border-radius: 999px;
}

.calendar-month {
    min-width: 380px;
    flex: 0 0 380px;
    background: white;
    border-radius: 24px;
    padding: 22px;
    box-shadow: 0 18px 42px rgba(33, 33, 33, 0.06);
    scroll-snap-align: start;
    margin-bottom: 0;
}

.month-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 14px;
    text-transform: capitalize;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    font-size: 12px;
    color: var(--text-light);
    text-transform: uppercase;
    margin-bottom: 12px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.calendar-day {
    min-height: 48px;
    border-radius: 16px;
    padding: 10px 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    transition: transform 0.15s ease, background 0.15s ease;
}

.calendar-day.available {
    background: #eaf9ef;
    color: #0f572a;
    border: 1px solid #c6ecd0;
}

.calendar-day.booked {
    background: #ffe3e6;
    color: #a41624;
    border: 1px solid #f5c2cb;
    position: relative;
}

.calendar-day.booked::after {
    content: 'Réservé';
    position: absolute;
    bottom: 5px;
    right: 6px;
    font-size: 10px;
    color: #a41624;
}

.calendar-day.past {
    opacity: 0.5;
}

.calendar-day.empty {
    background: transparent;
    border: none;
}

/* Booking Card */
.booking-card {
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(221, 221, 221, 0.8);
    border-radius: 28px;
    padding: 32px;
    box-shadow: 0 20px 60px rgba(33, 33, 33, 0.08);
    position: sticky;
    top: 120px;
    height: fit-content;
    backdrop-filter: blur(10px);
}

.price-line {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 28px;
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(255, 90, 95, 0.05), rgba(255, 90, 95, 0.02));
    border-radius: 20px;
    border: 1px solid rgba(255, 90, 95, 0.1);
}

.price-line span {
    font-size: 18px;
    font-weight: 400;
    color: var(--text-light);
}

.date-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.date-field {
    border: 1px solid var(--gray-border);
    border-radius: 16px;
    padding: 12px;
}

.date-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--text-dark);
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.date-field input {
    width: 100%;
    border: none;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    padding: 4px 0;
}

.date-field input:focus {
    outline: none;
}

.guests-row {
    border: 1px solid var(--gray-border);
    border-radius: 16px;
    padding: 12px;
    margin-bottom: 20px;
}

.guests-input {
    width: 100%;
    border: none;
    font-size: 14px;
    padding: 4px 0;
}

.guests-input:focus {
    outline: none;
}

.price-breakdown {
    margin: 20px 0;
    border-top: 1px solid var(--gray-border);
    padding-top: 16px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    color: var(--text-light);
}

.price-row.total {
    border-top: 1px solid var(--gray-border);
    margin-top: 8px;
    padding-top: 16px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 16px;
}

.reserve-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--airbnb), var(--airbnb-dark));
    color: white;
    border: none;
    padding: 16px;
    border-radius: 40px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(255, 90, 95, 0.3);
}

.reserve-btn:hover {
    background: linear-gradient(135deg, var(--airbnb-dark), var(--airbnb));
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(255, 90, 95, 0.4);
}

.reserve-note {
    text-align: center;
    font-size: 12px;
    color: var(--text-light);
    margin-top: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .listing-body {
        grid-template-columns: 1fr;
        gap: 48px;
    }
    .booking-card {
        position: static;
        order: -1;
    }
    .gallery-main img {
        height: 400px;
    }
    .gallery-secondary {
        margin: 32px auto 48px;
    }
    .gallery-item {
        flex: 0 0 250px;
        height: 180px;
    }
}

@media (max-width: 768px) {
    nav {
        padding: 16px 20px;
    }
    .listing-header {
        margin: 24px auto 24px;
        padding: 32px 24px;
    }
    .listing-header h1 {
        font-size: 28px;
    }
    .listing-meta {
        padding: 20px 24px;
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .gallery-main {
        margin: 24px auto 32px;
        padding: 0 20px;
    }
    .gallery-main img {
        height: 300px;
        border-radius: 16px;
    }
    .gallery-secondary {
        margin: 32px auto 48px;
        padding: 0 20px;
    }
    .gallery-secondary-title {
        font-size: 20px;
    }
    .gallery-item {
        flex: 0 0 200px;
        height: 150px;
    }
    .gallery-no-photos {
        padding: 40px 20px;
        margin: 32px auto;
    }
    .gallery-no-photos i {
        font-size: 36px;
    }
    .gallery-no-photos h3 {
        font-size: 18px;
    }
    .listing-body {
        margin: 32px auto 48px;
        padding: 0 20px;
        gap: 32px;
    }
    .host-line {
        padding: 20px 24px;
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    .features, .description, .amenities {
        padding: 24px 20px;
    }
    .booking-card {
        padding: 24px 20px;
    }
    .price-line {
        font-size: 28px;
        padding: 16px;
    }
    .availability {
        padding: 20px;
        margin: 24px 0 20px;
    }
}
</style>
</head>
<body>

<nav>
    <a href="index.php" class="logo"><i class="fa-solid fa-house-chimney"></i> Logements</a>
    <a href="index.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i> Retour aux résultats</a>
</nav>

<div class="listing-header">
    <h1><?php echo htmlspecialchars($logement['titre'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></h1>
    <div class="listing-meta">
        <div class="meta-item"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($logement['region_ville'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>, Tunisie</span></div>
        <div class="meta-actions">
            <button class="meta-btn" onclick="toggleFavoriDetail(<?php echo $id; ?>)">
                <i class="fa-<?php echo $estFavori ? 'solid' : 'regular'; ?> fa-heart" id="favIcon"></i> 
                <span id="favText"><?php echo $estFavori ? 'Sauvegardé' : 'Sauvegarder'; ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Photo principale -->
<div class="gallery-main">
    <img src="<?php echo $imagePrincipale; ?>" alt="<?php echo htmlspecialchars($logement['titre'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"/>
</div>

<!-- Photos supplémentaires -->
<?php if (!empty($imagesSecondaires)): ?>
<div class="gallery-secondary">
    <h2 class="gallery-secondary-title">Plus de photos</h2>
    <div class="gallery-scroll">
        <?php foreach($imagesSecondaires as $img): ?>
            <div class="gallery-item">
                <img src="<?php echo $img['url']; ?>" alt="Photo supplémentaire"/>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="gallery-no-photos">
    <i class="fas fa-images"></i>
    <h3>Pas de photos supplémentaires</h3>
    <p>Le propriétaire n'a pas ajouté d'autres photos pour ce logement.</p>
</div>
<?php endif; ?>

<div class="listing-body">
    <div class="listing-info">
        <div class="host-line">
            <div>
                <div class="host-name">Logement géré par le propriétaire <?php echo htmlspecialchars($logement['hote_prenom'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></div>
                <div class="host-sub"><?php echo $logement['nb_chambres']; ?> chambres · <?php echo $logement['nb_lits']; ?> lits · <?php echo $logement['nb_sdb']; ?> sdb</div>
            </div>
            <div class="host-avatar"><?php echo strtoupper(substr($logement['hote_prenom'], 0, 1)); ?></div>
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon"><i class="fa-solid fa-award"></i></div>
                <div class="feature-text"><strong>Superhost</strong><span>Expérience reconnue de qualité</span></div>
            </div>
            <div class="feature">
                <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="feature-text"><strong>Annulation gratuite</strong><span>Sous conditions</span></div>
            </div>
            <div class="feature">
                <div class="feature-icon"><i class="fa-solid fa-key"></i></div>
                <div class="feature-text"><strong>Arrivée autonome</strong><span>Code d'accès sécurisé</span></div>
            </div>
        </div>

        <div class="description">
            <h3>À propos de ce logement</h3>
            <p><?php echo nl2br(htmlspecialchars($logement['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
        </div>

        <div class="amenities">
            <h3>Ce que propose ce logement</h3>
            <div class="amenities-grid">
                <div class="amenity"><i class="fa-solid fa-wifi"></i>Wi-Fi haut débit</div>
                <div class="amenity"><i class="fa-solid fa-snowflake"></i>Climatisation</div>
                <div class="amenity"><i class="fa-solid fa-car"></i>Parking gratuit</div>
                <div class="amenity"><i class="fa-solid fa-tv"></i>Télévision</div>
                <div class="amenity"><i class="fa-solid fa-kitchen-set"></i>Cuisine équipée</div>
                <div class="amenity"><i class="fa-solid fa-water-ladder"></i>Piscine</div>
            </div>
        </div>
    </div>

    <div class="booking-card">
        <div class="price-line">
            <strong><?php echo number_format($logement['prix_nuit'], 0); ?> TND</strong><span>/ nuit</span>
        </div>

        <div class="availability">
            <div class="calendar-title">Disponibilités</div>
            <div class="calendar-legend">
                <div class="legend-badge"><span class="legend-swatch legend-available"></span> Disponible</div>
                <div class="legend-badge"><span class="legend-swatch legend-booked"></span> Réservé</div>
            </div>
            <div id="calendarContainer"></div>
        </div>
        
        <form id="bookingForm" method="POST" action="api/add_reservation.php" onsubmit="return validateBooking()">
            <input type="hidden" name="id_logement" value="<?php echo $id; ?>"/>
            
            <?php 
            $bookingError = $_GET['error'] ?? '';
            if ($bookingError): ?>
            <div id="bookingError" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 14px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; color: #dc2626;"></i>
                <span><?php echo htmlspecialchars($bookingError); ?></span>
            </div>
            <?php endif; ?>
            
            <div id="dateConflictError" style="display: none; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 14px; margin-bottom: 16px; font-size: 14px; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; color: #dc2626;"></i>
                <span>Ces dates chevauchent une réservation existante. Veuillez choisir d'autres dates.</span>
            </div>
            
            <div class="date-row">
                <div class="date-field">
                    <div class="date-label">ARRIVÉE</div>
                    <input type="date" name="date_deb" id="date_deb" required min="<?php echo date('Y-m-d'); ?>" onchange="updateDateFin(); calculerTotal()"/>
                </div>
                <div class="date-field">
                    <div class="date-label">DÉPART</div>
                    <input type="date" name="date_fin" id="date_fin" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculerTotal()"/>
                </div>
            </div>
            
            <div id="dateOrderError" style="display: none; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 14px; margin-bottom: 16px; font-size: 14px; align-items: center; gap: 10px;">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 16px; color: #dc2626;"></i>
                <span>La date de départ doit être après la date d'arrivée.</span>
            </div>
            
            <div class="guests-row">
                <div class="date-label">VOYAGEURS</div>
                <input type="number" name="nb_voyageurs" id="nb_voyageurs" min="1" value="1" class="guests-input" onchange="calculerTotal()"/>
            </div>
            
            <div class="form-group">
                <textarea name="commentaire" placeholder="Message pour le propriétaire (optionnel)" rows="3" style="width:100%; padding:12px; border-radius:12px; border:1px solid #EBEBEB;"></textarea>
            </div>
            
            <div id="totalDisplay">
                <div class="price-breakdown">
                    <div class="price-row" id="prixNuitsRow">
                        <span><?php echo number_format($logement['prix_nuit'], 0); ?> TND × <span id="nbNuits">0</span> nuits</span>
                        <span id="prixNuits">0 TND</span>
                    </div>
                    <div class="price-row">
                        <span>Frais de service (8%)</span>
                        <span id="fraisService">0 TND</span>
                    </div>
                    <div class="price-row total">
                        <span>Total</span>
                        <span id="total">0 TND</span>
                    </div>
                </div>
            </div>
            
            <?php if(isLoggedIn()): ?>
                <button type="submit" class="reserve-btn"><i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i> Demander la réservation</button>
                <div class="reserve-note"><i class="fa-solid fa-info-circle" style="margin-right: 4px;"></i> Votre demande sera envoyée au propriétaire pour approbation</div>
            <?php else: ?>
                <button type="button" class="reserve-btn" onclick="showLogin()">Connectez-vous pour réserver</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
const prixNuit = <?php echo $logement['prix_nuit']; ?>;
const bookedRanges = <?php echo json_encode($bookedRanges); ?>;

function formatISODate(date) {
    return date.toISOString().split('T')[0];
}

function isDateBooked(date) {
    const iso = formatISODate(date);
    return bookedRanges.some(range => iso >= range.start && iso < range.end);
}

function buildMonthCalendar(year, month) {
    const firstDay = new Date(year, month, 1);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const startWeekday = (firstDay.getDay() + 6) % 7;
    const monthDiv = document.createElement('div');
    monthDiv.className = 'calendar-month';
    monthDiv.innerHTML = `<div class="month-name">${firstDay.toLocaleString('fr-FR', { month: 'long', year: 'numeric' })}</div>`;

    const weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const header = document.createElement('div');
    header.className = 'calendar-weekdays';
    weekdays.forEach(day => {
        const dayLabel = document.createElement('div');
        dayLabel.textContent = day;
        header.appendChild(dayLabel);
    });
    monthDiv.appendChild(header);

    const grid = document.createElement('div');
    grid.className = 'calendar-grid';
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 0; i < startWeekday; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-day empty';
        grid.appendChild(empty);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const current = new Date(year, month, day);
        const cell = document.createElement('div');
        cell.className = 'calendar-day';
        const booked = isDateBooked(current);
        cell.classList.add(booked ? 'booked' : 'available');
        if (current < today) {
            cell.classList.add('past');
        }
        cell.textContent = day;
        grid.appendChild(cell);
    }

    monthDiv.appendChild(grid);
    return monthDiv;
}

function renderAvailabilityCalendar(months = 3) {
    const container = document.getElementById('calendarContainer');
    if (!container) return;
    container.innerHTML = '';
    const now = new Date();

    for (let i = 0; i < months; i++) {
        const monthDate = new Date(now.getFullYear(), now.getMonth() + i, 1);
        container.appendChild(buildMonthCalendar(monthDate.getFullYear(), monthDate.getMonth()));
    }
}

document.addEventListener('DOMContentLoaded', () => renderAvailabilityCalendar(6));

function updateDateFin() {
    const dateDeb = document.getElementById('date_deb').value;
    const dateFin = document.getElementById('date_fin');
    if (dateDeb) {
        const nextDay = new Date(dateDeb);
        nextDay.setDate(nextDay.getDate() + 1);
        const minFin = nextDay.toISOString().split('T')[0];
        dateFin.min = minFin;
        if (dateFin.value && dateFin.value <= dateDeb) {
            dateFin.value = minFin;
        }
    }
}

function calculerTotal() {
    const dateDeb = document.getElementById('date_deb').value;
    const dateFin = document.getElementById('date_fin').value;
    const nbVoyageurs = parseInt(document.getElementById('nb_voyageurs').value) || 1;
    const orderError = document.getElementById('dateOrderError');
    const submitBtn = document.querySelector('.reserve-btn[type="submit"]');
    
    // Vérifier l'ordre des dates
    if (dateDeb && dateFin && dateFin <= dateDeb) {
        orderError.style.display = 'flex';
        document.getElementById('prixNuitsRow').style.display = 'none';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.style.opacity = '0.5'; submitBtn.style.cursor = 'not-allowed'; }
        return;
    } else {
        orderError.style.display = 'none';
    }
    
    // Vérifier les conflits de dates
    checkDateConflict();
    
    if (dateDeb && dateFin) {
        const deb = new Date(dateDeb);
        const fin = new Date(dateFin);
        const nbNuits = Math.ceil((fin - deb) / (1000 * 60 * 60 * 24));
        
        if (nbNuits > 0) {
            const prixNuits = nbNuits * prixNuit;
            const frais = prixNuits * 0.08;
            const total = prixNuits + frais;
            
            document.getElementById('nbNuits').textContent = nbNuits;
            document.getElementById('prixNuits').textContent = prixNuits.toLocaleString() + ' TND';
            document.getElementById('fraisService').textContent = Math.round(frais).toLocaleString() + ' TND';
            document.getElementById('total').textContent = Math.round(total).toLocaleString() + ' TND';
            document.getElementById('prixNuitsRow').style.display = 'flex';
            return;
        }
    }
    
    document.getElementById('prixNuitsRow').style.display = 'none';
}

// Vérifier si les dates choisies chevauchent une réservation
function hasDateConflict(dateDebStr, dateFinStr) {
    if (!dateDebStr || !dateFinStr) return false;
    return bookedRanges.some(range => {
        return dateDebStr < range.end && dateFinStr > range.start;
    });
}

function checkDateConflict() {
    const dateDeb = document.getElementById('date_deb').value;
    const dateFin = document.getElementById('date_fin').value;
    const errorDiv = document.getElementById('dateConflictError');
    const submitBtn = document.querySelector('.reserve-btn[type="submit"]');
    
    if (dateDeb && dateFin && hasDateConflict(dateDeb, dateFin)) {
        errorDiv.style.display = 'flex';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        }
    } else {
        errorDiv.style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    }
}

function validateBooking() {
    const dateDeb = document.getElementById('date_deb').value;
    const dateFin = document.getElementById('date_fin').value;
    
    if (!dateDeb || !dateFin) {
        alert('Veuillez sélectionner les dates d\'arrivée et de départ.');
        return false;
    }
    
    if (new Date(dateFin) <= new Date(dateDeb)) {
        alert('La date de départ doit être après la date d\'arrivée.');
        return false;
    }
    
    if (hasDateConflict(dateDeb, dateFin)) {
        document.getElementById('dateConflictError').style.display = 'flex';
        return false;
    }
    
    return true;
}

function toggleFavoriDetail(id) {
    <?php if(isLoggedIn()): ?>
        fetch('api/toggle_favori.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_logement=' + id
        })
        .then(response => response.json())
        .then(data => {
            const icon = document.querySelector('#favIcon');
            const text = document.getElementById('favText');
            if (data.favori) {
                icon.className = 'fa-solid fa-heart';
                text.textContent = 'Sauvegardé';
            } else {
                icon.className = 'fa-regular fa-heart';
                text.textContent = 'Sauvegarder';
            }
        });
    <?php else: ?>
        showLogin();
    <?php endif; ?>
}

function partager() {
    navigator.clipboard.writeText(window.location.href);
    alert('Lien copié dans le presse-papier !');
}

function showLogin() {
    window.location.href = 'connexion.php?redirect=logement-detail.php?id=<?php echo $id; ?>';
}
</script>

<?php require_once 'includes/footer.php'; ?>