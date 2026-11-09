<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$context = $input['context'] ?? [];   // historique côté client : [{role, text}]

if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'Message vide']);
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

/* ───────────────────────────────────────────────
   Analyse du message (NLP maison, sans API)
─────────────────────────────────────────────── */
$msg = mb_strtolower($message, 'UTF-8');

// Intention : réservation directe
$wantsReserve = preg_match('/r[eé]serv|book|je\s+veux|je\s+prends|louer|r[eé]server/u', $msg);

// Intention : aide / comment ça marche
$wantsHelp = preg_match('/aide|comment|quoi|help|comment[?]/u', $msg) && !$wantsReserve;

// Intention : salutation
$isGreeting = preg_match('/^(bonjour|salut|hello|bonsoir|hi|hey|salam|coucou)[\s!.,]*$/u', $msg);

// Intention : liste / voir logements
$wantsList = preg_match('/voir|liste|logement|affich|montre|propos|disponible|cherch|trouv|quoi comme/u', $msg);

// ── Extraction ville / région ──
$villes = [
    // ── Tunis (gouvernorat) ──────────────────────────────────────────
    'tunis', 'sidi bou said', 'la marsa', 'gammarth', 'carthage',
    'le bardo', 'la goulette', 'ariana', 'manouba',

    // ── Ariana ──────────────────────────────────────────────────────
    'ariana', 'raoued', 'kalâat el andalous', 'ettadhamen', 'mnihla',

    // ── Ben Arous ────────────────────────────────────────────────────
    'ben arous', 'hammam lif', 'hammam chott', 'ezzahra', 'megrine',
    'rades', 'bou mhel el bassatine',

    // ── Manouba ─────────────────────────────────────────────────────
    'manouba', 'den den', 'oued ellil', 'tebourba', 'djedeida',

    // ── Nabeul ──────────────────────────────────────────────────────
    'nabeul', 'hammamet', 'kelibia', 'korba', 'el haouaria',
    'korbous', 'menzel temime', 'grombalia', 'soliman',

    // ── Zaghouan ────────────────────────────────────────────────────
    'zaghouan', 'zriba', 'hammam zriba', 'djebel zaghouan',

    // ── Bizerte ─────────────────────────────────────────────────────
    'bizerte', 'menzel bourguiba', 'mateur', 'ras jebel', 'cap bon',
    'ichkeul', 'sejnane', 'ghezala',

    // ── Béja ────────────────────────────────────────────────────────
    'beja', 'béja', 'testour', 'nefza', 'thibar', 'teboursouk',
    'medjez el bab',

    // ── Jendouba ────────────────────────────────────────────────────
    'jendouba', 'tabarka', 'ain draham', 'aïn draham', 'bulla regia',
    'fernana', 'ghardimaou', 'oued melliz',

    // ── Le Kef ──────────────────────────────────────────────────────
    'kef', 'le kef', 'sakiet sidi youssef', 'tajerouine', 'dahmani',
    'kalâat khasba',

    // ── Siliana ─────────────────────────────────────────────────────
    'siliana', 'makthar', 'rouhia', 'kesra', 'bargou',

    // ── Sousse ──────────────────────────────────────────────────────
    'sousse', 'port el kantaoui', 'hammam sousse', 'akouda',
    'kalaa kebira', 'sidi bou ali', 'enfidha',

    // ── Monastir ────────────────────────────────────────────────────
    'monastir', 'skanes', 'ksar hellal', 'moknine', 'bekalta',
    'sayada', 'lamta',

    // ── Mahdia ──────────────────────────────────────────────────────
    'mahdia', 'el jem', 'ksour essef', 'chebba', 'bou merdes',
    'sidi alouane',

    // ── Sfax ────────────────────────────────────────────────────────
    'sfax', 'kerkenah', 'ile de kerkenah', 'el ain', 'sakiet eddaier',
    'thyna', 'mahres', 'jebeniana',

    // ── Kairouan ────────────────────────────────────────────────────
    'kairouan', 'sbikha', 'haffouz', 'chebika', 'oueslatia',
    'nasrallah', 'el alaa',

    // ── Kasserine ───────────────────────────────────────────────────
    'kasserine', 'sbeitla', 'feriana', 'thala', 'majel bel abbes',
    'hidra', 'foussana',

    // ── Sidi Bouzid ─────────────────────────────────────────────────
    'sidi bouzid', 'regueb', 'menzel bouzaiene', 'jelma', 'bir el hafey',
    'meknassy', 'souk jedid',

    // ── Gabès ───────────────────────────────────────────────────────
    'gabes', 'gabès', 'matmata', 'nouvelle matmata', 'mareth',
    'el hamma', 'chenini gabès', 'toujane',

    // ── Médenine ────────────────────────────────────────────────────
    'medenine', 'médenine', 'djerba', 'houmt souk', 'midoun',
    'ajim', 'zarzis', 'ben gardane', 'tataouine', 'beni khedache',

    // ── Tataouine ───────────────────────────────────────────────────
    'tataouine', 'chenini', 'douiret', 'ghomrassen', 'remada',
    'bir lahmar', 'ksar ouled soltane', 'ksar hadada',

    // ── Gafsa ───────────────────────────────────────────────────────
    'gafsa', 'metlaoui', 'redeyef', 'moulares', 'el guettar',
    'sened', 'lalla',

    // ── Tozeur ──────────────────────────────────────────────────────
    'tozeur', 'nefta', 'degache', 'tamerza', 'chebika tozeur',
    'mides', 'hazoua',

    // ── Kébili ──────────────────────────────────────────────────────
    'kebili', 'kébili', 'douz', 'souk lahad', 'el faouar',
    'jemna', 'zaafrane',
];
$villeDetectee = null;
foreach ($villes as $v) {
    if (mb_strpos($msg, $v) !== false) {
        $villeDetectee = $v;
        break;
    }
}

// ── Extraction type de logement ──
$types = ['villa','appartement','studio','maison','loft'];
$typeDetecte = null;
foreach ($types as $t) {
    if (mb_strpos($msg, $t) !== false) {
        $typeDetecte = $t;
        break;
    }
}

// ── Extraction prix max ──
preg_match('/(\d+)\s*(dt|dinar|tnd|€|euro|par nuit)?/u', $msg, $prixMatch);
$prixMax = null;
if (!empty($prixMatch[1]) && (int) $prixMatch[1] >= 30) {
    $prixMax = (int) $prixMatch[1];
}

// ── Extraction thème ──
$theme = null;
if (preg_match('/mer|plage|bord de mer|balnéaire|baln/u', $msg))       $theme = 'mer';
elseif (preg_match('/montagne|nature|forêt|foret|vert/u', $msg))       $theme = 'montagne';
elseif (preg_match('/luxe|premium|standing|haut de gamme/u', $msg))    $theme = 'luxe';

// ── Extraction id logement dans contexte ──
$logementIdFromContext = null;
foreach (array_reverse($context) as $c) {
    if (!empty($c['logement_id'])) {
        $logementIdFromContext = (int) $c['logement_id'];
        break;
    }
}
// Cherche aussi dans le message courant
preg_match('/logement[^\d]*(\d+)/u', $msg, $lMatch);
if (!empty($lMatch[1])) $logementIdFromContext = (int) $lMatch[1];

/* ───────────────────────────────────────────────
   Construction de la réponse
─────────────────────────────────────────────── */

// 1 — Salutation
if ($isGreeting) {
    echo json_encode([
        'ok'   => true,
        'type' => 'greeting',
        'text' => "Bonjour ! 👋 Je suis votre assistant logement. Je peux vous aider à :\n• Trouver un logement (ville, type, budget)\n• Afficher les détails d'un bien\n• Réserver directement depuis le chat\n\nQue recherchez-vous ?",
        'suggestions' => [
            'Logements à Djerba',
            'Studio pas cher',
            'Villa bord de mer',
            'Je veux réserver',
        ],
    ]);
    exit;
}

// 2 — Aide
if ($wantsHelp) {
    echo json_encode([
        'ok'   => true,
        'type' => 'help',
        'text' => "Je comprends les phrases naturelles 💬\n\nExemples :\n• « Je cherche un appartement à Sousse »\n• « Villa moins de 300 DT »\n• « Logements en bord de mer »\n• « Réserver le logement 12 »\n\nJe trouve et affiche les résultats directement ici !",
        'suggestions' => ['Voir tous les logements','Logements à Tunis','Villa luxe'],
    ]);
    exit;
}

// 3 — Réservation directe
if ($wantsReserve && $logementIdFromContext) {
    if (!$isLoggedIn) {
        echo json_encode([
            'ok'   => true,
            'type' => 'auth_required',
            'text' => "🔐 Pour réserver, vous devez être connecté.\nCliquez sur le bouton ci-dessous pour vous connecter.",
            'action' => ['label' => 'Se connecter', 'url' => 'index.php#login'],
        ]);
        exit;
    }
    // Redirige vers la fiche logement avec flag chatbot
    $logement = getLogementById($logementIdFromContext);
    if ($logement) {
        echo json_encode([
            'ok'          => true,
            'type'        => 'redirect_reservation',
            'text'        => "Super choix ! 🎉 Je vous redirige vers la fiche de **{$logement['titre']}** pour finaliser votre réservation.",
            'logement_id' => $logementIdFromContext,
            'redirect_url'=> "logement-detail.php?id={$logementIdFromContext}&chatbot=1",
        ]);
        exit;
    }
}

// 4 — Recherche de logements
if ($wantsList || $villeDetectee || $typeDetecte || $theme || $prixMax) {
    $filtres = [];
    if ($villeDetectee) $filtres['ville']    = $villeDetectee;
    if ($typeDetecte)   $filtres['type']     = $typeDetecte;
    if ($theme)         $filtres['theme']    = $theme;
    if ($prixMax)       $filtres['prix_max'] = $prixMax;

    $logements = getLogements($filtres, 6);

    // Photos
    $defaultImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=70';
    $results = [];
    if (!empty($logements)) {
        $ids = array_map(fn($r) => (int) $r['id_logement'], $logements);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::execute(
            "SELECT id_logement, url FROM photo WHERE id_logement IN ($placeholders) AND est_principale = 1",
            $ids
        );
        $photos = [];
        foreach ($stmt->fetchAll() as $p) $photos[(int)$p['id_logement']] = $p['url'];

        foreach ($logements as $l) {
            $id = (int) $l['id_logement'];
            $results[] = [
                'id'          => $id,
                'titre'       => $l['titre'],
                'ville'       => $l['region_ville'],
                'type'        => $l['type'] ?? 'logement',
                'prix'        => (float) $l['prix_nuit'],
                'image'       => $photos[$id] ?? $defaultImg,
                'url'         => "logement-detail.php?id={$id}",
            ];
        }
    }

    $criteres = array_filter([
        $villeDetectee ? "à ".ucfirst($villeDetectee) : null,
        $typeDetecte   ? "type ".ucfirst($typeDetecte)  : null,
        $theme         ? "thème $theme"                  : null,
        $prixMax       ? "max {$prixMax} DT/nuit"        : null,
    ]);
    $critereStr = $criteres ? ' ('.implode(', ', $criteres).')' : '';

    if (empty($results)) {
        echo json_encode([
            'ok'   => true,
            'type' => 'no_results',
            'text' => "Désolé, aucun logement trouvé{$critereStr}. Essayez d'autres critères.",
            'suggestions' => ['Tous les logements','Logements à Tunis','Appartements'],
        ]);
        exit;
    }

    $count = count($results);
    echo json_encode([
        'ok'          => true,
        'type'        => 'search_results',
        'text'        => "J'ai trouvé **{$count} logement(s)**{$critereStr} 🏠",
        'logements'   => $results,
        'suggestions' => [
            'Réserver le premier',
            $villeDetectee ? 'Autre ville' : 'Djerba',
            $prixMax       ? 'Sans limite de prix' : 'Moins de 150 DT',
        ],
    ]);
    exit;
}

// 5 — Fallback : réponse générique
$suggestions = ['Logements à Djerba','Villa bord de mer','Studio pas cher','Voir tous les logements'];
echo json_encode([
    'ok'   => true,
    'type' => 'fallback',
    'text' => "Je n'ai pas bien compris votre demande. Vous pouvez me dire par exemple :\n• « Je cherche un logement à Hammamet »\n• « Villa avec piscine »\n• « Appartement moins de 200 DT »",
    'suggestions' => $suggestions,
]);
